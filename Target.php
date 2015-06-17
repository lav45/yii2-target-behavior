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
use yii\validators\Validator;

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
     * @var string|boolean If the value is false then $targetAttribute will contain an array
     */
    public $delimiter = ',';
    /**
     * @var bool
     */
    public $deleteOldTarget = true;
    /**
     * @var \Closure|array
     */
    public $beforeUnlink;
    /**
     * @var \Closure|array
     */
    public $afterUnlink;
    /**
     * @var \Closure|array
     */
    public $beforeLink;
    /**
     * @var \Closure|array
     */
    public $afterLink;
    /**
     * @var \Closure|array
     */
    public $onUpdate;
    /**
     * @var \Closure|array
     */
    public $getItem;
    /**
     * @var \Closure|array
     */
    public $getExtraColumns;
    /**
     * @var array
     */
    private $_attributeValue;
    /**
     * @var array
     */
    private $_change_items;

    /**
     * @inheritdoc
     */
    public function events()
    {
        return [
            ActiveRecord::EVENT_INIT => 'initEvent',
            ActiveRecord::EVENT_BEFORE_INSERT => 'beforeSave',
            ActiveRecord::EVENT_BEFORE_UPDATE => 'beforeSave',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_VALIDATE => 'afterValidate',
            ActiveRecord::EVENT_BEFORE_DELETE => 'beforeDelete',
        ];
    }

    public function initEvent()
    {
        if ($this->targetAttribute !== null) {
            $this->owner->validators[] = Validator::createValidator('safe', $this->owner, [$this->targetAttribute]);
        }
    }

    public function afterValidate()
    {
        /** @var ActiveRecord $class */
        $class = $this->getRelation()->modelClass;
        $attributes = array_keys($class::getTableSchema()->columns);

        if ($this->hasManyToMany() === false) {
            $ignore_columns = array_keys($this->getRelation()->link);
            $attributes = array_diff($attributes, $ignore_columns);
        }

        foreach ($this->getCreateItems() as $item) {
            $this->callUserFunction($this->beforeLink, $item);
            if ($item->validate($attributes) === false) {
                foreach($item->getErrors() as $errors) {
                    foreach($errors as $error) {
                        $error = "[{$item->{$this->targetRelationAttribute}}] $error";
                        $this->owner->addError($this->targetAttribute, $error);
                    }
                }
            }
        }
    }

    public function beforeSave()
    {
        if ($this->isChangeAttribute() === false) {
            $this->setAttributeValue($this->owner->{$this->targetAttribute});
        }

        foreach ($this->getDeleteItems() as $item) {
            $this->unlink($item);
        }

        if ($this->hasManyToMany() === false) {
            $isPrimaryKey = false;
            foreach ($this->getCreateItems() as $item) {
                if ($isPrimaryKey === false) {
                    $link = $this->getRelation()->link;
                    if (($isPrimaryKey = $item->isPrimaryKey(array_keys($link))) === false) {
                        return;
                    }
                }
                if ($item->getIsNewRecord()) {
                    $item->save(false);
                }
                foreach ($link as $pk => $fk) {
                    $this->owner->$fk = $item->$pk;
                }
            }
        }
    }

    /**
     * @param bool $asArray
     * @return array|string
     */
    public function getAttributeValue($asArray)
    {
        if ($this->isChangeAttribute() === false) {
            $items = $this->owner->getIsNewRecord() ? [] : array_keys($this->getOldTarget());
        } else {
            $items = $this->_attributeValue;
            $items = array_keys(array_flip($items));
            $items = array_map('trim', $items);
            $items = array_filter($items);
            $items = array_values($items);
        }

        return $asArray ? $items : implode($this->delimiter, $items);
    }

    /**
     * @param string|array $value
     */
    public function setAttributeValue($value)
    {
        if (empty($value)) {
            $this->_attributeValue = [];
        } elseif (is_array($value)) {
            $this->_attributeValue = $value;
        } elseif (is_string($value)) {
            $this->_attributeValue = explode($this->delimiter, $value);
        }
    }

    /**
     * @return array
     */
    protected function getChangeItems()
    {
        if ($this->_change_items !== null) {
            return $this->_change_items;
        }

        $old = $this->getOldTarget();
        $new = array_flip($this->getAttributeValue(true));

        $update = array_intersect_key($old, $new);
        $delete = array_diff_key($old, $update);
        $create = array_diff_key($new, $update);

        foreach ($create as $name => $key) {
            $create[$name] = $this->getItem($name);
        }

        $this->_change_items = [$create, $update, $delete];

        return $this->_change_items;
    }

    /**
     * @return ActiveRecord[]
     */
    protected function getCreateItems()
    {
        return $this->getChangeItems()[0];
    }

    /**
     * @return ActiveRecord[]
     */
    protected function getUpdateItems()
    {
        return $this->getChangeItems()[1];
    }

    /**
     * @return ActiveRecord[]
     */
    protected function getDeleteItems()
    {
        return $this->getChangeItems()[2];
    }

    public function afterSave()
    {
        foreach ($this->getCreateItems() as $item) {
            $this->link($item);
        }
        foreach ($this->getUpdateItems() as $item) {
            $this->callUserFunction($this->onUpdate, $item);
        }
    }

    public function beforeDelete()
    {
        foreach ($this->getOldTarget() as $item) {
            $this->unlink($item);
        }
    }

    /**
     * @return ActiveRecord[]
     */
    protected function getOldTarget()
    {
        $model = $this->owner->{$this->targetRelation};
        if ($this->hasManyToMany() == false) {
            $model = empty($model) ? [] : [$model];
        }
        return ArrayHelper::index($model, $this->targetRelationAttribute);
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
     * @return ActiveRecord
     */
    protected function getItem($name)
    {
        /** @var ActiveRecord $class */
        $class = $this->getRelation()->modelClass;

        if ($this->getItem !== null) {
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
        if ($this->hasManyToMany()) {
            if ($item->getIsNewRecord()) {
                $item->save(false);
            }
            $extraColumns = $this->callUserFunction($this->getExtraColumns, $item, []);
            $this->owner->link($this->targetRelation, $item, $extraColumns);
        } else {
            $link = $this->getRelation()->link;
            if ($item->isPrimaryKey(array_keys($link)) === false) {
                $this->owner->link($this->targetRelation, $item);
            }
        }
        $this->callUserFunction($this->afterLink, $item);
    }


    /**
     * @param $item ActiveRecord
     */
    protected function unlink($item)
    {
        $this->callUserFunction($this->beforeUnlink, $item);
        $link = $this->getRelation()->link;
        if ($item->isPrimaryKey(array_keys($link)) === false) {
            $this->owner->unlink($this->targetRelation, $item, $this->deleteOldTarget);
        }
        $this->callUserFunction($this->afterUnlink, $item);
    }

    /**
     * @param \Closure|array $function
     * @param mixed $params
     * @param null $default
     * @return mixed|null
     */
    private function callUserFunction($function, $params, $default = null)
    {
        return $function !== null ? call_user_func($function, $params) : $default;
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
