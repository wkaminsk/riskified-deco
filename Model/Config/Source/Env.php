<?php
declare(strict_types=1);

namespace Riskified\Deco\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class Env implements OptionSourceInterface
{
    private const SANDBOX = 'sandboxw.decopayments.com';
    private const STAGING = 'stagingw.decopayments.com';
    private const PRODUCTION = 'w.decopayments.com';

    /**
     * {@inheritDoc}
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => static::SANDBOX,
                'label' => __('Sandbox')
            ],
            [
                'value' => static::STAGING,
                'label' => __('Staging')
            ],
            [
                'value' => static::PRODUCTION,
                'label' => __('Production')
            ]
        ];
    }
}
