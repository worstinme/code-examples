<?php


namespace app\components\log;

use Generator;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * Trait UpdatedLogTrait
 * @package app\components\log
 *
 * @property ActiveRecord $model;
 * @property string $name;
 */
trait UpdatedLogTrait
{
    /** @var int */
    public $model_id;
    /** @var array */
    public $changed_attributes = [];

    private $_model;

    abstract protected function attributes(): array;

    abstract protected function modelClass(): string;

    /**
     * @return Generator
     */
    protected function getMessage(): Generator
    {
        if ($this->model !== null) {
            foreach ($this->changed_attributes as $attribute => $value) {
                if ($this->isAttributeObserved($attribute) && $this->isModelAttributeChanged($attribute, $value)) {
                    yield self::t('{name} - Изменено «{attribute}»: <strike>{from}</strike> >> {to}', [
                        'name' => $this->name,
                        'attribute' => $this->model->getAttributeLabel($attribute),
                        'from' => $this->getAttributeValue($attribute, $value),
                        'to' => $this->getAttributeValue($attribute, ArrayHelper::getValue($this->model, $attribute)),
                    ]);
                }
            }
        }
    }

    protected function getName(): string
    {
        return '';
    }

    /**
     * @param $attribute
     * @return bool
     */
    protected function isAttributeObserved($attribute): bool
    {
        foreach ($this->attributes() as $key => $value) {
            if (is_int($key)) {
                if ($value === $attribute) {
                    return true;
                }
            } elseif ($key === $attribute) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $attribute
     * @param $value
     * @return mixed
     */
    protected function getAttributeValue($attribute, $value)
    {
        $expression = ArrayHelper::getValue($this->attributes(), $attribute);
        if (!empty($expression) && is_callable($expression)) {
            return $expression($value);
        }
        return $value;
    }

    /**
     * @return ActiveRecord
     * @throws LoggerException
     */
    protected function getModel(): ActiveRecord
    {
        if (($this->_model === null) && ($this->_model = $this->modelClass()::findOne($this->getModelId())) === null) {
            throw new LoggerException('Модель я записи логов обновления не найдена');
        }
        return $this->_model;
    }

    /**
     * @return int
     */
    protected function getModelId(): int
    {
        return $this->model_id;
    }

    /**
     * @param $attribute
     * @param $value
     * @return bool
     */
    protected function isModelAttributeChanged($attribute, $value): bool
    {
        return $value != ArrayHelper::getValue($this->model, $attribute);
    }
}
