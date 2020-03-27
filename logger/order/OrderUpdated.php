<?php


namespace app\components\log\order;

use app\models\User;
use Yii;
use app\components\log\UpdatedLogTrait;
use app\models\Models;
use app\models\Orders;
use app\models\Vehicles;

/**
 *
 * @property int $modelId
 */
class OrderUpdated extends OrderLoggerJobAbstract
{
    use UpdatedLogTrait;

    protected function modelClass(): string
    {
        return Orders::class;
    }

    protected function getName(): string
    {
        return '(Заказ)';
    }

    protected function attributes(): array
    {
        return [
            'state' => static function ($value) {
                return Yii::t('app', 'ORDER_STATE_'.$value);
            },
            'start_at' => static function ($value) {
                return Yii::$app->formatter->asDateTime($value);
            },
            'end_at' => static function ($value) {
                return Yii::$app->formatter->asDateTime($value);
            },
            'client_name',
            'client_wa',
            'client_wa_second',
            'client_email',
            'client_language',
            'client_passport_photo' => static function ($value) {
                return $value ? 'Загружено' : 'Не загружено';
            },
            'model_id' => static function ($value) {
                if (($model = Models::findOne($value)) !== null) {
                    return $model->name;
                }
            },
            'vehicle_id' => static function ($value) {
                if (($vehicle = Vehicles::findOne($value)) !== null) {
                    return $vehicle->platShort.' '.$vehicle->model->name;
                }
            },
            'managed_by'=> static function ($value) {
                if (($user = User::findOne($value)) !== null) {
                    return $user->username;
                }
                return 'undefined';
            },
            'visitor_id',
            'managed_reason',
            'notes',
            'prepaid_required',
            'force_payment_allowed',
            'full_payment_allowed',
            'is_prepaid',
            'vehicle_locked',
            'monthly',
            'urgent',
            'deposit_value',
            'deposit_status',
            'pid',
        ];
    }

    /**
     * @return int
     */
    protected function getModelId(): int
    {
        return $this->order_id;
    }

}
