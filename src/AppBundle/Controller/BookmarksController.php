<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bookmark;
use AppBundle\Entity\Comment;
use Doctrine\DBAL\Exception\DriverException;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class BookmarksController
 * @package AppBundle\Controller
 */
class BookmarksController extends FOSRestController
{
    /**
     * @Rest\View
     */
    public function getBookmarksAction(Request $request)
    {
        $bookmarks = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository(Bookmark::class)
            ->findBy([], ['created_at' => 'DESC'], 10)
        ;

        return $this->view([
            'bookmarks' => $bookmarks
        ]);
    }

    /**
     * @param Request $request
     * @param string $slug
     * @return Response
     *
     * @Rest\View
     */
    public function getBookmarkAction(Request $request, $slug)
    {
        $bookmark = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository(Bookmark::class)
            ->findOneBy(['slug' => $slug])
        ;

        if (!$bookmark) {
            return $this->view([], 404);
        }

        return $this->view([
            'bookmark' => $bookmark,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     *
     * @Rest\View
     */
    public function postBookmarkAction(Request $request)
    {
        //Get entity manager
        $em = $this->getDoctrine()->getManager();

        $bookmark = new Bookmark();
        $bookmark->setUrl($request->request->get('url'));

        $errors = $this->get('validator')->validate($bookmark);

        //Bookmark validation
        if (sizeof($errors) > 0) {
            return $this->view([
                'errors' => $errors,
            ], 406);
        }

        $em->persist($bookmark);

        //If bookmark with current url already exists, return bookmark
        try {
            $em->flush();
        } catch (DriverException $ex) {
            $bookmark = $em
                ->getRepository(Bookmark::class)
                ->findOneBy(['url' => $bookmark->getUrl()])
            ;

            return $this->view([
                'bookmark' => $bookmark,
            ]);
        }

        //Return created bookmark
        return $this->view([
            'bookmark' => $bookmark,
        ], 201);
    }
}
