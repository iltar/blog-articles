<?php
namespace Iltar\BlogArticles\RethinkingFormDevelopment;

use Symfony\Component\Validator\Constraints as Assert;

final class CommentData
{
    /** @Assert\Email() */
    private $email;

    /** @Assert\Length(min=25)) */
    private $comment;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    public function getComment(): string
    {
        return $this->comment;
    }

    public function setComment(string $comment)
    {
        $this->comment = $comment;
    }
}
