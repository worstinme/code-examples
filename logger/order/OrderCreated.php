<?php


namespace app\components\log\order;

use Yii;
use app\models\Operations;
use app\models\OrdersExtras;
use app\models\Transactions;

class OrderCreated extends OrderLoggerJobAbstract
{
    public $order_extra_id;
    public $order_id;

    protected function getMessage(): \Generator
    {
        if ($this->order_extra_id !== null) {
            if (($extra = OrdersExtras::findOne($this->order_extra_id)) !== null) {
                yield self::t('Добавлена услуга {name}: {quantity} х {value} = {sum} IDR',
                        [
                            'sum' => Yii::$app->formatter->asDecimal($extra->sum),
                            'value' => $extra->value,
                            'quantity' => $extra->quantity,
                            'name' => $extra->extra->name,
                        ]);
            }
        } else {
            yield self::t('Создан заказ на {model} c {start} по {end}', [
                'model' => $this->order->model->name,
                'start' => $this->order->start,
                'end' => $this->order->end,
            ]);
        }
    }
}
