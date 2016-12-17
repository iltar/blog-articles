<?php
namespace Iltar\BlogArticles\RethinkingFormDevelopment;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Templating\EngineInterface;

class ConfirmReplyController
{
    private $formFactory;
    private $templating;
    private $entityManager;

    public function __construct(FormFactoryInterface $formFactory, EngineInterface $templating, EntityManagerInterface $entityManager)
    {
        $this->formFactory = $formFactory;
        $this->templating = $templating;
        $this->entityManager = $entityManager;
    }

    public function viewPostAction(Request $request, Post $post)
    {
        $data = new ConfirmReplyData();
        $form = $this->formFactory->create(ConfirmReplyFormType::class, $data);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $commentData = $data->getComment();
            $comment = new Comment($post, $commentData->getEmail(), $commentData->getComment());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return new RedirectResponse($request->getUri());
        }

        return $this->templating->render('/confirm_reply/view_post.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }
}
