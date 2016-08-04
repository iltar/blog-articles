[//]: # (TITLE: Symfony Security Made Simple)
[//]: # (TAGS: php, security, configuration, authentication, authorization, firewall, access control)

One of the more complex parts of Symfony is probably the Security and everything that comes with it. It's not only
rather big, it's also quite flexible with lots of different concepts which people often confuse. Often enough when
developers implement a security system for their website, they call it Authentication or Authorization yet often don't
exactly know what they are exactly supposed to call it.

One quote I always refer to is "if you can't explain it simply you don't understand it well enough" and I think it's
rather fitting for most cases. I think I've reached a point where I can explain it well so bare with me!

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

In Symfony the `SecurityBundle` lets your configure the `SecurityComponent`. The configuration for this bundle looks
rather big [and honestly, it is][2]. The most important parts you will work with are directly under the `security` key:
firewall and access_control.

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
concept.

```yml
security:
    # ... 
    
    firewall:
        secured_area:
            pattern: ^/
            anonymous: ~
```

While this config is not complete, it are the two most important configuration parts.
 - `pattern`: this defines which urls are covered by this firewall. If a request is matched on this pattern, this 
  firewall will kick in and try to authenticated the user.
 - `anonymous`: this allows an anonymous login. Long story short, it falls back to acknowledging that a user simply
  cannot be identified. This is particularly useful if users can visit the pages only when identified but does not 
  actually require a successful login.

The firewall configuration is big but flexible. There are dozens of authentication methods and restrictions possible but
has no direct relations with any forms of authorization whatsoever.


[1]: http://symfony.com/doc/current/security.html#learn-more
[2]: http://symfony.com/doc/current/reference/configuration/security.html
[3]: https://en.wikipedia.org/wiki/Firewall_(computing)
[4]: http://symfony.com/doc/current/security.html
