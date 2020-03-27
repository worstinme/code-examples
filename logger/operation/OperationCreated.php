<?php


namespace app\components\log\operation;

use app\components\log\LoggerException;
use app\components\log\LoggerJobAbstract;
use Yii;
use app\models\Operations;

/**
 * Class OrderCreated
 * @package app\components\log\operation
 *
 */
class OperationCreated extends OperationLoggerJobAbstract
{
    protected function getMessage(): \Generator
    {
        if ($this->operation !== null) {
            yield self::t('Добавлен платеж #{operation_id} в размере {value} IDR со счета {sender} на счет {recipient}',
                [
                    'value' => Yii::$app->formatter->asDecimal($this->operation->value),
                    'operation_id' => $this->operation->id,
                    'sender' => $this->operation->sender->name,
                    'recipient' => $this->operation->recipient->name,
                ]);
        }
    }

}
