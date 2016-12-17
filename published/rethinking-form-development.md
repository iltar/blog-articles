[//]: # (TITLE: Rethinking Form Development)
[//]: # (DATE: 2016-12-17T14:30:00+01:00)
[//]: # (TAGS: Symfony, Forms, DTO, Data Transfer Object, Entity, User Story, Minimal Viable Product)

In one of my previous blog posts, [Avoiding Entities in Forms][entities in forms], I've shown how to decouple your forms
from your entities. Afterwards I got feedback and most of it was about the lack of examples and the flow, when
to fill your data and how to get this back in the entity. However, often I notice that developers design forms
based on their entities. This can lead to complex forms because you're confined to a strict set of properties.
Developers often get struck with unmapped fields and form events to work their way around those limitations.

With Symfony Forms I highly recommend to follow the [composition over inheritance][composition over inheritance]
principle. Small form types are easier to re-use and make it less complex to build forms. Moreover, this allows small
data objects to have specific goals with validation for their specific case, rather than complex validation groups.

## The User Story
Story: _As a writer of blog posts, I want people to be able to post comments on my blog post so I can gather feedback
and answer questions._

Does not sound too hard, right?

## Starting From Scratch
As a developer, you know what your form should do; you have data from a request and you want to create or update
a record in your database. Those records are commonly mapped by Entities. Let's say you could have a very simple
`BlogComment` entity, which has a `BlogPost` relation, a title, body and email address of the poster.

It makes sense to write a form for this Entity, let the form component do its magic and flush the entity. However, as
written in my other post, you should probably decouple this. So you check your entity and start extracting the fields
you need and come to the conclusion that it has a slight overhead. Why extract a data object that's identical to the
entity?

## Don't Think Like a Developer
Remember the User Story? It did not mention any technical details, only the goal. What do people need to enter when they
post a comment? I would say that in this case the bare minimum is sufficient, an email address for verification and the
actual comment; the Minimal Viable Product. What should the page contain? Probably the post itself and the existing
replies. Underneath that you can place a pair of input fields for the comment.

## Finally, a Form!
Now you can actually put this into context, as you have the details of what you need.

```php
    // CommentFormType.php
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('email', EmailType::class);
        $builder->add('comment', TextareaType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', CommentData::class);
    }
```
```php
    // CommentData.php
    /** @Assert\Email() */
    private $email;

    /** @Assert\Length(min=25)) */
    private $comment;
```

This form is not too complex and is relatively easy to handle. Moreover, it's not bound to your entity and defines the
requirements rather than the setup of the database. All you need to do now is wire it to your controller.

```php
    // SimpleReplyController.php
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
You finished the User Story and you successfully deployed your code to production. However, the business changes over
time and someone created a new User Story.

Story: _As a writer of blog posts, I want a checkbox on another page to confirm the post, so that users explicitly have
to agree with our terms._

A confirmation field still requires something to hold the data to say yes or
no. Luckily you have seen how to not use entities but DTOs for your form, thus adding one should be a piece of cake!

## Adding a Confirm Checkbox
As mentioned before, I encourage composition over inheritance. To accomplish this, you can create a new form type and
data object that wrap around the `CommentData` and `CommentType`.

```php
    // ConfirmReplyFormType.php
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('confirm', CheckboxType::class, ['required' => true]);
        $builder->add('comment', CommentFormType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('data_class', ConfirmReplyData::class);
    }
```
```php
    // ConfirmReplyData.php
    /** @Assert\IsTrue() */
    private $confirm;

    /** @Assert\Valid() */
    private $comment;
```

To keep both controllers functional, a new controller can be added. However, this controller contains only some slight
modifications but essentially works the same; it transfers data from the DTO into an Entity and flushes it.

```php
    // ConfirmReplyController.php
    public function viewPostAction(Request $request, Post $post)
    {
        $data = new ConfirmReplyData();
        $form = $this->formFactory->create(ConfirmReplyFormType::class, $data);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $data->getComment();

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            return new RedirectResponse($request->getUri());
        }

        return $this->templating->render('/confirm_reply/view_post.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }
```

To sum this up:
 - It's a good idea to follow [Composition over Inheritance][composition over inheritance] for forms.
 - It's actually quite easy to use Data Transfer Objects.
 - Decoupling your entity from forms is easier if you rethink from the ground up what your form should do and how to
 model this.
 
If you wish to view the full classes, you can check them out in my blog-articles repository, where the articles posted
on this blog are stored: https://github.com/iltar/blog-articles/tree/master/src/RethinkingFormDevelopment

[entities in forms]: /post/avoiding-entities-in-forms
[composition over inheritance]: https://en.wikipedia.org/wiki/Composition_over_inheritance
