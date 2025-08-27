<?php
namespace FraudLogix\Core\Plugin\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use FraudLogix\Core\Model\Config;
use FraudLogix\Core\Logger\Logger;
use FraudLogix\Core\Helper\ApiHelper;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\CustomerFactory;

class LoginGuard extends \FraudLogix\Core\Plugin\AbstractGuard
{
    protected $guadType = Config::XML_PATH_ACTION_LOGIN;

    /**
     * @var RemoteAddress
     */
    protected RemoteAddress $remoteAddress;
    /**
     * @var Config
     */
    // protected Config $config;
    /**
     * @var ApiHelper
     */
    protected ApiHelper $apiHelper;
    /**
     * @var CustomerRepositoryInterface
     */
    protected CustomerRepositoryInterface $customerRepository;
    /**
     * @var CustomerResource
     */
    protected CustomerResource $customerResource;
    /**
     * @var CustomerFactory
     */
    protected CustomerFactory $customerFactory;
    /**
     * @var Logger
     */
    protected Logger $logger;

    public function __construct(
        RemoteAddress $remoteAddress,
        CustomerRepositoryInterface $customerRepository,
        Config $config,
        ApiHelper $apiHelper,
        CustomerResource $customerResource,
        CustomerFactory $customerFactory,
        Logger $logger
    ) {
        $this->remoteAddress = $remoteAddress;
        // $this->config = $config;
        $this->apiHelper = $apiHelper;
        $this->customerRepository = $customerRepository;
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
        parent::__construct($config);
    }

    /**
     * around AccountManagement::authenticate($username, $password)
     * @return CustomerInterface
     * @throws LocalizedException
     */
    public function aroundAuthenticate(
        AccountManagementInterface $subject,
        callable $proceed,
        $username,
        $password
    ) {
        $customer = $proceed($username, $password);
        $customer = $this->guard($customer);
        return $customer;
    }

    /**
     * @param CustomerInterface $customer
     * @return CustomerInterface
     * @throws LocalizedException
     */
    private function guard(CustomerInterface $customer)
    {
        if (!$this->config->isEnabled()) {
            return $customer;
        }
        $ip = (string)($this->apiHelper->getClientIp() ?? '');
        try {
            $riskData = $this->apiHelper->fetchData('', $ip, 'register');
        } catch (\Exception $e) {
            $this->logger->warning('Risk service failed during login, allowing login: ' . $e->getMessage(), [
                'email' => $customer->getEmail(), 'ip' => $ip
            ]);
        }
        $target = $this->customerFactory->create()->load($customer->getId());
        $target->setData('fraud_risk_data', json_encode($riskData));
        $this->customerResource->saveAttribute($target, 'fraud_risk_data');
        $riskLevels = [
            'Low' => 'loginLowRiskAction',
            'Medium' => 'loginMediumRiskAction',
            'High' => 'loginHighRiskAction',
            'Extreme' => 'loginExtremeRiskAction',
        ];

        if (isset($riskLevels[$riskData['RiskScore']])) {
            $actionMethod = $riskLevels[$riskData['RiskScore']];
            $action = $this->config->$actionMethod();
            $action = min($this->guardBoolean($riskData), $action);

            if ($action <= 1) {
                $target->setData('fraud_risk_flag', 1);
                $this->customerResource->saveAttribute($target, 'fraud_risk_flag');
            }
            if ($action === 0) {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Login blocked due to high fraud risk.')
                );
            }
        }

        return $target;
    }
}
