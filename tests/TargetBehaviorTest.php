<?php

namespace tests;

use Yii;
use yii\db\Connection;
use tests\models\Post;

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
        $this->assertEquals('tag 2, tag 3, tag 4', $post->tagNames);
    }

    public function testCreatePost()
    {
        $post = new Post([
            'title' => 'New post title',
            'body' => 'New post body',
        ]);

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testCreatePostSetTagValues()
    {
        $post = new Post([
            'title' => 'New post title',
            'body' => 'New post body',
            'tagNames' => 'tag 4, tag 5  , tag 5  , , tag 6',
        ]);

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testCreatePostSetTagValuesAsArray()
    {
        $post = new Post([
            'title' => 'New post title',
            'body' => 'New post body',
            'tagNames' => ['tag 4', 'tag 5', '', 'tag 6'],
        ]);

        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-create-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePostSetTagValues()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $post->tagNames = 'tag 3, tag 4, , tag 6';
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testUpdatePostSetTagValuesAsArray()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $post->title = 'Updated post title 2';
        $post->body = 'Updated post body 2';
        $post->tagNames = ['tag 3', 'tag 4', '', 'tag 6'];
        $this->assertTrue($post->save());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeletePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $this->assertEquals(1, $post->delete());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag_assn']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-delete-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    /**
     * @inheritdoc
     */
    public static function setUpBeforeClass()
    {
        try {
            Yii::$app->set('db', [
                'class' => Connection::className(),
                'dsn' => 'sqlite::memory:',
            ]);

            Yii::$app->getDb()->open();
            $lines = explode(';', file_get_contents(__DIR__ . '/migrations/sqlite.sql'));

            foreach ($lines as $line) {
                if (trim($line) !== '') {
                    Yii::$app->getDb()->pdo->exec($line);
                }
            }
        } catch (\Exception $e) {
            Yii::$app->clear('db');
        }
    }
}
