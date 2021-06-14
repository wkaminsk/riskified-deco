<?php
declare(strict_types=1);

namespace Riskified\Deco\Controller\Checkout;

use Magento\Authorizenet\Model\Directpost\Session as DirectpostSessionModel;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Riskified\Decider\Model\Api\Api;
use Riskified\Decider\Model\Api\Config;
use Riskified\Decider\Model\Api\Log;
use Riskified\Decider\Model\Api\Order as OrderApi;
use Riskified\Decider\Model\Api\Order\Config as OrderConfig;
use Riskified\Deco\Model\Api\Deco;

class OptIn extends Action implements HttpPostActionInterface
{
    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var Log
     */
    private $logger;

    /**
     * @var Deco
     */
    private $deco;

    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var OrderApi
     */
    private $orderApi;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Config
     */
    private $riskifiedConfig;

    /**
     * @var OrderConfig
     */
    private $riskifiedOrderConfig;

    /**
     * @var CartManagementInterface
     */
    private $quoteManagement;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    public function __construct(
        Session $checkoutSession,
        Log $logger,
        Deco $deco,
        OrderInterfaceFactory $orderFactory,
        CartRepositoryInterface $quoteRepository,
        OrderApi $orderApi,
        OrderRepositoryInterface $orderRepository,
        Config $riskifiedConfig,
        OrderConfig $riskifiedOrderConfig,
        CartManagementInterface $quoteManagement,
        ManagerInterface $eventManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->deco = $deco;
        $this->orderFactory = $orderFactory;
        $this->quoteRepository = $quoteRepository;
        $this->orderApi = $orderApi;
        $this->orderRepository = $orderRepository;
        $this->riskifiedConfig = $riskifiedConfig;
        $this->riskifiedOrderConfig = $riskifiedOrderConfig;
        $this->quoteManagement = $quoteManagement;
        $this->eventManager = $eventManager;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->logger->log('Deco OptIn request, quote_id: ' . $this->checkoutSession->getQuoteId());

            $response = $this->deco->post(
                $this->checkoutSession->getQuote(),
                Deco::ACTION_OPT_IN
            );

            if ($response->order->status === Deco::ACTION_OPT_IN) {
                if (!$this->checkoutSession->getLastRealOrder()->getQuoteId() !== $this->checkoutSession->getQuote()->getId()) {
                    $this->prepareOrder();
                }
                $this->processOrder($this->checkoutSession->getQuote()->getPayment()->getMethod());

                $this->orderApi->post(
                    $this->checkoutSession->getLastRealOrder(),
                    Api::ACTION_CREATE
                );
            }

            return $result->setData([
                'success' => true,
                'status' => $response->order->status,
                'message' => $response->order->description
            ]);
        } catch (\Exception $e) {
            $this->logger->logException($e);

            return $result->setData([
                'success' => false,
                'status' => Deco::STATUS_NOT_OPT_IN,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * @param string $paymentMethod
     *
     * @return void
     * @throws \Exception
     */
    private function processOrder(string $paymentMethod): void
    {
        if ($paymentMethod !== 'authorizenet_directpost') {
            return;
        }

        $incrementId = $this->checkoutSession->getLastRealOrderId();
        if ($incrementId) {
            $order = $this->orderFactory->create()->loadByIncrementId($incrementId);
            if ($order->getId()) {
                try {
                    $quote = $this->quoteRepository->get($order->getQuoteId());
                    $quote->setIsActive(0);
                    $this->quoteRepository->save($quote);
                    $this->orderApi->unCancelOrder(
                        $order,
                        __(
                            'Payment by %1 has been declined. Order processed by Deco Payments',
                            $order->getPayment()->getMethod()
                        )
                    );
                    $order->getPayment()->setMethod('deco');
                    $this->orderRepository->save($order);

                    if ($this->riskifiedConfig->getConfigStatusControlActive()) {
                        $state = Order::STATE_PROCESSING;
                        $status = $this->riskifiedOrderConfig->getOnHoldStatusCode();
                        $order->setState($state)->setStatus($status);
                        $order->addStatusHistoryComment('Order submitted to Riskified', false);
                    }
                } catch (NoSuchEntityException $e) {
                    $this->logger->logException($e);
                }
            }
        }
    }

    /**
     * @return void
     */
    private function prepareOrder(): void
    {
        $previousMethod = $this->checkoutSession->getQuote()->getPayment()->getMethod();
        $this->checkoutSession->getQuote()->getPayment()->setMethod('deco');
        $order = $this->quoteManagement->submit($this->checkoutSession->getQuote());

        if (is_null($order)) {
            throw new LocalizedException(
                __('An error occurred on the server. Please try to place the order again.')
            );
        }

        $order->addStatusHistoryComment(
            __(
                'Payment by %1 has been declined. Order processed by Deco Payments',
                $previousMethod
            ),
            false
        );
        if ($this->riskifiedConfig->getConfigStatusControlActive()) {
            $state = Order::STATE_PROCESSING;
            $status = $this->riskifiedOrderConfig->getOnHoldStatusCode();
            $order->setState($state)->setStatus($status);
            $order->addStatusHistoryComment('Order submitted to Riskified', false);
        }
        $this->orderRepository->save($order);

        $this->checkoutSession->setLastQuoteId($this->checkoutSession->getQuote()->getId());
        $this->checkoutSession->setLastSuccessQuoteId($this->checkoutSession->getQuote()->getId());
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());

        $this->eventManager->dispatch(
            'checkout_submit_all_after',
            [
                'order' => $order,
                'quote' => $this->checkoutSession->getQuote()
            ]
        );
    }
}
