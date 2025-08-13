<?php
namespace FraudLogix\Core\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class EnableFraudFlagInAdmin implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private CustomerSetupFactory $customerSetupFactory
    ) {}

    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->moduleDataSetup]);

        $attr = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, 'fraud_risk_flag');
        if ($attr && $attr->getId()) {
            $attr->setData('is_visible', 1);
            $attr->setData('frontend_input', 'boolean');
            $attr->setData('source_model', Boolean::class);
            $attr->setData('default', 0);

            $forms = (array)$attr->getData('used_in_forms');
            $forms = array_unique(array_filter(array_merge($forms, ['adminhtml_customer'])));
            $attr->setData('used_in_forms', $forms);

            $attr->setData('is_used_in_grid', 1);
            $attr->setData('is_visible_in_grid', 1);
            $attr->setData('is_filterable_in_grid', 1);
            $attr->setData('is_searchable_in_grid', 0);

            $attr->setData('sort_order', 998);
            $attr->save();
        }

        $this->moduleDataSetup->getConnection()->endSetup();
    }

    public static function getDependencies(): array { return []; }
    public function getAliases(): array { return []; }
}
