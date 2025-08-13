<?php

namespace FraudLogix\Core\Plugin;


use Magento\Checkout\Controller\Onepage\Success as OnepageSuccess;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Sales\Api\OrderRepositoryInterface;
use FraudLogix\Core\Model\Config;
use FraudLogix\Core\Logger\Logger;
use Magento\Sales\Api\OrderManagementInterface;
use FraudLogix\Core\Helper\ApiHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;


class SuccessGuard
{
    /** @var RedirectFactory */
    protected $resultRedirectFactory;
    /** @var StoreManagerInterface */
    protected $storeManager;
    /** @var Cart */
    protected $cart;
    /** @var CheckoutSession */
    protected $checkoutSession;
    /** @var OrderRepositoryInterface */
    protected $orderRepository;
    /** @var Config */
    protected $config;
    /** @var OrderManagementInterface */
    protected $orderManagement;
    /** @var ApiHelper */
    protected $apiHelper;
    /** @var RequestInterface */
    protected $request;
    /** @var RemoteAddress */
    protected $remoteAddress;
    /** @var Logger */
    protected $logger;


    public function __construct(
        RedirectFactory $resultRedirectFactory,
        StoreManagerInterface $storeManager,
        Cart $cart,
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        Config $config,
        OrderManagementInterface $orderManagement,
        ApiHelper $apiHelper,
        RequestInterface $request,
        RemoteAddress $remoteAddress,
        Logger $logger
    ) {
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->storeManager = $storeManager;
        $this->cart = $cart;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->config = $config;
        $this->orderManagement = $orderManagement;
        $this->apiHelper = $apiHelper;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
    }

    public function aroundExecute($subject, callable $proceed)
    {
        if ($this->config->isEnabled() === false) {
            return $proceed();
        }
        $blocked = false;

        try {
            $session = $subject->getOnepage()->getCheckout();
            $lastOrderId = $this->checkoutSession->getLastRealOrder();
            $order = $session->getLastRealOrder();
            if ($order) {
                $status = $this->updateOrder($order);
                if ($status === 'block') {
                    $blocked = true;
                }
            }
        } catch (\Exception $e) {
            $this->logger->warning('Unable to resolve block decision: ' . $e->getMessage());
        }

        
        if ($blocked) {
            $result = $this->resultRedirectFactory->create();
            return $result->setPath('fraudlogix/blocked/index');
        }

        return $proceed();
    }

    private function isBlocked(?string $riskJson): bool
    {
        if ($riskJson) {
            $data = json_decode($riskJson, true);
            $decision = is_array($data) ? (string)($data['Descision'] ?? '') : '';
            if (strtoupper($decision) === 'BLOCK') {
                return true;
            }
        }
        return false;
    }

    private function updateOrder($order) {
        $ip = (string)($this->apiHelper->getClientIp() ?? '');
        $riskData = $this->apiHelper->fetchData('', $ip, 'order_place');

        $riskLevels = [
            'Low' => 'checkoutLowRiskAction',
            'Medium' => 'checkoutMediumRiskAction',
            'High' => 'checkoutHighRiskAction',
            'Extreme' => 'checkoutExtremeRiskAction',
        ];

        if (isset($riskLevels[$riskData['RiskScore']])) {
            $actionMethod = $riskLevels[$riskData['RiskScore']];
            $action = $this->config->$actionMethod();
            if ($action <= 1) {
                $order->setStatus('fraud_review'); // Set fraud risk flag
                $order->setData('fraud_risk_flag', 1);
            }
            if ($action === 0) {
                $order->setData('fraud_risk_data', json_encode($riskData));
                $order->setStatus('canceled'); 
                $this->orderRepository->save($order);
                $this->orderManagement->cancel($order->getId());

                return 'block'; // Exit if the order is blocked
            }
            if ($action > 1) {
                return 'allow'; // Default action if no risk level matched
            }
            $order->setData('fraud_risk_data', json_encode($riskData));
            $this->orderRepository->save($order);
            return 'alert';
    }
        return 'allow'; // Default action if no risk level matched
    }
}