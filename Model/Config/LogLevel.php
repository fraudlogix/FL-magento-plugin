<?php
namespace FraudLogix\Core\Model\Config;

use Magento\Framework\Data\OptionSourceInterface;
use Monolog\Logger;

class LogLevel implements OptionSourceInterface
{
    public function toOptionArray(): array
    {
        $levels = [
            Logger::DEBUG     => 'DEBUG',
            Logger::INFO      => 'INFO',
            Logger::NOTICE    => 'NOTICE',
            Logger::WARNING   => 'WARNING',
            Logger::ERROR     => 'ERROR',
            Logger::CRITICAL  => 'CRITICAL',
            Logger::ALERT     => 'ALERT',
            Logger::EMERGENCY => 'EMERGENCY',
        ];
        $options = [];
        foreach ($levels as $value => $label) {
            $options[] = ['value' => $value, 'label' => $label];
        }
        return $options;
    }
}
