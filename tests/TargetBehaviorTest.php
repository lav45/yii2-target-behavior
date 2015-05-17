<?php

namespace tests;

use Yii;
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

    public function testCreatePostSetTags()
    {
        $post = new Post();
        $post->setAttributes([
            'title' => 'New post title',
            'body' => 'New post body',
            'tagNames' => 'tag 4, tag 5  , tag 5  , , tag 6',
        ]);

        $this->assertTrue($post->save());

        $this->assertEquals('tag 4, tag 5, tag 6', $post->tagNames);

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
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

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
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

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
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

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
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

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-update-post-set-tag-values.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }

    public function testDeletePost()
    {
        /** @var Post $post */
        $post = Post::findOne(2);
        $this->assertEquals(1, $post->delete());

        $dataSet = $this->getConnection()->createDataSet(['post', 'tag', 'post_tag']);
        $expectedDataSet = $this->createFlatXMLDataSet(__DIR__ . '/data/test-delete-post.xml');
        $this->assertDataSetsEqual($expectedDataSet, $dataSet);
    }
}
