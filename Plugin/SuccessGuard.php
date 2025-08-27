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


class SuccessGuard extends \FraudLogix\Core\Plugin\AbstractGuard
{
    protected $guadType = Config::XML_PATH_ACTION_CHECKOUT;

    /** @var RedirectFactory */
    protected RedirectFactory $resultRedirectFactory;
    /** @var StoreManagerInterface */
    protected StoreManagerInterface $storeManager;
    /** @var Cart */
    protected Cart $cart;
    /** @var CheckoutSession */
    protected CheckoutSession $checkoutSession;
    /** @var OrderRepositoryInterface */
    protected OrderRepositoryInterface $orderRepository;
    /** @var Config */
    // protected Config $config;
    /** @var OrderManagementInterface */
    protected OrderManagementInterface $orderManagement;
    /** @var ApiHelper */
    protected ApiHelper $apiHelper;
    /** @var RequestInterface */
    protected RequestInterface $request;
    /** @var RemoteAddress */
    protected RemoteAddress $remoteAddress;
    /** @var Logger */
    protected Logger $logger;


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
        // $this->config = $config;
        $this->orderManagement = $orderManagement;
        $this->apiHelper = $apiHelper;
        $this->request = $request;
        $this->remoteAddress = $remoteAddress;
        $this->logger = $logger;
        parent::__construct($config);
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

    /**
     * Check if the risk data indicates a block decision
     *
     * @param string|null $riskJson
     * @return bool
     */
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

    /**
     * Update the order with risk data and determine the action based on risk score
     *
     * @param $order
     * @return string
     */
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
            $action = min($this->guardBoolean($riskData), $action);
            if ($action <= 1) {
                $order->setStatus('fraud_review');
                $order->setData('fraud_risk_flag', 1);
            }
            if ($action === 0) {
                $order->setData('fraud_risk_data', json_encode($riskData));
                $order->setStatus('canceled'); 
                $this->orderRepository->save($order);
                $this->orderManagement->cancel($order->getId());

                return 'block';
            }
            if ($action > 1) {
                return 'allow';
            }
            $order->setData('fraud_risk_data', json_encode($riskData));
            $this->orderRepository->save($order);
            return 'alert';
    }
        return 'allow';
    }
}