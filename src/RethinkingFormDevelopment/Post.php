<?php
namespace Iltar\BlogArticles\RethinkingFormDevelopment;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity() */
class Post
{
    /** @ORM\Column(type="text") */
    private $title;

    /** @ORM\Column(type="text") */
    private $author;

    /** @ORM\Column(type="text") */
    private $body;

    /** @ORM\OneToMany(targetEntity="Comment", mappedBy="post") */
    private $comments;

    public function __construct(string $title, string $author, string $body)
    {
        $this->title = $title;
        $this->author = $author;
        $this->body = $body;
        $this->comments = new ArrayCollection();
    }

    public function addComment(Comment $comment)
    {
        $this->comments->add($comment);
    }

    // .. getters
}
