<?php

namespace tests;

use Yii;
use tests\models\Post;
use tests\models\Tag;
use tests\models\Image;
use lav45\behavior\Target;

/**
 * TargetBehaviorTest
 */
class TargetBehaviorTest extends DatabaseTestCase
{
    public function testFindPosts()
    {
        $posts = Post::find()
            ->with('tags', 'images')
            ->asArray()
            ->all();

        $this->assertEquals(require(__DIR__ . '/data/test-find-posts.php'), $posts);
    }

    public function testFindPost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
            'delimiter' => ', ',
        ]);
        $post->attachBehavior('target-images', [
            'class' => Target::className(),
            'targetRelation' => 'images',
            'targetAttribute' => 'imageNames',
            'delimiter' => false,
        ]);
        $post->init();

        $this->assertEquals('tag 2, tag 3, tag 4', $post->tagNames);
        $this->assertEquals(['img3.jpg', 'img4.jpg', 'img5.jpg'], $post->imageNames);
    }

    public function testCreatePostSetTags()
    {
        $post = new Post();
        /** @var Target $tags */
        $tags = $post->attachBehavior('target-tags', [
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
        ]);
        $post->init();

        $post->setAttributes([
            'title' => 'New post title',
            'body' => 'New post body',
            'tagNames' => 'tag 4, tag 5  , tag 5  , , tag 6',
        ]);

        $this->assertTrue($post->save());

        $this->assertEquals('tag 4, tag 5, tag 6', $post->tagNames);

        $tags->delimiter = false;
        $this->assertEquals(['tag 4', 'tag 5', 'tag 6'], $post->tagNames);

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testCreatePostSetImages()
    {
        $post = new Post();
        $post->attachBehavior('target-images', [
            'class' => Target::className(),
            'targetAttribute' => 'imageNames',
            'targetRelation' => 'images',
            'delimiter' => false,
            'getItem' => function ($name, $class) {
                return new $class(['name' => $name]);
            },
        ]);
        $post->init();

        $post->setAttributes([
            'title' => 'New post title',
            'body' => 'New post body',
            'imageNames' => ['img1.jpg', 'img2.jpg', 'img3.jpg'],
        ]);

        $this->assertTrue($post->save());

        $this->assertEquals(['img1.jpg', 'img2.jpg', 'img3.jpg'], $post->imageNames);

        $images = array_map(function($value) {
            /** @var Image $value */
            return $value->toArray();
        }, $post->images);

        $new_images = [
            [
                'id' => 6,
                'name' => 'img1.jpg',
                'post_id' => 4,
            ],
            [
                'id' => 7,
                'name' => 'img2.jpg',
                'post_id' => 4,
            ],
            [
                'id' => 8,
                'name' => 'img3.jpg',
                'post_id' => 4,
            ]
        ];

        $this->assertEquals($new_images, $images);
    }

    public function testCreatePostSetTagValuesAsArray()
    {
        $post = new Post();
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
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
        ]);
        $post->init();

        $post->title = 'New post title';
        $post->body = 'New post body';
        $post->tagNames = ['tag 4', 'tag 5', '', 'tag 6'];

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
        ]);
        $post->init();

        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePostSetTagValues()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
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
        ]);
        $post->init();

        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $post->tagNames = 'tag 3, tag 4, , tag 6';
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePostSetTagValuesAsArray()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
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
        ]);
        $post->init();

        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $post->tagNames = ['tag 3', 'tag 4', '', 'tag 6'];
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeletePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
            'afterUnlink' => function($tag) {
                /** @var Tag $tag */
                $tag->frequency--;
                if ($tag->frequency == 0) {
                    $tag->delete();
                } else {
                    $tag->update(false);
                }
            }
        ]);
        $post->attachBehavior('target-images', [
            'class' => Target::className(),
            'targetAttribute' => 'imageNames',
            'targetRelation' => 'images',
            'deleteOldTarget' => false,
        ]);
        $post->init();

        $this->assertEquals(1, $post->delete());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag', 'image']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-delete-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testValidatePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target-tags', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
            'beforeLink' => function($tag) {
                /** @var Tag $tag */
                $tag->frequency = 'aaa';
            },
        ]);
        $post->init();

        $post->tagNames = ['tag 3', 'tag 6', 'mega long tag name'];

        $this->assertEquals(false, $post->save());

        $this->assertEquals([
            'tagNames' => [
                '[tag 6] Frequency must be an integer.',
                '[mega long tag name] Name should contain at most 6 characters.',
                '[mega long tag name] Frequency must be an integer.',
            ]
        ], $post->getErrors());
    }
}
