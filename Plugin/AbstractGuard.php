<?php
namespace FraudLogix\Core\Plugin;

use FraudLogix\Core\Model\Config;

class AbstractGuard
{

    protected $guadType = Config::XML_PATH_ACTION_REGISTRATION;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * AbstractGuard constructor.
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    )
    {
        $this->config = $config;
    }

    private function actionCheck(int $value) {
        if ($value === 0) {
            return 'block';
        } elseif ($value === 1) {
            return 'flag';
        } elseif ($value === 2) {
            return 'allow';
        }
    }

    /**
     * Guard method to check if the action is allowed based on configuration.
     *
     * @param mixed $data
     * @return bool
     */
    protected function guardBoolean(
        $data
    ) {
        $action = 2;
        if (isset($data['MaskedDevices']) && $data['MaskedDevices']) {
            $configAction = $this->config->getMaskedDeviceAction($guadType);
            $action = min($action, $configAction);
        }
        if (isset($data['Proxy']) && $data['Proxy']) {
            $configAction = $this->config->getProxyAction($guadType);
            $action = min($action, $configAction);
        }
        if (isset($data['VPN']) && $data['VPN']) {
            $configAction = $this->config->getVpnAction($guadType);
            $action = min($action, $configAction);
        }
        if (isset($data['TOR']) && $data['TOR']) {
            $configAction = $this->config->getTorAction($guadType);
            $action = min($action, $configAction);
        }
        if (isset($data['DataCenter']) && $data['DataCenter']) {
            $configAction = $this->config->getDatacenterAction($guadType);
            $action = min($action, $configAction);
        }
        if (isset($data['SearchEngineBot']) && $data['SearchEngineBot']) {
            $configAction = $this->config->getSearchEngineBotAction($guadType);
            $action = min($action, $configAction);
        }
        if (isset($data['AbnormalTraffic']) && $data['AbnormalTraffic']) {
            $configAction = $this->config->getAbnormalTrafficAction($guadType);
            $action = min($action, $configAction);
        }
        return $action;
    }
}