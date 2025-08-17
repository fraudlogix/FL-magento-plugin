<?php
namespace FraudLogix\Core\Block\Adminhtml\Customer;

use Magento\Backend\Block\Template;
use Magento\Ui\Component\Layout\Tabs\TabInterface;
use Magento\Framework\Registry;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\RegistryConstants;
use Magento\Customer\Model\ResourceModel\Customer as CustomerResource;
use Magento\Customer\Model\CustomerFactory;

class Fraud extends Template implements TabInterface
{
    protected $_template = 'FraudLogix_Core::customer/fraud.phtml';

    /** @var CustomerFactory */
    protected CustomerFactory $customerFactory;

    /** @var CustomerRepositoryInterface */
    protected CustomerRepositoryInterface $customerRepository;

    /** @var CustomerResource */
    protected CustomerResource $customerResource;

    /** @var Registry */
    protected Registry $registry;

    public function __construct(
        Template\Context $context,
        CustomerRepositoryInterface $customerRepository,
        CustomerResource $customerResource,
        Registry $registry,
        CustomerFactory $customerFactory,
        array $data = []
    ) {
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->customerResource = $customerResource;
        $this->registry = $registry;
        parent::__construct($context, $data);
    }

    private ?CustomerInterface $customer = null;

    public function getCustomerId(): int
    {
        return (int)($this->getRequest()->getParam('id')
            ?: $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID));
    }

    public function getCustomer(): ?CustomerInterface
    {
        if ($this->customer !== null) return $this->customer;
        $id = $this->getCustomerId();
        if (!$id) return null;
        try { $this->customer = $this->customerRepository->getById($id); }
        catch (\Throwable) { $this->customer = null; }
        return $this->customer;
    }

    private function getRawJson(): string
    {
        $id = $this->getCustomerId();
        if (!$id) return '';
        try {
            $val = $this->customerResource->getAttributeRawValue($id, 'fraud_risk_data', 0);
            return is_scalar($val) ? (string)$val : '';
        } catch (\Throwable) {
            return '';
        }
    }

    public function getRiskData(): array
    {
        $raw = '';
        $target = $this->customerFactory->create()
            ->getCollection()
            ->addAttributeToSelect('fraud_risk_data')
            ->getItemById($this->getCustomerId());
        if ($target) {
            $raw = $target->getData('fraud_risk_data');
        }

        if ($raw === '') {
            $raw = $this->getRawJson();
        }
        if ($raw === '') return [];


        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $data = json_decode(html_entity_decode($raw), true) ?: [];
        }
        return $data;
    }

    public function getFlag(): ?int
    {
        $id = $this->getCustomerId();
        if (!$id) return null;
        try {
            $val = $this->customerResource->getAttributeRawValue($id, 'fraud_risk_flag', 0);
            return $val === null ? null : (int)$val;
        } catch (\Throwable) {
            return null;
        }
    }

    public function getTabLabel(){ return __('Fraud Risk'); }
    public function getTabTitle(){ return __('Fraud Risk'); }
    public function canShowTab(){ return (bool)$this->getCustomerId(); }
    public function isHidden(){ return !$this->canShowTab(); }
    public function getTabClass(){ return ''; }
    public function getTabUrl(){ return ''; }
    public function isAjaxLoaded(){ return false; }
}
