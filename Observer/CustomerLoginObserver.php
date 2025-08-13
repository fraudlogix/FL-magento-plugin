<?php
namespace FraudLogix\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use FraudLogix\Core\Helper\ApiHelper;
use FraudLogix\Core\Model\Config;

class CustomerLoginObserver implements ObserverInterface
{
    private RequestInterface $request;
    private ApiHelper $apiHelper;
    private CustomerRepositoryInterface $customerRepository;
    private Config $config;

    public function __construct(
        RequestInterface $request,
        ApiHelper $apiHelper,
        CustomerRepositoryInterface $customerRepository,
        Config $config
    ) {
        $this->request            = $request;
        $this->apiHelper          = $apiHelper;
        $this->customerRepository = $customerRepository;
        $this->config             = $config;
    }

    public function execute(Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return; // Exit if the module is disabled
        }
        /** @var \Magento\Customer\Model\Customer $customer */
        $customer = $observer->getEvent()->getCustomer();

        $ip = $this->request->getClientIp();
        $riskData = $this->apiHelper->fetchData('', $ip, 'login');

        $customer->setCustomAttribute('fraud_risk_data', json_encode($riskData));
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
                $customer->setCustomAttribute('fraud_risk_flag', 1);
            }
            if ($action === 0) {
                $this->customerRepository->save($customer);
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('Your login has been blocked due to risk assessment.'),
                    null,
                    0,
                    null,
                    403 // HTTP 403 Forbidden
                );
            }
        }  
    }
}
