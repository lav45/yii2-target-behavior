<?php
/**
 * @link https://github.com/LAV45/yii2-target-behavior
 * @copyright Copyright (c) 2015 LAV45!
 * @license http://opensource.org/licenses/BSD-3-Clause
 */

namespace lav45\behavior;

use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * @author Alexey Loban <lav451@gmail.com>
 *
 * @property ActiveRecord $owner
 */
class Target extends Behavior
{
    /**
     * @var string
     */
    public $targetAttribute;
    /**
     * @var string
     */
    public $targetRelation = 'tags';
    /**
     * @var string
     */
    public $targetRelationAttribute = 'name';
    /**
     * @var string|boolean
     */
    public $delimiter = ',';
    /**
     * @var bool
     */
    public $deleteOldTarget = true;
    /**
     * @var \Closure
     */
    public $beforeUnlink;
    /**
     * @var \Closure
     */
    public $afterUnlink;
    /**
     * @var \Closure
     */
    public $beforeLink;
    /**
     * @var \Closure
     */
    public $afterLink;
    /**
     * @var \Closure
     */
    public $getItem;
    /**
     * @var array
     */
    private $_attributeValue;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    /**
     * @param bool $asArray
     * @return array|string
     */
    public function getAttributeValue($asArray)
    {
        if (!$this->isChangeAttribute()) {
            $items = $this->owner->getIsNewRecord() ? [] : array_keys($this->getOldTarget());
        } else {
            $items = $this->_attributeValue;
            $items = array_map('trim', $items);
            $items = array_unique($items);
            $items = array_filter($items);
        }

        return $asArray ? $items : implode($this->delimiter, $items);
    }

    /**
     * @param string|array $value
     */
    public function setAttributeValue($value)
    {
        if (is_array($value)) {
            $this->_attributeValue = $value;
        } elseif (is_string($value)) {
            $this->_attributeValue = explode($this->delimiter, $value);
        }
    }

    public function afterSave()
    {
        if (!$this->isChangeAttribute()) {
            return;
        }

        $old = $this->getOldTarget();
        $new = array_flip($this->getAttributeValue(true));

        $update = array_intersect_key($old, $new);
        $delete = array_diff_key($old, $update);
        $create = array_diff_key($new, $update);

        /** @var ActiveRecord $class */
        $class = $this->getRelation()->modelClass;

        foreach ($create as $name => $key) {
            $item = $this->getItem($name, $class);
            $this->link($item);
        }

        foreach ($delete as $item) {
            $this->unlink($item);
        }

        if ($this->afterUnlink !== null && !empty($update)) {
            foreach ($update as $item) {
                $this->callUserFunction($this->afterUnlink, $item);
            }
        }
    }

    public function beforeDelete()
    {
        $this->owner->unlinkAll($this->targetRelation, $this->deleteOldTarget);
    }

    /**
     * @return ActiveRecord[]
     */
    protected function getOldTarget()
    {
        return ArrayHelper::index($this->owner->{$this->targetRelation}, $this->targetRelationAttribute);
    }

    /**
     * @return \yii\db\ActiveQuery|\yii\db\ActiveQueryInterface
     */
    protected function getRelation()
    {
        return $this->owner->getRelation($this->targetRelation);
    }

    /**
     * @return bool
     */
    protected function hasManyToMany()
    {
        return $this->getRelation()->via !== null;
    }

    /**
     * @return bool
     */
    protected function isChangeAttribute()
    {
        return $this->_attributeValue !== null;
    }

    /**
     * @param string $name
     * @param ActiveRecord $class
     * @return ActiveRecord
     */
    protected function getItem($name, $class)
    {
        if ($this->getItem instanceof \Closure) {
            return call_user_func($this->getItem, $name, $class);
        } else {
            $condition = [$this->targetRelationAttribute => $name];
            return $class::findOne($condition) ?: new $class($condition);
        }
    }

    /**
     * @param $item ActiveRecord
     */
    protected function link($item)
    {
        $this->callUserFunction($this->beforeLink, $item);
        $this->hasManyToMany() && $item->save(false);
        $this->owner->link($this->targetRelation, $item);
        $this->callUserFunction($this->afterLink, $item);
    }

    /**
     * @param $item ActiveRecord
     */
    protected function unlink($item)
    {
        $this->callUserFunction($this->beforeUnlink, $item);
        $this->owner->unlink($this->targetRelation, $item, $this->deleteOldTarget);
        $this->callUserFunction($this->afterUnlink, $item);
    }

    /**
     * @param \Closure|array $function
     * @param $parameter
     */
    private function callUserFunction($function, $parameter)
    {
        if ($function instanceof \Closure || is_array($function)) {
            call_user_func($function, $parameter);
        }
    }

    /**
     * @param string $name
     * @return bool
     */
    protected function isAttribute($name)
    {
        return $name === $this->targetAttribute;
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function canGetProperty($name, $checkVars = true)
    {
        return $this->isAttribute($name) ?: parent::canGetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     * @return bool
     */
    public function canSetProperty($name, $checkVars = true)
    {
        return $this->isAttribute($name) ?: parent::canSetProperty($name, $checkVars);
    }

    /**
     * @inheritdoc
     */
    public function __get($name)
    {
        if ($this->isAttribute($name)) {
            return $this->getAttributeValue($this->delimiter === false);
        } else {
            return parent::__get($name);
        }
    }

    /**
     * @inheritdoc
     */
    public function __set($name, $value)
    {
        if ($this->isAttribute($name)) {
            $this->setAttributeValue($value);
        } else {
            parent::__set($name, $value);
        }
    }
}