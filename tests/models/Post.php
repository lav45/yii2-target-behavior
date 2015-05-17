<?php

namespace tests\models;

use yii\db\ActiveRecord;
use lav45\behavior\Target;

/**
 * Post
 *
 * @property integer $id
 * @property string $title
 * @property string $body
 *
 * @property string $tagNames
 *
 * @property Tag[] $tags
 */
class Post extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'post';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            [
                'class' => Target::className(),
                'targetAttribute' => 'tagNames',
                'delimiter' => ', ',
                'beforeLink' => function($tag) {
                    /** @var Tag $tag */
                    $tag->frequency++;
                },
                'afterUnlink' => function($tag) {
                    /** @var Tag $tag */
                    $tag->frequency--;
                    if ($tag->frequency == 0) {
                        $tag->delete();
                    } else {
                        $tag->update(false);
                    }
                }
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'body'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function transactions()
    {
        return [
            self::SCENARIO_DEFAULT => self::OP_ALL,
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTags()
    {
        return $this->hasMany(Tag::className(), ['id' => 'tag_id'])
            ->viaTable('post_tag', ['post_id' => 'id']);
    }
}
