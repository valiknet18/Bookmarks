<?php

namespace AppBundle\Voter;

use AppBundle\Entity\Comment;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class CommentVoter extends Voter
{
    const EDIT = 'edit';
    const DELETE = 'delete';

    private $request;

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        if (!in_array($attribute, array(self::EDIT, self::DELETE))) {
            return false;
        }

        if (!$subject instanceof Comment) {
            return false;
        }

        return true;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Comment $comment */
        $comment = $subject;

        switch ($attribute) {
            case self::DELETE:
                return $this->canDelete($comment);
            case self::EDIT:
                return $this->canEdit($comment);
        }

        throw new \LogicException('This code should not be reached!');
    }

    /**
     * @param Comment $comment
     * @return bool
     */
    private function canEdit(Comment $comment)
    {
        $clientIp = $this->getRequest()->getClientIp();
        //Check difference in date, if created_at date less than 1 hour
        $diff = ((new \DateTime())->diff($comment->getCreatedAt())->h < 1) && ($comment->getCreatedAt()->modify('+1 day') > (new \DateTime()));

        if ($clientIp !== $comment->getIp() || !$diff) {
            return false;
        }

        return true;
    }

    /**
     * @param Comment $comment
     * @return bool
     */
    private function canDelete(Comment $comment)
    {
        return $this->canEdit($comment);
    }

    /**
     * @param RequestStack $requestStack
     */
    public function setRequest(RequestStack $requestStack)
    {
        $this->request = $requestStack->getMasterRequest();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
