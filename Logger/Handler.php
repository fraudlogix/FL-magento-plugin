<?php
namespace FraudLogix\Core\Logger;

use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use FraudLogix\Core\Model\Config as FraudConfig;

class Handler extends StreamHandler
{
    public function __construct(FraudConfig $config)
    {
        $file = BP . '/var/log/' . $config->getLogFile();
        $level = $config->getLogLevel();
        parent::__construct($file, $level);
    }
}
