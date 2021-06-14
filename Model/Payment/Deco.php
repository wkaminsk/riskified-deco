<?php
declare(strict_types=1);

namespace Riskified\Deco\Model\Payment;

use Magento\Payment\Model\Method\AbstractMethod;

class Deco extends AbstractMethod
{
    public const PAYMENT_METHOD_DECO_CODE = 'deco';

    protected $_code = self::PAYMENT_METHOD_DECO_CODE;
}
