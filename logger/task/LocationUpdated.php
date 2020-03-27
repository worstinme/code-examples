<?php


namespace app\components\log\task;

use app\models\Locations;
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
class LocationUpdated extends TaskLoggerJobAbstract
{
    use UpdatedLogTrait;

    protected function modelClass(): string
    {
        return Locations::class;
    }

    protected function getName(): string
    {
        return "(адрес #$this->model_id)";
    }

    protected function attributes(): array
    {
        return [
            'address',
            'lat',
            'lng',
        ];
    }
}
