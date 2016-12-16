[//]: # (TITLE: Rethinking Form Development)
[//]: # (DATE: 2000-00-00T00:00:00+01:00)
[//]: # (TAGS: Symfony, Forms, DTO, Data Transfer Object, Entity, User Story, Minimal Viable Product)

In one of my previous blog posts, [Avoiding Entities in Forms][entities in forms], I've shown how to decouple your forms
from your entities. Afterwards I got a lot of feedback and most of it was about the lack of examples and the flow, when
to fill your data and how to get this back in the entity. One thing I notice however, is that developers design forms
based on their entities. This leads to complex forms because you're confined to a strict set of properties. Developers
often enough get struck with unmapped fields and form events to work their way around those limitations.

With Symfony Forms I highly encourage to follow the [composition over inheritance][composition over inheritance]
principle. Small form types are easier to re-use and make it less complex to build forms. Moreover, this allows small
data objects to have specific goals with validation for their specific case, rather than complex validation groups.

## The User Story
As a writer of blog posts, I want people to post comments on my blog post to gather feedback and answer questions.

Does not sound to hard, right?

## Starting From Scratch
As developer, you know what your form is supposed to do; you have data from a request and you want to create or update
a record in your database. Those records are commonly mapped by Entities. Let's say you could have a very simple
`BlogComment` entity, which has a `BlogPost` relation, a title, body and email address of the poster.

It makes sense to write a form for this Entity, let the form component do its magic and flush the entity. But as I've
mentioned in my other post, you should probably decouple this. So you check your entity and start extracting the fields
you need and come to the conclusion that it's a bit of overhead. Why extract a data object that's exactly the same as
the entity?

## Don't Think Like a Developer
Remember the User Story? It did not mention any technical details, only the goal. What do people need to enter when they
post a comment? I would say that in this case the bare minimum is good enough, an email address for verification and the
actual comment; the Minimal Viable Product. What should the page contain? Probably the post itself and the existing
replies. Underneath that you can place a pair of input fields for the comment.

## Finally, a Form!
Now you can actually put this into context, as you have the details of what you need.

```php
    // https://github.com/iltar/blog-articles/blob/master/src/RethinkingFormDevelopment/CommentFormType.php
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class);
        $builder->add('comment', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', CommentData::class);
    }

    // https://github.com/iltar/blog-articles/blob/master/src/RethinkingFormDevelopment/CommentData.php
    /** @Assert\Email() */
    private $email;

    /** @Assert\Length(min=25)) */
    private $comment;
```

This form is not too complex and is relatively easy to handle. Moreover, it's not bound to your entity and defines the
requirements rather than the setup of the database. All you need to do now is wire it to your controller.

```php
    // https://github.com/iltar/blog-articles/blob/master/src/RethinkingFormDevelopment/SimpleReplyController.php
    public function viewPostAction(Request $request, Post $post)
    {
        $data = new CommentData();
        $form = $this->formFactory->create(CommentFormType::class, $data);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = new Comment($post, $data->getEmail(), $data->getComment());

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return new RedirectResponse($request->getUri());
        }

        return $this->templating->render('comment.template', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }
```

## The Business Changed...
As a writer of blog posts, I want a default reply hinting people that they can post, so that 

Your User Story is finished and was deployed successfully. However, the business changes over time and a new User Story
is created.

[entities in forms]: /post/avoiding-entities-in-forms
[composition over inheritance]: https://en.wikipedia.org/wiki/Composition_over_inheritance
