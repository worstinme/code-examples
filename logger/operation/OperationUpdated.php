<?php


namespace app\components\log\operation;

use Yii;
use app\components\log\UpdatedLogTrait;
use app\models\Bills;
use app\models\Operations;
use yii\db\ActiveRecord;

/**
 * Class OrderPayment
 * @package app\components\log\order
 *
 * @property int $modelId
 */
class OperationUpdated extends OperationLoggerJobAbstract
{
    use UpdatedLogTrait;

    protected function attributes(): array
    {
        return [
            'value' => static function ($value) {
                return \Yii::$app->formatter->asDecimal($value).' IDR';
            },
            'debit' => static function ($value) {
                if ($value === Operations::DEBIT) {
                    return 'Поступление';
                }
                if ($value === Operations::CREDIT) {
                    return 'Расход';
                }
                if ($value === Operations::INTERNAL) {
                    return 'Не учитывать';
                }
                return $value;
            },
            'is_paid' => static function ($value) {
                return $value ? 'Да' : 'Нет';
            },
            'sender_bill_id' => static function ($value) {
                return ($bill = Bills::findOne($value)) !== null ? $bill->name : $value;
            },
            'recipient_bill_id' => static function ($value) {
                return ($bill = Bills::findOne($value)) !== null ? $bill->name : $value;
            },
        ];
    }

    protected function getName(): string
    {
        return '(Платеж #'.$this->getModelId().')';
    }

    protected function modelClass(): string
    {
        return Operations::class;
    }

    protected function getModelId(): int
    {
        return $this->operation_id;
    }

    protected function getModel(): ActiveRecord
    {
        return $this->operation;
    }
}
