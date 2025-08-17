<?php
namespace FraudLogix\Core\Block\Adminhtml\Order\View\Tab;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Widget\Tab\TabInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\OrderInterface;

class Fraud extends Template implements TabInterface
{
    protected $_template = 'FraudLogix_Core::order/fraud.phtml';

    /** @var Registry */
    protected Registry $registry;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        array $data = []
    ) {
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    public function getOrder(): ?OrderInterface
    {
        return $this->registry->registry('current_order');
    }

    public function getRiskData(): array
    {
        $raw = (string)($this->getOrder()?->getData('fraud_risk_data') ?? '');
        $a = json_decode($raw, true);
        return is_array($a) ? $a : [];
    }

    public function getTabLabel() { return __('Fraud Risk'); }
    public function getTabTitle() { return __('Fraud Risk'); }
    public function canShowTab()   { return true; }
    public function isHidden()     { return false; }
}
