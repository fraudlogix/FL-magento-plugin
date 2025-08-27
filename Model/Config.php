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

    private const XML_PATH_ACTIONS_REGISTRATION_LOW_RISK = 'fraudlogix/actions/registration/low_level_risk';
    private const XML_PATH_ACTIONS_REGISTRATION_MEDIUM_RISK = 'fraudlogix/actions/registration/medium_level_risk';
    private const XML_PATH_ACTIONS_REGISTRATION_HIGH_RISK = 'fraudlogix/actions/registration/high_level_risk';
    private const XML_PATH_ACTIONS_REGISTRATION_EXTREME_RISK = 'fraudlogix/actions/registration/extreme_level_risk';

    private const XML_PATH_ACTIONS_LOGIN_LOW_RISK = 'fraudlogix/actions/login/low_level_risk';
    private const XML_PATH_ACTIONS_LOGIN_MEDIUM_RISK = 'fraudlogix/actions/login/medium_level_risk';
    private const XML_PATH_ACTIONS_LOGIN_HIGH_RISK = 'fraudlogix/actions/login/high_level_risk';
    private const XML_PATH_ACTIONS_LOGIN_EXTREME_RISK = 'fraudlogix/actions/login/extreme_level_risk';

    private const XML_PATH_ACTIONS_CHECKOUT_LOW_RISK = 'fraudlogix/actions/order/low_level_risk';
    private const XML_PATH_ACTIONS_CHECKOUT_MEDIUM_RISK = 'fraudlogix/actions/order/medium_level_risk';
    private const XML_PATH_ACTIONS_CHECKOUT_HIGH_RISK = 'fraudlogix/actions/order/high_level_risk';
    private const XML_PATH_ACTIONS_CHECKOUT_EXTREME_RISK = 'fraudlogix/actions/order/extreme_level_risk';

    private const XML_PATH_DEV_ENABLE= 'fraudlogix/dev/enable_dev_mode';
    private const XML_PATH_DEV_IP = 'fraudlogix/dev/dev_ip';

    private const XML_PATH_CORE_ACTION_PATH = 'fraudlogix/actions';

    public const XML_PATH_ACTION_REGISTRATION = 'registration';
    public const XML_PATH_ACTION_LOGIN = 'login';
    public const XML_PATH_ACTION_CHECKOUT = 'checkout';

    private const XML_PATH_ACTION_MASKED_DEVICE = 'masked_device';
    private const XML_PATH_ACTION_PROXY = 'proxy';
    private const XML_PATH_ACTION_VPN = 'vpn';
    private const XML_PATH_ACTION_TOR = 'tor';
    private const XML_PATH_ACTION_DATA_CENTER = 'data_center';
    private const XML_PATH_ACTION_SEARCH_ENGINE_BOT = 'search_engine_bot';
    private const XML_PATH_ACTION_ABNORMAL_TRAFFIC = 'abnormal_traffic';


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
        // Alway allow low risk registrations
        return 2;
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
        // Always allow low risk logins
        return 2;
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
        // Alway allow low risk orders
        return 2;
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

    public function getMaskedDeviceAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_MASKED_DEVICE;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getProxyAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_PROXY;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getVpnAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_VPN;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getTorAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_TOR;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getDataCenterAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_DATA_CENTER;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getSearchEngineBotAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_SEARCH_ENGINE_BOT;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getAbnormalTrafficAction(string $pathType): int
    {
        if ($pathType !== self::XML_PATH_ACTION_REGISTRATION
            && $pathType !== self::XML_PATH_ACTION_LOGIN
            && $pathType !== self::XML_PATH_ACTION_CHECKOUT) {
            throw new \InvalidArgumentException('Invalid path type provided');
        }
        $path = self::XML_PATH_CORE_ACTION_PATH . '/' . $pathType . '/' . self::XML_PATH_ACTION_ABNORMAL_TRAFFIC;
        return (int)$this->scopeConfig->getValue(
            $path,
            ScopeInterface::SCOPE_STORE
        );
    }



}
