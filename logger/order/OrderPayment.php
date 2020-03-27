<?php


namespace app\components\log\order;

use app\models\Operations;
use app\models\Transactions;
use function GuzzleHttp\Psr7\uri_for;

/**
 * Class OrderPayment
 * @package app\components\log\order
 */
class OrderPayment extends OrderLoggerJobAbstract
{
    public $operation_id;
    public $transaction_id;

    protected function getMessage(): \Generator
    {
        if (($operation = Operations::findOne($this->operation_id)) !== null) {
            yield [
                self::t('Поступила оплата #{operation_id} в размере {value} IDR на счет {bill}', [
                    'value' => $operation->value,
                    'operation_id' => $operation->id,
                    'bill' => $operation->recipient->name,
                ]), ($transaction = Transactions::findOne(['id' => $this->transaction_id])) !== null ? [
                    'transaction' => [
                        'txn_id' => $transaction->txn_id,
                        'txn_type' => $transaction->txn_type,
                    ]
                ] : []
            ];
        }
    }
}
