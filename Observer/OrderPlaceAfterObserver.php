<?php
namespace FraudLogix\Core\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\App\RequestInterface;
use FraudLogix\Core\Helper\ApiHelper;
use Magento\Sales\Api\OrderRepositoryInterface;
use FraudLogix\Core\Model\Config;
use Magento\Sales\Api\OrderManagementInterface;

class OrderPlaceAfterObserver implements ObserverInterface
{
    private RequestInterface $request;
    private ApiHelper $apiHelper;
    private OrderRepositoryInterface $orderRepository;
    private Config $config;
    private OrderManagementInterface $orderManagement;

    public function __construct(
        RequestInterface $request,
        ApiHelper $apiHelper,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        OrderManagementInterface $orderManagement
    ) {
        $this->request         = $request;
        $this->apiHelper       = $apiHelper;
        $this->orderRepository = $orderRepository;
        $this->config          = $config;
        $this->orderManagement = $orderManagement;
    }

    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $observer->getEvent()->getOrder();

        $ip = $this->request->getClientIp();
        $riskData = $this->apiHelper->fetchData('', $ip, 'order_place');

        

        $riskLevels = [
            'Low' => 'checkoutLowRiskAction',
            'Medium' => 'checkoutMediumRiskAction',
            'High' => 'checkoutHighRiskAction',
            'Extreme' => 'checkoutExtremeRiskAction',
        ];
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->info(
                'FraudLogix Risk Data: ' . json_encode($riskData)
            );
        \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->info(
                $riskLevels[$riskData['RiskScore']]
            );
        

        if (isset($riskLevels[$riskData['RiskScore']])) {
            $actionMethod = $riskLevels[$riskData['RiskScore']];
            $action = $this->config->$actionMethod();
            \Magento\Framework\App\ObjectManager::getInstance()
            ->get(\Psr\Log\LoggerInterface::class)->info(
                $actionMethod . ' action: ' . $action
            );
            $riskData['Descision'] = 'ALLOW';
            if ($action <= 1) {
                $riskData['Descision'] = 'ALERT';
                $order->setStatus('fraud_review'); // Set fraud risk flag
                $order->setData('fraud_risk_flag', 1);
            }
            if ($action === 0) {
                $riskData['Descision'] = 'BLOCK';
                $order->setData('fraud_risk_data', json_encode($riskData));
                $this->orderRepository->save($order);
                $this->orderManagement->cancel($order->getId());
                return; // Exit if the order is blocked
            }
            $order->setData('fraud_risk_data', json_encode($riskData));
            $this->orderRepository->save($order);
        }

        
        
    }
}
