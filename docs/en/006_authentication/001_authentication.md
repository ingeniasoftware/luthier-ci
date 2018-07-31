[//]: # ([author] Julio Cedeño)
[//]: # ([meta_description] Luthier CI includes powerful user authentication tools, inspired by Symfony, so you can worry about what really matters about your application)

# Authentication

### Introduction

CodeIgniter includes all the necessary tools to build a user authentication system. Unfortunately, it lacks an integrated interface or library that's easy to implement, maintain and scale.

Luthier CI tackles the problem using an authentication model inspired by Symfony, which looks for as much flexibility as possible so that the developer can start working quickly, without needing to reinvent the wheel.
### Activation

As an optional module, the Luthier CI authentication functions must be activated first. To do so, go to the `application/config/hooks.php` file and replace it:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks();
```

With:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks(
    [
        'modules' => ['auth']
    ]
);
```

### Authentication tools available

The authentication of Luthier CI comes in two flavors: **SimpleAuth** and the **Luthier CI Authentication Framework**.

#### SimpleAuth: the fastest and funniest way

If what you need is a pre-configured, customizable and easy-to-use authentication system, **SimpleAuth** is perfect for you. It has been designed for the most common case of authentication: the traditional login through a form and a database.

Some of its features:

* Login screen and user registration
* Verification of email when registering
* Password reset
* User Roles
* "Remind me" functionality based on cookies (optional)
* Access Control Lists (ACL) (optional)
* It works with all CodeIgniter database drivers
* Protection against brute force attacks during login (optional)
* Automatic definition of routes (With the method `Route::auth()`)
* Multiple templates available to choose from, translated into several languages

#### Luthier CI Authentication Framework: for advanced users

The **Luthier CI Authentication Framework** is a set of classes and interfaces that define the user authentication process abstractly. With it, you can perform the following tasks:

* Load of  **User Providers**
* Login by username and password
* Forced login (bypass) using a username
* Validation of authentication status
* Validation of user roles
* Validation of user permissions through Access Control Lists (ACL)
* Management of session variables related to user authentication
* Driver-based authentication

Note that the Library is the basis for authentication, but its implementation depends on you!












