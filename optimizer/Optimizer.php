<?php
/**
 * This file is part of the balimotion project.
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 *
 * @copyright balimotion (c) 2019
 * @author Eugene Zakirov (worstinme) <box@flyleaf.su>
 */

namespace app\components;

use app\components\log\order\OrderUpdated;
use app\models\Orders;
use app\models\Vehicles;
use Closure;
use Yii;
use yii\base\InvalidArgumentException;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use yii\web\UnprocessableEntityHttpException;

/**
 * Class Optimizer
 * @package app\components
 *
 * @property ActiveQuery $query
 * @property integer $min
 * @property integer $max
 * @property integer $model_id
 * @property Array $transport
 */
class Optimizer extends \yii\base\Component
{
    public $model_id;

    private $min;
    private $max;

    private $_orders;
    private $_transport;

    public function init()
    {
        if ($this->model_id === null) {
            throw new InvalidArgumentException(Yii::t('app','OPTIMIZATOR_MODEL_ID_REQUIRED'));
        }

        $this->max = $this->query->max('end_at');
        $min = $this->query->min('start_at');
        $today = strtotime('yesterday 0 hours 0 minutes 0 seconds');
        $this->min = $min < $today ? $today : $min;

        parent::init();
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    protected function getQuery()
    {
        return Orders::find()
            ->where([
                'and',
                ['model_id' => $this->model_id],
                ['vehicle_locked' => 0],
                ['!=', 'state', Orders::STATE_FAILED],
                ['<', 'state', Orders::STATE_WAITING_FOR_DELIVERY],
            ])->orderBy('state DESC, start_at DESC');
    }

    /**
     * @return array
     */
    protected function getTransport()
    {
        if ($this->_transport === null) {
            $this->_transport = [];
            foreach (Vehicles::find()
                         ->with([
                             'orders' => function ($query) {
                                 // выбрать все заказы, закрепленные и не допустимые к перемещению на эти даты/* @var $query ActiveQuery */;
                                 return $query
                                     ->where([
                                         'and',
                                         ['<=', 'start_at', $this->max],
                                         ['>=', 'end_at', $this->min],
                                         ['!=', 'state', Orders::STATE_FAILED],
                                         [
                                             'or',
                                             ['vehicle_locked' => 1],
                                             ['>=', 'state', 4],
                                         ]
                                     ])
                                     ->orderBy('start_at');
                             }
                         ])
                         ->with([
                             'tasks' => function ($query) {
                                 // выбрать все задачи блокируещие транспорт на эти даты/* @var $query ActiveQuery */;
                                 return $query
                                     ->where([
                                         'and',
                                         ['<=', 'start_at', $this->max + 86399],
                                         // задача может начинаться в середине дня, а максимум отражает день в 00:00
                                         ['>=', 'end_at', $this->min],
                                         ['date_effect' => 1],
                                         ['is_active' => 1],
                                     ])
                                     ->orderBy('start_at');
                             }
                         ])
                         ->where(['model_id' => $this->model_id])
                         ->andWhere([
                             'and',
                             ['or', ['operation_end_date' => null], ['>=', 'operation_end_date', $this->min]],
                             ['<=', 'operation_start_date', $this->max]
                         ])
                         ->orderBy('mileage ASC')
                         ->all() as $vehicle) {

                $this->_transport[$vehicle->id] = [];

                foreach ($vehicle->orders as $order) {
                    $this->_transport[$vehicle->id][] = [
                        'start_at' => $order->start_at, 'end_at' => $order->end_at, 'order_id' => $order->id
                    ];
                }
                foreach ($vehicle->tasks as $task) {
                    $this->_transport[$vehicle->id][] = [
                        'start_at' => $task->start_at, 'end_at' => $task->end_at, 'task_id' => $task->id
                    ];
                }
                if ($vehicle->operation_start_date > $this->min) {
                    $this->_transport[$vehicle->id][] = [
                        'start_at' => $this->min, 'end_at' => strtotime('-1 day',$vehicle->operation_start_date)
                    ];
                }
                if ($vehicle->operation_end_date && $vehicle->operation_end_date < $this->max) {
                    $this->_transport[$vehicle->id][] = [
                        'start_at' => strtotime('+1 day',$vehicle->operation_end_date), 'end_at' => $this->max
                    ];
                }
            }

        }


        return $this->_transport;
    }

    /**
     * @return array
     */
    protected function getOrders()
    {
        if ($this->_orders === null) {
            $this->_orders = $this->query->select([
                'id as order_id', 'start_at', 'end_at', 'state', 'is_prepaid', 'vehicle_id'
            ])
                ->andWhere(['<=', 'start_at', $this->max])
                ->andWhere(['>=', 'end_at', $this->min])
                ->asArray()
                ->all();
        }
        return $this->_orders;
    }


    public function optimize()
    {
        Yii::beginProfile('optimize', self::class);
        try {
            $optimize = $this->profiledOptimize();
            Yii::endProfile('optimize', self::class);
            $result = Yii::getLogger()->getProfiling([self::class]);
            Yii::info($result, 'profiling');
        } catch (\Exception $e) {
            Yii::endProfile('optimize', self::class);
            $result = Yii::getLogger()->getProfiling([self::class]);
            Yii::info($result, 'profiling');
            throw $e;
        }
        return $optimize;
    }

    protected function profiledOptimize()
    {

        $orders = $this->getOrders();
        // разделим заказы на две группы
        $other_orders = $this->split($orders, function ($order) {
            // обязательный к размещению заказ
            return $order['is_prepaid'] || $order['state'] >= Orders::STATE_WAITING_FOR_PREPAY;
        });
        // переберем все варианты размещений обязательных заказов и разных комбинаций необязательных
        // начиная с самых больших по сумме дней необязательных комбинаций
        foreach ($this->combinations($other_orders) as $key => $combination) {
            //попробуем разместить данную комбинацию заказов в расписание
            if (($placed = $this->stack(array_merge($orders, $combination))) !== false) {
                // похоже, мы нашли успешный вариант
                // найдем заказы не вошедшие в "подборку"
                $failed = array_filter($other_orders, function ($order) use ($placed) {
                    return !isset($placed[$order['order_id']]);
                });
                //сохраним результат
                return $this->save($placed, $failed);
            }
        }
        throw new UnprocessableEntityHttpException('Ошибка оптимизации размещения оплаченных заказов и задач. Проверьте корректность заказов и задач блокирующих размещение.');
    }

    public function experiment($min, $max, $quantity = 0)
    {
        Yii::beginProfile('experiment', self::class);
        try {
            $experiment = $this->profiledExperiment($min, $max, $quantity);
            Yii::endProfile('experiment', self::class);
            $result = Yii::getLogger()->getProfiling([self::class]);
            Yii::info($result, 'profiling');
        } catch (\Exception $e) {
            Yii::endProfile('experiment', self::class);
            $result = Yii::getLogger()->getProfiling([self::class]);
            Yii::info($result, 'profiling');
            throw $e;
        }
        return $experiment;
    }

    protected function profiledExperiment($min, $max, $quantity = 0)
    {

        $this->setPeriod($min, $max);

        $orders = $this->getOrders();
        // разделим заказы на две группы
        $other_orders = $this->split($orders, function ($order) {
            // обязательный к размещению заказ
            return $order['is_prepaid'] || $order['state'] >= Orders::STATE_WAITING_FOR_PREPAY;
        });

        $experimental = [];
        $experiment_max = 0;
        $zero_experiment_loss = 0;
        $losses = [0 => 0];

        REPEAT_EXPERIMENT:

        if (($placed = $this->stack(array_merge($orders, $experimental))) !== false) {
            $experiment_loss = 0;
            foreach ($this->combinations($other_orders) as $key => $combination) {
                //попробуем разместить данную комбинацию заказов в расписание
                if (($placed = $this->stack(array_merge($orders, $experimental, $combination))) !== false) {
                    $experiment_loss = $this->getLoss($other_orders, $placed);
                    break;
                }
            }
            if ($experiment_max === 0) {
                $zero_experiment_loss = $experiment_loss;
            } //запишем loss при нулевом эксперименте
            $losses[$experiment_max] = round(($zero_experiment_loss - $experiment_loss + $experiment_max * ($max - $min)) / 86400);

            if ($quantity > 0 && $experiment_max == $quantity) {
                goto FINISH;
            }

            $experiment_max++;
            $experimental[] = [
                'order_id' => "experiment-$experiment_max", 'start_at' => $min, 'end_at' => $max,
                'state' => Orders::STATE_READY, 'is_prepaid' => 1
            ];

            goto REPEAT_EXPERIMENT; // повторим =)

        } elseif ($experiment_max > 0) {
            $experiment_max--;
            unset($experimental);
        }

        FINISH:

        $max_clean = 0;
        $max_clean_value = 0;

        foreach ($losses as $key => $value) {
            if ($value > 0 && $value > $max_clean_value) {
                $max_clean = $key;
                $max_clean_value = $value;
            }
        }

        return [
            'max' => $experiment_max,
            'value' => $losses[$experiment_max],
            'losses' => array_keys($losses),
            'max_clean' => $max_clean,
            'max_clean_value' => $max_clean_value
        ];
    }

    public function experimentExtend(Orders $order, $end_at = null)
    {

        if ($end_at === null) {
            $end_at = strtotime('+ 30 days', $order->end_at);
        }

        $this->setPeriod($order->start_at, $end_at);

        $orders = $this->getOrders();
        $testOrder = null;

        foreach ($orders as $key => $o) {
            if ($o['order_id'] == $order->id) {
                $testOrder = &$orders[$key];
                break;
            }
        }

        $transport = $this->getTransport();

        if ($testOrder === null && $order->vehicle_id) {
            if (isset($transport[$order->vehicle_id])) {
                foreach ($transport[$order->vehicle_id] as $key => $value) {
                    if (!empty($value['order_id']) && $value['order_id'] == $order->id) {
                        $testOrder = &$transport[$order->vehicle_id][$key];
                    }
                }
            }
        }

        $result = new OptimizerExperimentExtendResult(['order' => $order]);

        if ($testOrder === null) {
            return $result;
        }

        $other_orders = $this->split($orders, function ($order) use ($testOrder) {
            return $order['is_prepaid'] || $order['state'] >= Orders::STATE_WAITING_FOR_PREPAY || $order['order_id'] == $testOrder['order_id'];
        });

        foreach ($this->combinations($other_orders) as $combination) {
            if (($placed = $this->stack(array_merge($orders, $combination), $transport)) !== false) {
                $result->base_loss = $this->getLoss($other_orders, $placed);
                break;
            }
        }

        $testOrder['end_at'] = $testOrder['start_at'];

        while ($testOrder['end_at'] < $end_at) {
            $testOrder['end_at'] = strtotime('+ 1 day', $testOrder['end_at']);
            foreach ($this->combinations($other_orders) as $combination) {
                if (($placed = $this->stack(array_merge($orders, $combination), $transport)) !== false) {
                    $combination_loss = $this->getLoss($other_orders, $placed);
                    $result->increment($combination_loss);
                    continue 2;
                }
            }
            break;
        }

        return $result;
    }

    protected function split(&$orders, Closure $callback)
    {
        $a = [];
        foreach ($orders as $key => $order) {
            if (!$callback($order)) {
                $a[] = $order;
                unset($orders[$key]);
            }
        }
        return $a;
    }

    protected function combinations($orders)
    {
        if (is_array($orders)) {

            $combinations = [];

            // TODO: решить проблему переполнения памяти при большом количестве заявок убрав этот лимит
            if (count($orders) > 15) {
                $orders = array_chunk($orders,15)[0];
            }

            for ($k = 2 ** count($orders); $k > 0; $k--) {
                $combinations[$k] = 0;
                foreach($orders as $key => &$order) {
                    if ($k & (1 << $key)) {
                        $combinations[$k] += $order['end_at'] - $order['start_at'];
                    }
                }
            }

            arsort($combinations); //сортируем по общей длине заказов в комбинации

            foreach($combinations as $k => $length) {
                // возвращаем список конкретных заказов
                yield array_filter($orders, function ($key) use ($k) {
                    return $k & (1 << $key);
                }, ARRAY_FILTER_USE_KEY);
            }

        }
    }

    protected function stack($orders, $transport = null)
    {

        $transport = $transport === null ? $this->getTransport() : $transport;

        ArrayHelper::multisort($orders, [
            'start_at', function ($model) {
                return $model['start_at'] - $model['end_at'];
            }
        ]);

        $succeed = [];

        foreach ($orders as $order) {

            $selected_transport_id = null;
            $baseWindow = $this->max - $this->min;

            foreach ($transport as $transport_id => $days) {

                $leftBorder = $this->min;
                $rightBorder = $this->max;

                foreach ($days as $day) {
                    if ($order['start_at'] <= $day['end_at'] && $order['end_at'] >= $day['start_at']) {
                        // нельзя разместить заказ в этот транспорт (дни заняты)
                        // выходим на 2 шага назад чтобы проверить следующий транспорт
                        continue 2;
                    }
                    if ($day['end_at'] < $order['start_at'] && $day['end_at'] > $leftBorder) {
                        $leftBorder = $day['end_at'];
                    }
                    if ($day['start_at'] > $order['end_at'] && $day['start_at'] < $rightBorder) {
                        $rightBorder = $day['start_at'];
                    }
                }

                if ($selected_transport_id === null || $rightBorder - $leftBorder < $baseWindow) {
                    // выберем этот транспорт только если он первый (с меньшим пробегом)  или он плотнее
                    $baseWindow = $rightBorder - $leftBorder;
                    $selected_transport_id = $transport_id;
                }

            }

            if ($selected_transport_id === null) {
                return false;
            } else {
                $succeed[$order['order_id']] = $selected_transport_id;
                $transport[$selected_transport_id][] = $order;
            }

        }

        return $succeed;

    }

    protected function getLoss($other_orders, $placed)
    {
        $failed_orders = array_filter($other_orders, function ($order) use ($placed) {
            return !isset($placed[$order['order_id']]);
        });
        return array_sum(ArrayHelper::getColumn($failed_orders, function ($order) {
            return $order['end_at'] - $order['start_at'];
        }));
    }

    protected function save($succeed, $failed)
    {
        // сохраним результаты
        foreach ($succeed as $order_id => $transport_id) {
            $vehicle_id = Yii::$app->db->createCommand('SELECT vehicle_id FROM orders WHERE id = :id',[
                ':id' => $order_id
            ])->queryScalar();
            Yii::$app->db->createCommand()->update('orders', [
                'vehicle_id' => $transport_id,
                'updated_at' => time(),
            ], ['id' => $order_id])->execute();
            Yii::$app->logger->add(new OrderUpdated([
                'order_id' => $order_id,
                'user_id' => 10,
                'changed_attributes' => ['vehicle_id' => $vehicle_id ?: null]
            ]));
        }

        foreach ($failed as $order) {
            Yii::$app->db->createCommand()->update('orders', [
                'vehicle_id' => null,
                'updated_at' => time(),
            ], ['id' => $order['order_id']])->execute();
            Yii::$app->logger->add(new OrderUpdated([
                'order_id' => $order['order_id'],
                'user_id' => 10,
                'changed_attributes' => ['vehicle_id' => $order['vehicle_id']]
            ]));
        }

        if (Yii::$app->id === 'console') {
            echo "Обновлены позиции ".count($succeed)." заказов;\n";
            echo "Конфликтных заказов: ".count($failed).PHP_EOL;
            echo implode(",",ArrayHelper::getColumn($failed,'order_id')).PHP_EOL;
        }
    }

    protected function setPeriod($min, $max)
    {
        if ($min < $this->min) {
            $this->min = $min;
        }
        if ($max > $this->max) {
            $this->max = $max;
        }
    }
}
