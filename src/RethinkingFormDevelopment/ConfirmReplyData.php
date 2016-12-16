<?php
namespace Iltar\BlogArticles\RethinkingFormDevelopment;

use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmReplyData
{
    /** @Assert\IsTrue() */
    private $confirm = false;

    /** @Assert\Valid() */
    private $comment;

    public function __construct()
    {
        $this->comment = new CommentData();
    }

    public function getConfirm(): bool
    {
        return $this->confirm;
    }

    public function setConfirm(bool $confirm)
    {
        $this->confirm = $confirm;
    }

    public function getComment(): CommentData
    {
        return $this->comment;
    }

    public function setComment(CommentData $comment)
    {
        $this->comment = $comment;
    }
}
