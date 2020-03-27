<?php


namespace app\components\log\order;

use app\components\log\LoggerException;
use app\components\log\LoggerJobAbstract;

abstract class OrderLoggerJobAbstract extends LoggerJobAbstract
{
    public $order_id;

    public function __construct($config = [])
    {
        if (empty($config['order_id'])) {
            throw new LoggerException('order_id is required');
        }
        parent::__construct($config);
    }

    protected function getOrderId()
    {
        return $this->order_id;
    }

    protected function getTaskId()
    {
        return null;
    }
}
