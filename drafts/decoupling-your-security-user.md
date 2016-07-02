[//]: # (TITLE: Decoupling Your Security User)
[//]: # (DATE: 2017-01-01T00:00:00+01:00)
[//]: # (TAGS: symfony, security, php, data transfer object)

Lots of people who come to symfony ask how to implement login or authentication system. It's quite common to have a
bunch of features which require authentication and authorization. While authentication is identifying your user,
authorization is granting permissions to this user.

One of the steps of implementing the security features of your application, involves creating an object to contain
your user information such as your username, email, password and user id. If you have followed the Symfony docs,
you will most like have ended up with a `User` entity implementing the
`Symfony\Component\Security\Core\User\UserInterface`. I would like to show you an alternative -decoupled- approach
which will prevent several issues within a Symfony application.
