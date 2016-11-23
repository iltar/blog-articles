[//]: # (TITLE: What are Bundles in Symfony?)
[//]: # (DATE: 2016-11-24T11:00:00+01:00)
[//]: # (TAGS: Symfony, Bundle, Module, Extension, DIC, Dependency Injection Container)

People often refer to bundles as modules or re-usable code for Symfony applications. When a developer has
experience with Symfony1 or another framework with the module concept, it might seem logical that this is what a bundle
represents in Symfony.

So what is a bundle? When do you need one and what can it do?

## Bundle Spaghetti
With the release of Symfony 2.0, it was often presumed useful to split code logically
in bundles. One could for example build a UserBundle, InvoicingBundle, ContractBundle, LoginBundle and so on. This
however, creates a spaghetti of dependencies and this was not necessarily in the code.

Twig is the main templating engine used in Symfony. So when using this bundle setup, templates were usually located in `src/SomeBundle/Resources/views/` or a similar structure. Moreover, it's quite common to use template inheritance. This
means that instead of including blocks of templates everywhere, you extend a base template. The question is, where do
you put this base template? Often in a website, you have similar layouts for all page, this means they share a common
parent, often called `layout.html.twig`. Either you put this in your application directly: `app/Resources/views` or
in a bundle that's shared between them.

This already creates a tight coupling between the bundles (and application), but is further strengthened by service
definitions and routes; for example my Invoice links to a Contract, this means that my InvoicingBundle has a hard dependency
on the ContractBundle as the routes are now known.

### The AppBundle
Introducing the AppBundle to solve this dependency problem. This meant that instead of splitting bundles
by domain, the AppBundle contains this split and recommends Resources to be added to `app/Resources/` instead
of having them in the bundle. This solved the problem of having too many bundles in your application, but what about
vendor bundles?

## What is a Bundle?
When looking back to what they are used for, we can determine that it seems to be pretty much an application. It has our
Controllers, Entities, Commands and probably a bunch of classes related to domain logic. So what is a bundle?

A bundle is literally nothing more than an extension point. All the features listed above are determined based on the
bundle. A bundle contains at least a bundle class and allows things like:
 - The `@Bundle` notation to alias everything referring to a bundle resource
 - Automatically finding entities when using the DoctrineBundle
 - Automatically registering commands

## Core Features of a Bundle
As mentioned, the bundle provides an extension point. Other bundles for example, can hook in on your bundle because it contains some
logic to expose information such as the directory of the bundle. This means that you can easily write a bundle that
scans all available bundles for an Entity directory and try to register entities found inside, for example the DoctrineBundle.

The main purpose of a bundle however, is to provide an extension point on the Dependency Injection Container. When
talking about this extension point, it revolves around adding, changing or removing service definitions. Often
when you have a library and you want to register certain classes as service, you write a bundle for it, simply to
register them in your kernel without having to do this in your AppBundle. Now we're talking about a re-usable vendor
bundle.

Often you want those services to be configured; for example a connection of some sorts often requires a host, username and
password. You don't want to rely on parameters that might or might not have be configured in the config.yml file, so you want
to actually add a [Configuration tree][config docs] which will force the right values to be defined the way you want it
to. This configuration will be processed by an [Extension class][extension docs] and here you can modify the
container.

Note that the Extension class only knows about your configuration and scopes the container, that
means you can't modify services defined outside of your bundle. This brings us another feature: Compiler
Passes. This feature can register compiler passes, which can be used to manipulate the container in
specific build phases by a form of priority. A compiler pass runs on a more complete container and runs
after the Extension initialization of the bundles.

## Conclusion
As I have explained, there's a difference between an application bundle and a re-usable vendor bundle. Do you need
multiple bundles in your application directly? Most likely not. You're better off writing an AppBundle to prevent
a spaghetti of dependencies. You can simply follow the [best practices][best practices] and it will work fine.

When building re-usable bundles, make them infrastructural. Elnur has written [a blog post][elnur blog post]
about infrastructural bundles.

> Infrastructural bundles are the ones that affect the infrastructure of your app but not its domain and database
  schema.

At the moment a bundle in your application is more of a convenience. It provides a few easy tricks to reduce
the amount of knowledge to get a project starts:
 - Entity registration
 - Command registration
 - What ever other bundles automatically register

In theory you don't need an AppBundle:
 - [Controller as a Service][controller service]
 - [Command as a Service][command service]
 - Often no need to directly manipulate the container
 - You can register services directly in config.yml or even load a complete directory of service files
 - Your entities are better off outside of the bundle as they don't require a bundle to function

[Hopefully when 3.3 is released][3.3 pr], it should become easier to register some basic container manipulation without
the use of a bundle, which for me, removes the last requirement to remove the AppBundle from my application.

[config docs]: https://symfony.com/doc/3.1/components/config/definition.html
[extension docs]: https://symfony.com/doc/3.1/bundles/extension.html
[best practices]: http://symfony.com/doc/current/best_practices/index.html
[elnur blog post]: http://elnur.pro/use-only-infrastructural-bundles-in-symfony/
[controller service]: http://symfony.com/doc/current/controller/service.html
[command service]: https://symfony.com/doc/current/console/commands_as_services.html
[3.3 pr]: https://github.com/symfony/symfony/pull/20107

