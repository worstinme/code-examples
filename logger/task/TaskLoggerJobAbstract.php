<?php


namespace app\components\log\task;


use app\components\log\LoggerJobAbstract;
use yii\base\InvalidConfigException;

abstract class TaskLoggerJobAbstract extends LoggerJobAbstract
{
    public $task_id;
    public $order_id;

    public function __construct($config = [])
    {
        if (empty($config['task_id'])) {
            throw new InvalidConfigException('task_id is required');
        }
        parent::__construct($config);
    }

    protected function getOrderId()
    {
        if ($this->order_id) {
            return $this->order_id;
        }

        if (is_array($this->params) && array_key_exists('order_id', $this->params)) {
            return $this->params['order_id'];
        }

        return $this->task->order_id;
    }

    protected function getTaskId()
    {
        return $this->task_id;
    }
}
