<?php


namespace app\components\log\operation;

use Yii;
use app\components\log\LoggerException;
use app\components\log\LoggerJobAbstract;
use app\models\Operations;

/**
 * Class OperationLoggerJobAbstract
 * @package app\components\log\operation
 * @property Operations $operation
 */
abstract class OperationLoggerJobAbstract extends LoggerJobAbstract
{
    public $operation_id;
    protected $_operation;

    protected function getOperation()
    {
        if ($this->_operation === null) {
            $this->_operation = Operations::findOne($this->operation_id);
            if ($this->_operation === null || !($this->_operation->task_id || $this->_operation->order_id)) {
                throw new LoggerException('Не удалось найти операцию');
            }
        }
        return $this->_operation;
    }

    protected function getTaskId()
    {
        return $this->operation->task_id;
    }

    protected function getOrderId()
    {
        return $this->operation->order_id;
    }
}
