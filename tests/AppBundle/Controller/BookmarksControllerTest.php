<?php

namespace AppBundle\Tests\Controller;

use AppBundle\Entity\Bookmark;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BookmarksControllerTest extends WebTestCase
{
    public function testGetBookmarks()
    {
        $client = self::createClient();
        $em = $client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
        ;

        $bookmarks = $em
            ->getRepository(Bookmark::class)
            ->findBy([], ['created_at' => 'DESC'], 10)
        ;

        $client->request('GET', '/bookmarks');

        //Status code assert
        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        //Response type assert
        $this->assertJson(
            $client->getResponse()->getContent()
        );

        $content = json_decode(
            $client->getResponse()->getContent(),
            1
        )['bookmarks'];

        //Assert count of bookmarks
        $this->assertCount(
            sizeof($bookmarks),
            $content
        );

        if (sizeof($content) > 0) {
            //If count bookmarks more than 0, check first row
            $this->assertEquals(
                $bookmarks[0]->getId(),
                $content[0]['id']
            );
        }
    }

    public function testGetBookmark()
    {
        $client = self::createClient();
        $em = $client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
        ;

        $client->request('GET', '/bookmarks/hello-woorld');

        $this->assertEquals(
            404,
            $client->getResponse()->getStatusCode()
        );

        $bookmark = $em
            ->getRepository(Bookmark::class)
            ->findOneBy([])
        ;

        if (!$bookmark) {
            return;
        }

        $client->request('GET', '/bookmarks/' . $bookmark->getSlug());

        $this->assertEquals(
            200,
            $client->getResponse()->getStatusCode()
        );

        $this->assertJson(
            $client->getResponse()->getContent()
        );
    }

    public function testPostBookmark()
    {
        $client = self::createClient();
        $em = $client
            ->getContainer()
            ->get('doctrine.orm.entity_manager')
        ;

        $client->request('POST', '/bookmarks', []);
        $this->assertEquals(
            406,
            $client->getResponse()->getStatusCode()
        );

        $bookmark = [
            'url' => 'https://www.instagram.com/'
        ];

        $bookmarkObject = $em
            ->getRepository(Bookmark::class)
            ->findOneBy($bookmark)
        ;

        $client->request('POST', '/bookmarks', $bookmark);

        if (!$bookmarkObject) {
            $this->assertEquals(
                201,
                $client->getResponse()->getStatusCode()
            );
        } else {
            $this->assertEquals(
                200,
                $client->getResponse()->getStatusCode()
            );
        }

        $content = json_decode(
            $client->getResponse()->getContent(),
            1
        )['bookmark'];

        $this->assertEquals(
            $bookmark['url'],
            $content['url']
        );
    }
}
