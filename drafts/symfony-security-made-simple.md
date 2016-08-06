[//]: # (TITLE: Symfony Security Made Simple)
[//]: # (TAGS: php, security, configuration, authentication, authorization, firewall, access control)

One of the more complex parts of Symfony is probably the Security and everything that comes with it. It's not only
rather big, it's also quite flexible with lots of different concepts which often confuse developers. Often enough when
developers implement a security system for their website, they call it Authentication or Authorization yet often don't
exactly know what they are exactly supposed to call it.

One quote I always refer to is "if you can't explain it simply you don't understand it well enough" and I think it's
rather fitting for most cases. I think I've reached a point where I can explain it well enough, so bare with me!

## Authentication vs. Authorization

What did I just implement, was it authentication or authorization? The Symfony documentation mentions it briefly
[if you look close enough][1]. Authentication is basically identification; "who are you?" and authorization is
all about permissions; "What may I do?".

To put this in perspective, imagine a Government Office building. This building has a large fence around its perimeter
with a check point, a small bald guy and a gate that can open. In order to enter the perimeter, you have to show your
badge to the small bald guy and he will verify if it's really you. If he can confirm you are who you claim to be, he
will open the gate for you. This means you are now identified or in terms of security: authenticated.

Once you enter the building, you are free to roam around as much as you want. You approach one of the office areas and
there's a tough guy with a scanner standing at the entrance, dressed in a guard uniform. You approach him, he asks you
for your badge and tells you it's a restricted area, not everyone is allowed to enter. You hand him over your badge, the
scanner bleeps and he lets you pass. You are now authorized to enter the restricted area, you have the permissions.

To put this into context, a login means you identify -or authenticate- yourself and seeing that "promote to Admin"
button means you have the permissions -or authorization- to do so.

## Symfony and Security

In Symfony the `SecurityBundle` lets you configure the `SecurityComponent`. The configuration for this bundle looks
rather big [and honestly, it is][2]. The most important parts you will work with are directly under the `security` key:
`firewall` and `access_control`.

### The Firewall

> In computing, a firewall is a network security system that monitors and controls the incoming and outgoing network
  traffic based on predetermined security rules. A firewall typically establishes a barrier between a trusted, secure
  internal network and another outside network, such as the Internet, that is assumed not to be secure or trusted.
>
> _- [Quoted from Wikipedia][3]_

In Symfony the firewall serves a similar purpose. A firewall is used to configure the methods of authentication. Various
methods can be implemented, stateless API authentications with keys, OpenID, a simple login form, http-basic auth etc.
While I won't go deeper into the actual methods of authentication, I think it's important to know how this works. While
[the documentation explains fairly well how it works][4], I'd like to add a bit more info related to the authentication
concept. While this config is not complete, it are the two most important configuration parts.

```yml
security:
    # ... 
    
    firewall:
        secured_area:
            pattern: ^/
            anonymous: ~
```


#### Pattern

The pattern defines which urls are covered by this firewall. If a request is matched on this pattern, this firewall will
kick in and try to authenticate the user. You can compare this to the fence around the office building.

#### Anonymous

This options enables an anonymous login for the given pattern. Long story short, it falls back to acknowledging that a
user simply cannot be identified. This is particularly useful if users can visit the pages only when identified but does
not actually require a successful login. This option is also used to add your login url to the same firewall as you
authenticate against.

If you were to compare this to the check point. As guest you can still roam around the terrain and building after
pasing, except that it's unknown who you really are. You can't really do much anyway as everything inside still requires
permissions.

Please note that while it may seem like being anonymous means you have been granted permissions, this is not
authorization nor does the firewall have anything to do with authorization.

### Access Control

Another important part of the security within symfony, is access control, this is the Authorization part. Symfony has
multiple ways of regulating access to resources: The `access_control` option, `@Security` annotations on controllers and
a more often used variant: `AuthorizationCheckerInterface::isGranted()`.

#### The access_control Option

This option is pretty much a set of requirements being evaluated top down. For a customer portal you might want to
require authentication for each page except the login page. This is [easily done via access control][5].

```yml

security:
    # ...
    
    access_control:
        # allow anonymous authentication on ^/login but nothing else
        - { path: ^/login, roles: IS_AUTHENTICATED_ANONYMOUSLY }
        - { path: ^/, roles: IS_AUTHENTICATED_FULLY }
```

#### The Security Annotation

While the access control is really nice to restrict access, you might want to have more specific access control for
controllers and actions. With [the security annotation][6] you can use the expression language to add extra checks.

```php
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class FooController
{
    /**
     * @Security("is_granted('CAN_SEE_POST', post)")
     */
    public function fooAction(Post $post)
    {
        // ...
    }
}

```

> The expression has access to the request attributes, this means that if you use a Parameter Converter, you can access
  the objects converted.

#### The AuthorizationChecker

The third most used access control method is [the `AuthorizationChecker`][7]. In the previous example you can see the
annotation with `is_granted`. Internally this translates to roughly the following PHP:

```php
$authorizationChecker->isGranted('CAN_SEE_POST', $request->attributes->get('post'));
```

The `AuthorizationChecker` is ideal to check permissions of something that would not influence your program flow, e.g.
hiding a button or link in your template or fetching more records from your database if you have the right permissions.

## What's Next?

While I don't intend to explain every security related item in detail, I will write a blog post about Roles vs Voters.
This will be one of the Authentication vs Authorization examples most often confused when designing access control in
Symfony applications and websites.


[1]: http://symfony.com/doc/current/security.html#learn-more
[2]: http://symfony.com/doc/current/reference/configuration/security.html
[3]: https://en.wikipedia.org/wiki/Firewall_(computing)
[4]: http://symfony.com/doc/current/security.html
[5]: http://symfony.com/doc/current/security.html#securing-url-patterns-access-control
[6]: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/annotations/security.html
[7]: http://symfony.com/doc/current/components/security/authorization.html#authorization-checker
