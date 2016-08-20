[//]: # (TITLE: Symfony Security Roles vs. Voters)
[//]: # (DATE: 2016-08-20T09:00:00+01:00)
[//]: # (TAGS: php, security, roles, voters, authentication, authorization, firewall, access control)

In my [previous blog bost][1] I've explained the basics of authentication, authorization and how this is dealt with in
Symfony. Due to the size of the post, I've left out several important topics such as roles and voters; Both an equally
important part of authentication and authorization. A common misconception is that roles should be used to check 
permissions. In fact, they should definitely not be used to check permissions! 

[1]: ./the-basics-of-symfony-security

## Roles and Authentication

Roles are primarily for authentication as they extend on the part of identification. A role describes something about a
user, for example `ROLE_USER` defines I'm a normal user and `ROLE_ADMIN` could define that I'm an administrator. In the
[Security documentation][2] it's explained how the `ROLE_` prefix is used and how this fits in with authorization. It
explains how the `ROLE_USER` is commonly assigned and how to check this for access with `access_control`. It also
briefly mentions the role hierarchy and how this is used to vote on dynamic roles; E.g. if you've got `ROLE_ADMIN` you
can have it virtually assign the `ROLE_USER` automatically.

While the role hierarchy looks interesting, it has nothing to do with authentication. In fact, this is the authorization
dealing with this virtual inheritance. The only way to trigger this, is by checking if you're allowed to do something;
Authorization. The example is pointing at `access_control` verifying if you have the required role for a specific route.
While this may seem nice, this is not how you should be using checking permissions directly.

## Voters and Authorization

So what should you be using then? Voters. Voters are classes that simply vote on an attribute and optionally a subject.
An attribute is usually an uppercase string that defines an action and a subject is being voted on if required. Did you 
know that the only reason you can vote on (dynamic) roles, is because of the [RoleVoter][3] and [RoleHierarchyVoter][4]?
They simply check if the token contains the roles specified. 

The [symfony documentation explains Authorization][5] if you want to dive a bit deeper into its inner workings. 
[Voters][6] basically come down to the following:
 - Can I vote on this attribute?
 - When I vote on this attribute do I return true or false?

Voters are triggered for every authorization part:
 - The `access_control` configuration triggers them;
 - The `@Security` annotation triggers them;
 - The `AuthorizationChecker` uses it via the `AccessDecisionManager`.
 
All of the above authorization methods use an attribute (or multiple) and a subject to vote on.

## So Why Should I Use Voters Instead of Roles?

As I've explained, roles are merely an extension to authentication, they serve as extra descriptions to your identity.
calling something like `$authorizationChecker->isGranted('ROLE_ADMIN')` doesn't really make sense, what are you actually
checking here? Let's say that I have a button to edit a forum post:
 - The owner may edit it;
 - The admin may edit it;
 - A moderator may edit it.

Let's add the link to the edit page:
```twig
{% if post.owner.id is app.user.username or is_granted('ROLE_MODERATOR') or is_granted('ROLE_ADMIN') %}
    <a href="{{ path('...') }}">Edit Post</a>
{% endif %}
```
And let's add the permission check in the controller:

```php
public function editPostAction(Post $post)
{
    // ... 
    /** @var $token \Symfony\Component\Security\Core\Authentication\Token\TokenInterface */
    /** @var $AuthorizationChecker \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface */
    if ($post->getOwner()->getId() !== $token->getUsername()
        && !$AuthorizationChecker->isGranted('ROLE_MODERATOR')
        && !$AuthorizationChecker->isGranted('ROLE_ADMIN')
    ) {
        throw new AccessDeniedHttpException();
    }
    // ...
}
```

As you can see, this is quite some logic just to check if the current user can see it. Now you want to add another
condition; The post may not be locked. Lets update the template!

```twig
{% if (post.owner.id is app.user.username and not post.locked)
    or is_granted('ROLE_MODERATOR') 
    or is_granted('ROLE_ADMIN')
%}
    <a href="{{ path('...') }}">Edit Post</a>
{% endif %}
```
Done, right? Oh, you still need to update the controller as well.

```php
<?php

public function editPostAction(Post $post)
{
    // ... 
    /** @var $token \Symfony\Component\Security\Core\Authentication\Token\TokenInterface */
    /** @var $AuthorizationChecker \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface */
    if (($post->getOwner()->getId() !== $token->getUsername() || $post->isLocked())
        && !$AuthorizationChecker->isGranted('ROLE_MODERATOR')
        && !$AuthorizationChecker->isGranted('ROLE_ADMIN')
    ) {
        throw new AccessDeniedHttpException();
    }
    // ...
}
```
All set, `git push` and be done with it. Except that you product owner wants this link shown in the topic overview as
well as in the post itself. Well, that's going to be a big copy paste... So how can you improve this?

## Creating a Voter

The solution is rather simple, create a voter. The easiest way to create a voter is by [extending the `Voter`][7] that
Symfony already provides. There's a few things you need to decide before making the class:
 - What will it vote on, or the attribute, what should it be called?
 - Do you have a subject or not?
 - What would give it access?

First off you start by making a class:

```php
<?php
namespace App\Security\Voter;

use App\Entity\Post;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class EditPostVoter extends Voter
{    
    protected function supports($attribute, $subject)
    {
        // you only want to vote if the attribute and subject are what you expect
        return $attribute === 'CAN_EDIT_POST' && $subject instanceof Post;
    }
    
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // our previous business logic indicates that mods and admins can do it regardless
        foreach ($token->getRoles() as $role) {
            if (in_array($role->getRole(), ['ROLE_MODERATOR', 'ROLE_ADMIN'])) {
                return true;
            }
        }   
        
        /** @var $subject Post */
        return $subject->getOwner()->getId() === $token->getUsername() && !$subject->isLocked();
    }
}
````
> You can also use the [role hierarchy with the access decision manager][8] if you want virtual roles.

The next thing to do, is create a service definition so the security picks it up. It's as simple as adding a tag.

```yaml
# app/config/services.yml
services:
    app.security.voter.edit_post:
        class: App\Security\Voter\EditPostVoter
        tags:
            - { name: security.voter }
```

The last things are to replace the security checks.

```php
<?php
// controller
public function editPostAction(Post $post)
{
    /** @var $AuthorizationChecker \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface */
    if (!$AuthorizationChecker->isGranted('CAN_EDIT_POST', $post)) {
        throw new AccessDeniedHttpException();
    }
    
    // ...
}
```

If you prefer less code to achieve the same, you can change the way the access granted by utilizing the [`@Security` 
annotation][9] from the SensioFrameworkExtraBundle. Before the controller is executed, it will executed the expression
defined in the annotation to verify access.

```php
<?php
// controller
/**
 * @Security("is_granted('CAN_EDIT_POST', post)")
 */
public function editPostAction(Post $post)
{
    // ...
}
```
> One cool thing about the `@Security` is that all arguments in the action are available in your expression as long
  as they come in via the request attributes, E.g as the parameter converter does.

And last, the template to check if the button can be shown.
```twig
{% if is_granted('CAN_EDIT_POST', post) %}
    <a href="{{ path('...') }}">Edit Post</a>
{% endif %}
```

Now when ever you need to check if the user is allowed to post, you can simply add the above checks without having to
worry about the complicated logic behind it. It also makes it a lot easier to modify the logic as there's only one
location to be updated.

## Back to Basic Security

Some things are already in place, such as the ability to check if a user is logged in. Symfony comes with three
different authentication levels which you can use for authorization checks in order:
 - `IS_AUTHENTICATED_ANONYMOUSLY`: Indicates that the minimal security level has to match the `anonymous: ~` option in
 as configured in the firewall. This is what I recommend to place on the root: `^/` in your access control. Enabling
 this means that every request your user will be authenticated and has an AnonymousToken to use the basic security
 features.
 - `IS_AUTHENTICATED_REMEMBERED`: Indicates that the `remember_me` option in the firewall should be triggered as minimal
 level of authentication. This is a feature to remember the user without actually having to log in. Not recommended if
 security for certain actions is mandatory.
 - `IS_AUTHENTICATED_FULLY`: Indicates that a full authentication has to take place in order to grant access. This is
 the option I recommend for pages you need to be logged-in if you don't use the remember me features or if you want to
 force the user to login manually to access the feature.

Internally they are all voted on by the [AuthenticatedVoter][10].

[2]: http://symfony.com/doc/current/security.html#denying-access-roles-and-other-authorization
[3]: https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/RoleVoter.php
[4]: https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/RoleHierarchyVoter.php
[5]: http://symfony.com/doc/current/components/security/authorization.html
[6]: http://symfony.com/doc/current/security/voters.html
[7]: http://symfony.com/doc/current/security/voters.html#the-voter-interface
[8]: http://symfony.com/doc/current/security/voters.html#checking-for-roles-inside-a-voter
[9]: https://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html
[10]: https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Security/Core/Authorization/Voter/AuthenticatedVoter.php
