<?php
namespace FraudLogix\Core\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status as StatusResource;

class AddOrderFraudReviewStatus implements DataPatchInterface
{
    public function __construct(
        private ModuleDataSetupInterface $setup,
        private StatusFactory $statusFactory,
        private StatusResource $statusResource
    ) {}

    public function apply()
    {
        $this->setup->getConnection()->startSetup();

        $status = $this->statusFactory->create();
        $this->statusResource->load($status, 'fraud_review', 'status');

        if (!$status->getStatus()) {
            $status->setStatus('fraud_review');
            $status->setLabel('Fraud Review');
            $this->statusResource->save($status);
        }

        try {
            $status->assignState('payment_review', false, true);
        } catch (\Throwable $e) {
        }

        $this->setup->getConnection()->endSetup();
    }

    public static function getDependencies(): array { return []; }
    public function getAliases(): array { return []; }
}
