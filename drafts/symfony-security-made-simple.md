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

[1]: http://symfony.com/doc/current/security.html#learn-more
