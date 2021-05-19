<?php
declare(strict_types=1);

namespace Riskified\Deco\Provider;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Riskified\Decider\Model\Api\Config;

class ConfigProvider
{
    public const XML_PATH_DECO_ENABLED = 'riskified/deco/enabled';
    public const XML_PATH_DECO_ENV_TYPE = 'riskified/deco/environment_type';
    public const XML_PATH_DECO_BUTTON_COLOR = 'riskified/deco/button_color';
    public const XML_PATH_DECO_BUTTON_TEXT_COLOR = 'riskified/deco/button_text_color';
    public const XML_PATH_DECO_LOGO_URL = 'riskified/deco/logo_url';

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var Config
     */
    private $riskifiedConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig, Config $riskifiedConfig)
    {
        $this->scopeConfig = $scopeConfig;
        $this->riskifiedConfig = $riskifiedConfig;
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        return $this->scopeConfig->getValue(static::XML_PATH_DECO_ENV_TYPE, ScopeInterface::SCOPE_STORES);
    }

    /**
     * @return bool
     */
    public function isRiskifiedEnabled(): bool
    {
        return (bool) $this->riskifiedConfig->isEnabled();
    }

    /**
     * @return bool
     */
    public function isDecoEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(static::XML_PATH_DECO_ENABLED);
    }
}
