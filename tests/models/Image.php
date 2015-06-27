<?php

namespace tests\models;

use yii\db\ActiveRecord;

/**
 * Class Image
 *
 * @property integer $id
 * @property string $name
 * @property integer $post_id
 *
 * @property Post $post
 */
class Image extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'image';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string'],

            [['post_id'], 'integer'],
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPost()
    {
        return $this->hasOne(Post::className(), ['id' => 'post_id']);
    }
}