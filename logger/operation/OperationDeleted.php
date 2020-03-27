<?php


namespace app\components\log\operation;

use app\components\log\LoggerJobAbstract;
use app\models\Bills;
use Yii;

class OperationDeleted extends LoggerJobAbstract
{
    public $operation_id;
    public $value;
    public $sender_bill_id;
    public $recipient_bill_id;
    public $is_paid;
    public $task_id;
    public $order_id;

    protected function getMessage(): \Generator
    {
        yield self::t('Удален платеж #{id} на сумму {value} IDR от {sender} для {recipient} со статусом {state}', [
            'id' => $this->operation_id,
            'value' => Yii::$app->formatter->asDecimal($this->value),
            'sender' => ($sender = Bills::findOne($this->sender_bill_id)) !== null ? $sender->name : $this->sender_bill_id,
            'recipient' => ($recipient = Bills::findOne($this->recipient_bill_id)) !== null ? $recipient->name : $this->recipient_bill_id,
            'state' => $this->is_paid ? 'Оплачено' : 'Не оплачено',
        ]);
    }

    protected function getTaskId()
    {
        return $this->task_id;
    }

    protected function getOrderId()
    {
        return $this->order_id;
    }
}
