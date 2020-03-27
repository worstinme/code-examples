<?php


namespace app\components\log\order;

use Yii;
use app\models\Extras;

/**
 * Class OrderPayment
 * @package app\components\log\order
 */
class OrderExtraDeleted extends OrderLoggerJobAbstract
{
    public $extra_id;
    public $value;
    public $sum;
    public $quantity;
    public $discount;
    public $task_id;
    public $order_id;

    protected function getMessage(): \Generator
    {
        yield self::t('Удалена услуга {extra} {quantity} х {value} IDR = {sum} IDR (скидка {discount})', [
            'extra' => ($extra = Extras::findOne($this->extra_id)) !== null ? $extra->name : $this->extra_id,
            'quantity' => $this->quantity,
            'value' => Yii::$app->formatter->asDecimal($this->value),
            'sum' => Yii::$app->formatter->asDecimal($this->sum),
            'discount' => $this->discount,
        ]);
    }

    protected function getTaskId()
    {
        return $this->task_id;
    }
}
