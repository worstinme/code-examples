<?php


namespace app\components\log;

use Yii;

class Logger
{
    private $level = 0;
    private $collection = [];

    /**
     * @param  int  $level
     */
    public function setLevel($level): void
    {
        $this->level = $level;
    }

    /**
     * Добавдяет задачу на логгирование в коллекцию
     * @param  LoggerJobAbstract  $job
     */
    public function add(LoggerJobAbstract $job): void
    {
        if (!array_key_exists($this->level, $this->collection)) {
            $this->collection[$this->level] = [];
        }
        $this->collection[$this->level][] = $job;
    }

    /**
     * Очищает коллекцию задач заданного уровня или выше
     * @param  null  $level
     */
    public function rollback($level = null): void
    {
        $min_level = $level ?? $this->level;
        foreach (array_keys($this->collection) as $value) {
            if ($value >= $min_level) {
                unset($this->collection[$value]);
            }
        }
    }

    /**
     * Отправляет задачи определенного уровня и выше в очередь
     * @param  null  $level
     */
    public function commit($level = null): void
    {
        $min_level = $level ?? $this->level;
        foreach ($this->collection as $value => $collection) {
            if ($value >= $min_level) {
                foreach ($collection as $job) {
                    Yii::$app->queue->push($job);
                }
                unset($this->collection[$value]);
            }
        }
    }

    /**
     * Отправляет все осташиеся задачи в очередь
     */
    public function __destruct()
    {
        $this->commit(0);
    }
}
