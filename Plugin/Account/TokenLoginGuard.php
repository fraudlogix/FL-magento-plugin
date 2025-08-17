<?php
namespace FraudLogix\Core\Plugin\Account;

use Magento\Integration\Api\CustomerTokenServiceInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use FraudLogix\Core\Model\Config;
use FraudLogix\Core\Model\FraudService;
use FraudLogix\Core\Logger\Logger;
use FraudLogix\Core\Helper\ApiHelper;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\CustomerFactory;

class TokenLoginGuard
{
    /**
     * @var RemoteAddress
     */
    protected RemoteAddress $remoteAddress;
    /**
     * @var Config
     */
    protected Config $config;
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
        $this->config = $config;
        $this->apiHelper = $apiHelper;
        $this->customerRepository = $customerRepository;
        $this->customerResource = $customerResource;
        $this->customerFactory = $customerFactory;
        $this->logger = $logger;
    }

    /**
     * around CustomerTokenService::createCustomerAccessToken($username, $password)
     * @return string token
     * @throws LocalizedException
     */
    public function aroundCreateCustomerAccessToken(
        CustomerTokenServiceInterface $subject,
        callable $proceed,
        $username,
        $password
    ) {
        $customer = $proceed($username, $password);
        $customer = $this->guard($customer);
        return $customer;
    }

    /**
     * @param string $customer
     * @return string
     * @throws LocalizedException
     */
    private function guard(CustomerInterface $customer)
    {
        if (!$this->config->isEnabled()) {
            return $customer; // Exit if the module is disabled
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

        return $customer;
    }
}
