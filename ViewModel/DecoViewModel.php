<?php
declare(strict_types=1);

namespace Riskified\Deco\ViewModel;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Store\Model\ScopeInterface as ScopeInterface;
use Riskified\Deco\Provider\ConfigProvider;

class DecoViewModel implements ArgumentInterface
{
    /**
     * @var ConfigProvider
     */
    private $configProvider;

    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    private $customerSession;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(
        ConfigProvider $configProvider, 
        ScopeConfigInterface $scopeConfig,
        \Magento\Customer\Model\Session $customerSession
    ) {
        $this->configProvider = $configProvider;
        $this->scopeConfig = $scopeConfig;
        $this->customerSession = $customerSession;
    }

    /**
     * @return bool
     */
    public function isRiskifiedEnabled(): bool
    {
        return $this->configProvider->isRiskifiedEnabled();
    }

    /**
     * @return string
     */
    public function getShopDomain(): string
    {
        return $this->scopeConfig->getValue(
            'riskified/riskified/domain'
        );
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->configProvider->isDecoEnabled();
    }

    public function getSessionId(): string
    {
        return $this->customerSession->getSessionId();
    }

    /**
     * @return string|null
     */
    public function getButtonColor(): ?string
    {
        return $this->scopeConfig->getValue(ConfigProvider::XML_PATH_DECO_BUTTON_COLOR);
    }

    /**
     * @return string|null
     */
    public function getButtonTextColor(): ?string
    {
        return $this->scopeConfig->getValue(ConfigProvider::XML_PATH_DECO_BUTTON_TEXT_COLOR);
    }

    /**
     * @return string|null
     */
    public function getLogoUrl(): ?string
    {
        return $this->scopeConfig->getValue(ConfigProvider::XML_PATH_DECO_LOGO_URL);
    }
}
