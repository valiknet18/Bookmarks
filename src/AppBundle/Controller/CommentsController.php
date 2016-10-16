<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Bookmark;
use AppBundle\Entity\Comment;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommentsController extends FOSRestController
{
    /**
     * @param Request $request
     * @param Bookmark $bookmark
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     */
    public function postCommentAction(Request $request, Bookmark $bookmark)
    {
        $em = $this->getDoctrine()->getManager();

        $comment = new Comment();

        $comment->setText($request->request->get('text'));
        $comment->setBookmark($bookmark);
        $comment->setIp($request->getClientIp());

        $errors = $this
            ->get('validator')
            ->validate($comment)
        ;

        if (sizeof($errors) > 0) {
            return $this->view([
                'errors' => $errors,
            ], 406);
        }

        $em->persist($comment);
        $em->flush();

        return $this->view([
            'comment' => $comment,
        ]);
    }

    /**
     * @param Request $request
     * @param Bookmark $bookmark
     * @param Comment $comment
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     */
    public function putCommentAction(Request $request, Bookmark $bookmark, Comment $comment)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            $this->denyAccessUnlessGranted('edit', $comment);

            $comment->setText($request->get('text'));

            $errors = $this
                ->get('validator')
                ->validate($comment)
            ;

            if (sizeof($errors) > 0) {
                return $this->view([
                    'errors' => $errors,
                ]);
            }

            $em->flush();

            return $this->view([
                'comment' => $comment,
            ]);
        } catch (AccessDeniedException $ex) {
            return $this->view([
                'message' => 'Access denied',
            ], 403);
        }
    }

    /**
     * @param Request $request
     * @param Bookmark $bookmark
     * @param Comment $comment
     * @return \FOS\RestBundle\View\View
     *
     * @Rest\View(serializerEnableMaxDepthChecks=true)
     */
    public function deleteCommentAction(Request $request, Bookmark $bookmark, Comment $comment)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            $this->denyAccessUnlessGranted('delete', $comment);

            $em->remove($comment);
            $em->flush();

            return $this->view([
                'message' => 'Comment successful deleted'
            ], 200);
        } catch (AccessDeniedException $ex) {
            return $this->view([
                'message' => 'Access denied',
            ], 403);
        }
    }
}
