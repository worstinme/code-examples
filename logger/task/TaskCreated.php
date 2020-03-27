<?php


namespace app\components\log\task;

use Yii;
use app\components\log\task\TaskLoggerJobAbstract;
use app\models\Locations;
use app\models\Tasks;

class TaskCreated extends TaskLoggerJobAbstract
{
    public $location_id;

    public function getMessage(): \Generator
    {
        if ($this->location_id !== null) {
            if (($location = Locations::findOne($this->location_id))) {
                if ($this->task) {

                    if ($this->task->type === Tasks::TYPE_DELIVERY) {
                        yield self::t('Добавлен новый адрес доставки: {address}', [
                            'address' => $location->address,
                        ]);
                    } elseif ($this->task->type === Tasks::TYPE_PICKUP) {
                        yield self::t('Добавлен новый адрес возврата: {address}', [
                            'address' => $location->address,
                        ]);
                    } else {
                        yield self::t('Добавлен новый адрес: {address}', [
                            'address' => $location->address,
                        ]);
                    }
                }
            }
        } else {
            yield [
                self::t('Создана задача {type}', [
                    'type' => Yii::t('app', 'TASK_TYPE_'.$this->task->type),
                ]), [
                    'start' => $this->task->start,
                    'end' => $this->task->end,
                    'vehicle' => $this->task->vehicle_id ? $this->task->vehicle->plat : null,
                ]
            ];
        }
    }
}
