<?php
namespace FraudLogix\Core\Logger;

use Monolog\Logger as MonoLogger;

class Logger extends MonoLogger
{
    public function __construct(Handler $handler)
    {
        parent::__construct('fraudlogix');
        $this->pushHandler($handler);
    }
}
