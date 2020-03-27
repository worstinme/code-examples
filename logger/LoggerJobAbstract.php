<?php


namespace app\components\log;

use app\models\Orders;
use app\models\Tasks;
use app\models\User;
use Yii;
use yii\base\BaseObject;
use yii\db\ActiveQuery;
use yii\helpers\Json;
use yii\queue\JobInterface;

/**
 * Class MileageUpdateJob
 * @package app\components\log
 *
 * @property $ttr int
 * @property $orderId int|null
 * @property $taskId int|null
 * @property $order Orders|null
 * @property $task Tasks|null
 * @property $user User|null
 * @property $message string
 */
abstract class LoggerJobAbstract extends BaseObject implements JobInterface
{
    public $user_id;
    private $time;
    public $params = [];
    private $user;

    public function init()
    {
        if ($this->user_id === null) {
            if (Yii::$app instanceof \yii\console\Application) {
                $this->user_id = 0;
            } elseif (!Yii::$app->user->isGuest) {
                $this->user_id = Yii::$app->user->getId();
            }
        }
        $this->time = time();
        parent::init();
    }

    abstract protected function getMessage(): \Generator;

    abstract protected function getOrderId();

    abstract protected function getTaskId();

    public function execute($queue): void
    {
        foreach ($this->getMessage() as $message) {
            if (is_array($message)) {
                $params = $this->jsonParams($message[1]);
                $message = $message[0];
            } else {
                $params = $this->jsonParams();
            }
            Yii::$app->db->createCommand()->insert('logs', [
                'order_id' => $this->orderId,
                'task_id' => $this->taskId,
                'message' => $message,
                'created_at' => $this->time,
                'user_id' => $this->user_id,
                'params' => $params,
            ])->execute();
        }
    }

    /**
     * @param  array  $params
     * @return string
     */
    protected function jsonParams($params = [])
    {
        if (is_array($this->params)) {
            $params = array_merge($this->params, $params);
        }
        return Json::encode($params);
    }

    /**
     * @return Orders|null
     */
    protected function getOrder(): ?Orders
    {
        return $this->orderQuery()->one();
    }

    /**
     * @return User|null
     */
    protected function getUser(): ?User
    {
        if ($this->user === null) {
            $this->user = User::findOne($this->user_id);
        }
        return $this->user;
    }

    /**
     * @return ActiveQuery
     */
    protected function orderQuery(): ActiveQuery
    {
        return Orders::find()->where(['id' => $this->orderId]);
    }

    /**
     * @return Tasks|null
     */
    protected function getTask(): ?Tasks
    {
        return $this->taskQuery()->one();
    }

    /**
     * @return ActiveQuery
     */
    protected function taskQuery(): ActiveQuery
    {
        return Tasks::find()->where(['id' => $this->taskId]);
    }

    public function getTtr()
    {
        return 10 * 60;
    }

    public function canRetry($attempt, $error): bool
    {
        return ($attempt < 2) && !($error instanceof LoggerException);
    }

    public function push(): void
    {
        Yii::$app->queue->push($this);
    }

    protected static function t($message, $params = [])
    {
        return Yii::t('app', $message, $params);
    }
}
