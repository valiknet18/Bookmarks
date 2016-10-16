<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Bookmark;
use AppBundle\Entity\Comment;
use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CommentsControllerTest extends WebTestCase
{
    /**
     * @param EntityManager $em
     * @return Bookmark
     */
    private function getFirstBookmark(EntityManager $em)
    {
        return $em
            ->getRepository(Bookmark::class)
            ->findOneBy([])
        ;
    }

    /**
     * @param EntityManager $em
     * @param Bookmark $bookmark
     * @return Comment
     */
    private function getFirstCommentByBookmark(EntityManager $em, Bookmark $bookmark)
    {
        $results = $em
            ->getRepository(Comment::class)
            ->createQueryBuilder('c')
            ->where('c.bookmark = :bookmark')
            ->AndWhere('c.created_at <= :now AND c.created_at >= :ago')
            ->setParameters([
                'bookmark' => $bookmark,
                'now' => (new \DateTime()),
                'ago' => (new \DateTime())->modify('-1 hour')
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getArrayResult()
        ;

        $result = (sizeof($results) > 0)?$results[0]:null;

        if (!$result) {
            return null;
        }

        $comment = new Comment();
        $comment->setId($result['id']);
        $comment->setText($result['text']);
        $comment->setIp($result['ip']);
        $comment->setCreatedAt($result['created_at']);

        return $comment;
    }

    public function testPostComment()
    {
        $client = self::createClient();

        $em = $client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
        ;

        $bookmark = $this->getFirstBookmark($em);

        if (!$bookmark) {
            return;
        }

        $text = 'Hello world';

        $client->request('POST', '/bookmarks/' . $bookmark->getId() . '/comments', [
            'text' => $text,
        ]);

        $this->assertEquals(
            201,
            $client->getResponse()->getStatusCode()
        );

        $content = json_decode($client->getResponse()->getContent(), 1)['comment'];

        $this->assertEquals(
            $text,
            $content['text']
        );
    }

    /**
     *
     */
    public function testPutComment()
    {
        $client = self::createClient();
        $em = $client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
        ;

        $bookmark = $this->getFirstBookmark($em);

        if (!$bookmark) {
            return;
        }

        $comment = $this->getFirstCommentByBookmark($em, $bookmark);

        if (!$comment) {
            return;
        }

        $text = 'New text';

        $client->request('PUT', '/bookmarks/' . $bookmark->getId() . '/comments/' . $comment->getId(), [
            'text' => $text,
        ], [], [
            'REMOTE_ADDR' => '122.22.22.22',
        ]);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );

        $client->request('PUT', '/bookmarks/' . $bookmark->getId() . '/comments/' . $comment->getId(), [
            'text' => $text,
        ], [], [
            'REMOTE_ADDR' => $comment->getIp(),
        ]);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $content = json_decode($client->getResponse()->getContent(), 1)['comment'];

        $this->assertEquals(
            $text,
            $content['text']
        );
    }

    public function testDeleteComment()
    {
        $client = self::createClient();
        $em = $client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
        ;

        $bookmark = $this->getFirstBookmark($em);

        if (!$bookmark) {
            return;
        }

        $comment = $this->getFirstCommentByBookmark($em, $bookmark);

        if (!$comment) {
            return;
        }

        $client->request('DELETE', '/bookmarks/' . $bookmark->getId() . '/comments/' . $comment->getId(), [], [], [
            'REMOTE_ADDR' => '122.22.22.22',
        ]);

        $this->assertEquals(
            403,
            $client->getResponse()->getStatusCode()
        );

        $client->request('DELETE', '/bookmarks/' . $bookmark->getId() . '/comments/' . $comment->getId(), [], [], [
            'REMOTE_ADDR' => $comment->getIp(),
        ]);

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $newLastComment = $this->getFirstCommentByBookmark($em, $bookmark);

        $this->assertNotEquals(
            $comment,
            $newLastComment
        );
    }
}
