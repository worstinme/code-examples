<?php


namespace app\components\log\task;

use Yii;
use app\components\log\UpdatedLogTrait;
use app\components\log\task\TaskLoggerJobAbstract;
use app\models\Orders;
use app\models\Tasks;
use app\models\Vehicles;
use function Clue\StreamFilter\fun;

/**
 *
 * @property int $modelId
 */
class TaskUpdated extends TaskLoggerJobAbstract
{
    use UpdatedLogTrait;

    protected function modelClass(): string
    {
        return Tasks::class;
    }

    protected function getName(): string
    {
        return '(Задача #'.$this->task_id.' '.Yii::t('app', 'TASK_TYPE_'.$this->task->type).')';
    }

    protected function attributes(): array
    {
        return [
            'approved',
            'type' => static function ($value) {
                return Yii::t('app', 'TASK_TYPE_'.$value);
            },
            'start_at' => static function ($value) {
                return Yii::$app->formatter->asDateTime($value);
            },
            'end_at' => static function ($value) {
                return Yii::$app->formatter->asDateTime($value);
            },
            'vehicle_id' => static function ($value) {
                if (($vehicle = Vehicles::findOne($value)) !== null) {
                    return $vehicle->platShort.' '.$vehicle->model->name;
                }
            },
            'note',
            'worker_notified',
            'date_effect',
            'is_active',
            'finished_at' => static function ($value) {
                return $value ? 'Завершено в '.Yii::$app->formatter->asDateTime($value) : 'Не завершено';
            },
            'vehicle_mileage',
            'maintenance_delay',
        ];
    }

    /**
     * @return int
     */
    protected function getModelId(): int
    {
        return $this->task_id;
    }

}
