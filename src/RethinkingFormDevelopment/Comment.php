<?php
namespace Iltar\BlogArticles\RethinkingFormDevelopment;

use Doctrine\ORM\Mapping as ORM;

/** @ORM\Entity() */
class Comment
{
    /**
     * @ORM\ManyToOne(targetEntity="Post", inversedBy="comments")
     * @ORM\JoinColumn(nullable=false)
     */
    private $post;

    /** @ORM\Column(type="text") */
    private $email;

    /** @ORM\Column(type="text") */
    private $comment;

    public function __construct(Post $post, string $email, string $comment)
    {
        $this->post = $post;
        $this->email = $email;
        $this->comment = $comment;

        $this->post->addComment($this);
    }

    // .. getters
}
