<?php

namespace tests;

use Yii;
use tests\models\Post;
use tests\models\Tag;
use lav45\behavior\Target;

/**
 * TargetBehaviorTest
 */
class TargetBehaviorTest extends DatabaseTestCase
{
    public function testFindPosts()
    {
        $posts = Post::find()
            ->with('tags')
            ->asArray()
            ->all();

        $this->assertEquals(require(__DIR__ . '/data/test-find-posts.php'), $posts);
    }

    public function testFindPost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->attachBehavior('target', [
            'class' => Target::className(),
            'targetAttribute' => 'tagNames',
            'delimiter' => ', ',
        ]);

        $this->assertEquals('tag 2, tag 3, tag 4', $post->tagNames);
    }

    public function testCreatePostSetTags()
    {
        $post = new Post();
        $tags = new Target([
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
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

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

    public function testCreatePostSetTagValuesAsArray()
    {
        $post = new Post();
        $tags = new Target([
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
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

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
        $tags = new Target([
            'targetAttribute' => 'tagNames',
        ]);
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

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
        $tags = new Target([
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
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

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
        $tags = new Target([
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
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

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
        $tags = new Target([
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
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

        $this->assertEquals(1, $post->delete());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-delete-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testValidatePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $tags = new Target([
            'targetAttribute' => 'tagNames',
            'beforeLink' => function($tag) {
                /** @var Tag $tag */
                $tag->frequency = 'aaa';
            },
        ]);
        $post->attachBehavior('target', $tags);
        $tags->initEvent();

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
