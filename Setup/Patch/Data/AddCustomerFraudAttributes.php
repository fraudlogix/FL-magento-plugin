<?php
namespace FraudLogix\Core\Setup\Patch\Data;

use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Customer\Setup\CustomerSetupFactory;

class AddCustomerFraudAttributes implements DataPatchInterface
{
    private ModuleDataSetupInterface $setup;
    private CustomerSetupFactory    $customerSetupFactory;

    public function __construct(
        ModuleDataSetupInterface $setup,
        CustomerSetupFactory     $customerSetupFactory
    ) {
        $this->setup                = $setup;
        $this->customerSetupFactory = $customerSetupFactory;
    }

    public function apply()
    {
        $this->setup->getConnection()->startSetup();
        $customerSetup = $this->customerSetupFactory->create(['setup' => $this->setup]);

        $customerSetup->addAttribute(Customer::ENTITY, 'fraud_risk_data', [
            'type'         => 'text',
            'label'        => 'Fraud Risk JSON',
            'input'        => 'textarea',
            'required'     => false,
            'visible'      => false,
            'system'       => false,
            'position'     => 999,
            'user_defined' => true,
        ]);

        $customerSetup->addAttribute(Customer::ENTITY, 'fraud_risk_flag', [
            'type'         => 'int',
            'label'        => 'Potential Fraudster',
            'input'        => 'boolean',
            'required'     => false,
            'visible'      => false,
            'system'       => false,
            'position'     => 998,
            'user_defined' => true,
        ]);

        foreach (['fraud_risk_data','fraud_risk_flag'] as $code) {
            $attr = $customerSetup->getEavConfig()->getAttribute(Customer::ENTITY, $code);
            $attr->setData('used_in_forms', []);
            $attr->save();
        }

        $this->setup->getConnection()->endSetup();
    }

    public static function getDependencies(): array { return []; }
    public function getAliases(): array { return []; }
}
