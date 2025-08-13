<?php
namespace FraudLogix\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use FraudLogix\Core\Helper\ApiHelper;
use FraudLogix\Core\Model\Config;

class CustomerRegisterObserver implements ObserverInterface
{
    private RequestInterface $request;
    private CustomerRepositoryInterface $customerRepository;
    private ApiHelper $apiHelper;
    private Config $config;

    public function __construct(
        RequestInterface $request,
        CustomerRepositoryInterface $customerRepository,
        ApiHelper $apiHelper,
        Config $config
    ) {
        $this->request            = $request;
        $this->customerRepository = $customerRepository;
        $this->apiHelper          = $apiHelper;
        $this->config             = $config;
    }

    public function execute(Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return; // Exit if the module is disabled
        }
        /** @var \Magento\Customer\Api\Data\CustomerInterface $customer */
        $customer = $observer->getEvent()->getCustomer();

        $ip = $this->request->getClientIp();

        $riskData = $this->apiHelper->fetchData('', $ip, 'register');

        $customer->setCustomAttribute('fraud_risk_data', json_encode($riskData));
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
                $customer->setCustomAttribute('fraud_risk_flag', 1);
            }
            if ($action === 0) {
                $this->customerRepository->save($customer);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Registration blocked.'),
                    null,
                    0,
                    null,
                    403 // HTTP 403 Forbidden
                );
            }
        }
    }
}