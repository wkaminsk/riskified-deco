<?php
declare(strict_types=1);

namespace Riskified\Deco\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Riskified\Decider\Model\Api\Order\Log;
use Riskified\Deco\Model\Api\Deco;

class IsEligible extends Action implements HttpPostActionInterface
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
     * @param Session $checkoutSession
     * @param Log $logger
     * @param Deco $deco
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        Log $logger,
        Deco $deco
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->deco = $deco;
    }

    public function execute()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            $this->logger->log('Deco isEligible request, quote_id: ' . $this->checkoutSession->getQuoteId());
            $response = $this->deco->post(
                $this->checkoutSession->getQuote(),
                Deco::ACTION_ELIGIBLE
            );

            return $result->setData([
                'success' => true,
                'status' => "not_eligible",
                'message' => $response->order->description
            ]);
        } catch (\Riskified\OrderWebhook\Exception\UnsuccessfulActionException $e) {
            $message = json_decode($e->jsonResponse, true);
            return $result->setData([
                'success' => false,
                'status' => "not_eligible",
                'message' => $message['error']['message']
            ]);
        } catch (\Exception $e) {
            $this->logger->logException($e);

            return $result->setData([
                'success' => false,
                'status' => "not_eligible",
                'message' => $e->getMessage()
            ]);
        }
    }
}
