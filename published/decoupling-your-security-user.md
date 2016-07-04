[//]: # (TITLE: Decoupling Your Security User)
[//]: # (DATE: 2016-07-03T10:00:00+01:00)
[//]: # (TAGS: symfony, security, php, data transfer object, session, serialize, entity)

A lot of developers come to `#symfony` and ask how to implement a login or authentication system. It's quite common to
have a bunch of features which require authentication and authorization. While authentication is identifying your user,
authorization is granting permissions to this user.

One of the steps of implementing the security features of your application, involves creating an object to contain
your user information such as your username, email, password and user id. If you have followed the Symfony docs,
you will most like have ended up with a `User` entity implementing the
`Symfony\Component\Security\Core\User\UserInterface`. I would like to show you an alternative- decoupled- approach
which will prevent several issues within a Symfony application.

## Once Upon a Login...
In Symfony the user object which represents the currently authenticated user, is stored within a token:
`Symfony\Component\Security\Core\Authentication\Token\TokenInterface`. Internally this token is stored in the session as
a serialized object and accessible via the token storage service: `security.token_storage`. The most frequently used
implementation is [by fetching an entity from your database][entity user provider]:

```php
/** @ORM\Table() @ORM\Entity() */
class User implements UserInterface, \Serializable
{
    // ...
}
```

While the documentation gives a very detailed explanation and a nice example of how easy it can be, this also comes with
side effects:
 - You will end up with this Entity in your session
 - Developers tend to also use this entity in forms

### Session Entities

If you end up with Entities in your session, you will get synchronization issues. If you update your entity, that means
your session entity won't be updated as it's not from the database. In order to solve this issue, you can merge the
entity back into the entity manager each request.

While this solves one of the problems, another common issue is the (un)serialization. Eventually your `User` Entity
will get relations to other objects and this comes with several side-effects:
 - Relations will be serialized as well
 - If a relation is lazy loaded (standard setting), it will try to serialize the Proxy which contains a connection. This
   will spew some errors on your screen as the connection cannot be serialized.

Oh and don't even think about changing your Entity such as adding fields, this will cause unserialization issues
with incomplete objects because of missing properties. This case is triggered for every authenticated user.

### Entities in Forms
If you haven't already, check my previous blog post: [Avoiding Entities in Forms][avoiding-entities-in-forms]. This will
give you an idea on solving this particular issue.

## Back to Basics
The solution to this problem is rather simple actually. Those who know me can probably guess the solution already:
Data Transfer Objects. This object's responsibility is to feed the security system with only the information required.
The sole responsibility of this object is to implement the `UserInterface` and provide the security system with
authentication.

```php
class SecurityUser implements UserInterface, \Serializable
{
    private $username;
    private $password;

    // other properties

    public function __construct(User $user)
    {
        $this->username = $user->getId();
        $this->password = $user->getPassword();
        // ... other info
    }

    // ... getters and issers to provider info to the security system
}
```

### A Layer of Abstraction
[According to the documentation][loadUserByUsername], you have to implement an interface which will return an object
implementing the `UserInterface`: the `Symfony\Bridge\Doctrine\Security\User\UserLoaderInterface`. In the example this
is done by adding it to your `UserRepository`, which is a doctrine `EntityRepository`.

Considering your `UserRepository` must return `User` entities, you can't simply make it return a `SecurityUser`. To
solve this, you have to make an object using the `UserRepository` creating a `SecurityUser`.

```php
class SecurityUserFactory implements UserLoaderInterface
{
    private $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function loadUserByUsername($username)
    {
        if (null === ($user = $this->userRepository->findOneByUsernameOrId($username))) {
            throw new BadCredentialsException(sprintf('No user found for "%s"', $username));
        }

        // create the DTO and feed it with the entity
        return new SecurityUser($user);
    }
}
```
## Recap
So what you've done is the following:
 - Your Entity is no longer stored in the session, thus avoiding synchronization and serialization issues
 - Your Entity and EntityRepository are no longer tightly coupled to the security system
 - Your `SecurityUser` now only contains data required for identification

This also means that if you request the security user from the token storage (either directly or indirectly), it will
no longer contain an entity. While some may argue this is a downside, I prefer it this way. The security user contains
an identifier which is related to your User object in the database. If you happen to need this Entity often, you could
create an [ArgumentValueResolver][argument value resolver]. This resolver would fetch the Entity based on the security
user and present it in your action arguments. If you use an older version of Symfony, you can do this with a [Parameter
Converter][parameter converter].

[entity user provider]:http://symfony.com/doc/current/cookbook/security/entity_provider.html
[avoiding-entities-in-forms]:http://stovepipe.systems/post/avoiding-entities-in-forms
[loadUserByUsername]:http://symfony.com/doc/current/cookbook/security/entity_provider.html#using-a-custom-query-to-load-the-user
[argument value resolver]:http://symfony.com/doc/current/cookbook/controller/argument_value_resolver.html
[parameter converter]:http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/converters.html
