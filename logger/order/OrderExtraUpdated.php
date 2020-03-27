<?php


namespace app\components\log\order;

use app\components\log\UpdatedLogTrait;
use app\models\Bills;
use app\models\Operations;
use app\models\OrdersExtras;
use yii\helpers\Html;

/**
 * Class OrderPayment
 * @package app\components\log\order
 */
class OrderExtraUpdated extends OrderLoggerJobAbstract
{
    use UpdatedLogTrait;

    protected function getName(): string
    {
        return Html::encode('(Услуга #'.$this->getModelId().' '.$this->model->extra->name.')');
    }

    protected function modelClass(): string
    {
        return OrdersExtras::class;
    }

    protected function attributes(): array
    {
        return [
            'value' => static function ($value) {
                return \Yii::$app->formatter->asDecimal($value).' IDR';
            },
            'quantity',
            'discount' => static function ($value) {
                return \Yii::$app->formatter->asDecimal($value).' IDR';
            },
            'managed_by',
        ];
    }
}
