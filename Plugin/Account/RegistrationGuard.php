<?php
namespace FraudLogix\Core\Plugin\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use FraudLogix\Core\Model\Config;
use FraudLogix\Core\Logger\Logger;
use FraudLogix\Core\Helper\ApiHelper;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\CustomerFactory;

class RegistrationGuard
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
        Config $config,
        ApiHelper $apiHelper,
        CustomerRepositoryInterface $customerRepository,
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

    public function aroundCreateAccount(
        AccountManagementInterface $subject,
        callable $proceed,
        CustomerInterface $customer,
        $password = null,
        $redirectUrl = ''
    ) {
        $customerData = $this->guard($customer);
        $customer = $proceed($customer, $password, $redirectUrl);
        if (is_array($customerData)) {
            $target = $this->customerFactory->create()->load($customer->getId());
            $target->setData('fraud_risk_flag', $customerData['flag']);
            $target->setData('fraud_risk_data', $customerData['data']);
            $this->customerResource->saveAttribute($target, 'fraud_risk_flag');
            $this->customerResource->saveAttribute($target, 'fraud_risk_data');
        }
        return $customer;
    }


    public function aroundCreateAccountWithPasswordHash(
        AccountManagementInterface $subject,
        callable $proceed,
        CustomerInterface $customer,
        $hash,
        $redirectUrl = ''
    ) {
        $customerData = $this->guard($customer);
        $customer =  $proceed($customer, $hash, $redirectUrl);
        if (is_array($customerData)) {
            $target = $this->customerFactory->create()->load($customer->getId());
            $target->setData('fraud_risk_flag', $customerData['flag']);
            $target->setData('fraud_risk_data', $customerData['data']);
            $this->customerResource->saveAttribute($target, 'fraud_risk_flag');
            $this->customerResource->saveAttribute($target, 'fraud_risk_data');
        }
        return $customer;
    }

    /**
     * @param CustomerInterface $customer
     * @return array
     * @throws LocalizedException
     */
    private function guard(CustomerInterface $customer)
    {
        $flag = 0;
        $data = [];
        if (!$this->config->isEnabled()) {
            return $customer;
        }
        $ip = (string)($this->apiHelper->getClientIp() ?? '');
        try {
            $riskData = $this->apiHelper->fetchData('', $ip, 'register');
        } catch (\Exception $e) {
            $this->logger->warning('Risk service failed during registration, allowing registration: ' . $e->getMessage(), [
                'email' => $customer->getEmail(), 'ip' => $ip
            ]);
        }

        $customer->setData('fraud_risk_data', json_encode($riskData));
        $data = json_encode($riskData);
        $riskLevels = [
            'Low' => 'registrationLowRiskAction',
            'Medium' => 'registrationMediumRiskAction',
            'High' => 'registrationHighRiskAction',
            'Extreme' => 'registrationExtremeRiskAction',
        ];

        if (isset($riskLevels[$riskData['RiskScore']])) {
            $actionMethod = $riskLevels[$riskData['RiskScore']];
            $action = $this->config->$actionMethod();

            if ($action <= 1) {
                $customer->setData('fraud_risk_flag', 1);
                $flag = 1;
            }
            if ($action === 0) {
                $this->logger->info('Registration blocked due to high risk', [
                    'email' => $customer->getEmail(), 'ip' => $ip
                ]);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Registration blocked.')
                );
            }
        }

        return [
            'flag' => $flag,
            'data' => $data
        ];
    }
}
