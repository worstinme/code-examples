<?php


namespace app\components\log\order;

class OrderLogMessage extends OrderLoggerJobAbstract
{
    public $m;
    public $p;

    protected function getMessage(): \Generator
    {
        yield self::t($this->m, $this->p);
    }
}
