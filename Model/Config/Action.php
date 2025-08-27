<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace FraudLogix\Core\Model\Config;

/**
 * @api
 * @since 100.0.2
 */
class Action implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [['value' => 0, 'label' => __('Block')], ['value' => 1, 'label' => __('Flag')], ['value' => 2, 'label' => __('Allow')]];
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return [0 => __('Block'), 1 => __('Flag'), 2 => __('Allow')];
    }
}
