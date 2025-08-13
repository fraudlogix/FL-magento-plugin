<?php
namespace FraudLogix\Core\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Config
{
    private const XML_PATH_ENABLED = 'fraudlogix/general/enabled';

    private const XML_PATH_LOG_FILE  = 'fraudlogix/logging/log_file_path';
    private const XML_PATH_LOG_LEVEL = 'fraudlogix/logging/log_level';
    private const XML_PATH_LOG_ENABLED = 'fraudlogix/logging/enabled';

    private const XML_PATH_ACTIONS_REGISTRATION_LOW_RISK = 'fraudlogix/actions/registration_low_level_risk';
    private const XML_PATH_ACTIONS_REGISTRATION_MEDIUM_RISK = 'fraudlogix/actions/registration_medium_level_risk';
    private const XML_PATH_ACTIONS_REGISTRATION_HIGH_RISK = 'fraudlogix/actions/registration_high_level_risk';
    private const XML_PATH_ACTIONS_REGISTRATION_EXTREME_RISK = 'fraudlogix/actions/registration_extreme_level_risk';

    private const XML_PATH_ACTIONS_LOGIN_LOW_RISK = 'fraudlogix/actions/login_low_level_risk';
    private const XML_PATH_ACTIONS_LOGIN_MEDIUM_RISK = 'fraudlogix/actions_login_medium_level_risk';
    private const XML_PATH_ACTIONS_LOGIN_HIGH_RISK = 'fraudlogix/actions/login_high_level_risk';
    private const XML_PATH_ACTIONS_LOGIN_EXTREME_RISK = 'fraudlogix/actions/login_extreme_level_risk';

    private const XML_PATH_ACTIONS_CHECKOUT_LOW_RISK = 'fraudlogix/actions/order_low_level_risk';
    private const XML_PATH_ACTIONS_CHECKOUT_MEDIUM_RISK = 'fraudlogix/actions/order_medium_level_risk';
    private const XML_PATH_ACTIONS_CHECKOUT_HIGH_RISK = 'fraudlogix/actions/order_high_level_risk';
    private const XML_PATH_ACTIONS_CHECKOUT_EXTREME_RISK = 'fraudlogix/actions/order_extreme_level_risk';

    private const XML_PATH_DEV_ENABLE= 'fraudlogix/dev/enable_dev_mode';
    private const XML_PATH_DEV_IP = 'fraudlogix/dev/dev_ip';


    private ScopeConfigInterface $scopeConfig;

    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    public function isEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function getLogFile(): string
    {
        $file = $this->scopeConfig->getValue(self::XML_PATH_LOG_FILE, ScopeInterface::SCOPE_STORE);
        return $file ?: 'fraudrisk.log';
    }

    public function getLogLevel(): int
    {
        $level = (int)$this->scopeConfig->getValue(self::XML_PATH_LOG_LEVEL, ScopeInterface::SCOPE_STORE);
        return $level ?: \Monolog\Logger::INFO;
    }

    public function isLogEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_LOG_ENABLED, ScopeInterface::SCOPE_STORE);
    }

    public function registrationLowRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_REGISTRATION_LOW_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function registrationMediumRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_REGISTRATION_MEDIUM_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function registrationHighRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_REGISTRATION_HIGH_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function registrationExtremeRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_REGISTRATION_EXTREME_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function loginLowRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_LOGIN_LOW_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function loginMediumRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_LOGIN_MEDIUM_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function loginHighRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_LOGIN_HIGH_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function loginExtremeRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_LOGIN_EXTREME_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function checkoutLowRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_CHECKOUT_LOW_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function checkoutMediumRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_CHECKOUT_MEDIUM_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function checkoutHighRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_CHECKOUT_HIGH_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function checkoutExtremeRiskAction(): int
    {
        return (int)$this->scopeConfig->getValue(self::XML_PATH_ACTIONS_CHECKOUT_EXTREME_RISK, ScopeInterface::SCOPE_STORE);
    }

    public function isDevModeEnabled(): bool
    {
        return (bool)$this->scopeConfig->getValue(self::XML_PATH_DEV_ENABLE, ScopeInterface::SCOPE_STORE);
    }

    public function getDevIp(): string
    {
        return (string)$this->scopeConfig->getValue(self::XML_PATH_DEV_IP, ScopeInterface::SCOPE_STORE);
    }

}
