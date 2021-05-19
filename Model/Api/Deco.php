<?php
declare(strict_types=1);

namespace Riskified\Deco\Model\Api;

use Magento\Framework\Event\ManagerInterface;
use Riskified\Common\Signature\HttpDataSignature;
//use Riskified\Decider\Model\Resource\Checkout;
use Riskified\Deco\Provider\ConfigProvider;
use Riskified\OrderWebhook\Model\Order;
use Riskified\OrderWebhook\Transport\CurlTransport;

class Deco
{
    public const ACTION_ELIGIBLE = 'eligible';
    public const ACTION_OPT_IN = 'opt_in';
    public const STATUS_NOT_ELIGIBLE = 'not_eligible';
    public const STATUS_NOT_OPT_IN = 'not_opt_in';

    /**
     * @var ManagerInterface
     */
    private $eventManager;

//    /**
//     * @var Checkout
//     */
//    private $checkoutResource;

    /**
     * @var ConfigProvider
     */
    private $configProvider;

    public function __construct(ManagerInterface $eventManager, ConfigProvider $configProvider)
    {
        $this->eventManager = $eventManager;
//        $this->checkoutResource = $checkoutResource;
        $this->configProvider = $configProvider;
    }

    public function post($quote, string $action)
    {
        if (!$this->configProvider->isRiskifiedEnabled() && !$this->configProvider->isDecoEnabled()) {
            return;
        }

        $transport = $this->getTransport();

        if (!$quote) {
            throw new \Exception("Order doesn't exists");
        }
        try {
            switch ($action) {
                case static::ACTION_ELIGIBLE:
                    $order = $this->load($quote);
                    $a = ["order" => [
                        'status' => 'eligible',
                        'description' => 'test'
                    ]];
                    $a = json_encode($a);
                    $response = json_decode($a);//$transport->eligible($order);
                    break;
                case static::ACTION_OPT_IN:
                    $order = $this->load($quote);
                    $response = $transport->optIn($order);
                    break;
            }

            $this->eventManager->dispatch(
                '',
                [
                    'order' => $quote,
                    'action' => $action,
                    'response' => $response
                ]
            );

            return $response;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function getTransport()
    {
        $transport = new CurlTransport(new HttpDataSignature(), $this->configProvider->getEnv());
        $transport->timeout = 15;
        $transport->use_https = true;

        return $transport;
    }

    /**
     * @param $quote
     *
     * @return Order
     */
    private function load($quote): Order
    {
        $order_array = array(
            'id' => 1,//$this->checkoutResource->getCheckoutId($quote->getId()),
        );

        return new \Riskified\OrderWebhook\Model\Checkout(array_filter($order_array, 'strlen'));
    }
}
