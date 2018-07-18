[//]: # ([author] Julio Cedeño)
[//]: # ([meta_description] The Luthier CI Authentication Framework is a structure on which user authentication systems are built in CodeIgniter)

# Luthier CI Authentication Framework

### Contents

1. [Introduction](#introduction)
2. [Creation of User Providers](#creation-of-user-providers)
   1. [User instance](#user-instance)
   2. [Users load](#user-load)
   3. [Password hash and its verification](#password-hash-and-verification)
   4. [Validate that a user is active and verified](#validate-that-an-user-is-active-and-verified)
3. [Working with User Providers](#working-with-user-providers)
   1. [User login](#user-login)
   2. [Advanced user login](#advanced-user-login)
4. [Sessions](#sessions)
   1. [Storing a user in the session](#storing-an-user-in-the-session)
   2. [Retrieving a user from the session](#retrieving-an-user-from-the-session)
   3. [Custom session data](#custom-session-data)
   4. [Deleting the current session](#deleting-custom-session)
5. [User Operations](#user-operations)
   1. [Roles verification](#roles-verification)
   2. [Permissions verification](#permissions-verification)
6. [Controller-based authentication](#controller-based-authentication)
   1. [General configuration](#general-configuration)
   2. [The authentication controller](#the-authentication-controller)
   3. [The login form](#the-login-form)
   4. [Log out](#logout)
   5. [Authentication events](#authentication-events)

### <a name="introduction"></a> Introduction

The **Luthier CI Authentication Framework** is a structure on which user authentication systems are built in CodeIgniter. It offers answers to two major dilemmas: where users are obtained from and how they are available in your application.

During the process the **User Providers** are used. A User Provider is a class that is responsible for obtaining the authenticated user from somewhere, working as an intermediary between CodeIgniter and, for example, a database, an API or even an array of users loaded into memory.

This article is aimed at advanced users and with very specific authentication needs. If you are looking for a pre-configured and easy-to-use solution, consult the documentation of [SimpleAuth](./simpleauth) which, in fact, is an implementation of everything you will read below.

### <a name="creation-of-user-providers"></a> Creation of User Providers

All User Providers are stored in the `application/security/providers` folder. In addition, your classes must implement the `Luthier\Auth\UserProviderInterface` interface, which defines the following methods:

```php
public function getUserClass();

public function loadUserByUsername($username, $password = null);

public function hashPassword($password);

public function verifyPassword($password, $hash);

public function checkUserIsActive(UserInterface $user);

public function checkUserIsVerified(UserInterface $user);
```

Let's start by creating a file called `MyUserProvider.php`, which will be our first user Provider:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserProviderInterface;

class MyUserProvider implements UserProviderInterface
{

}
```

#### <a name="user-instance"></a> User instance

The **user instances** are a logical representation of the authenticated users: they contain (and return) all their details, roles and permissions. The first method that we must implement in our User Provider is `getUserClass()`, which returns the name of the **user instance** that will be used from now on.

Our user instance will be called `MyUser`, then:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserProviderInterface;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }
}
```

The next step is to create the class `MyUser`. The user instance files are saved in the `application/security/providers` folder. In order for Luthier CI to use them, both the name of the class and the name of the file must match the name returned in the `getUserClass()` method.

User instances must implement the `Luthier\Auth\UserInterface` interface, which defines the following methods:

```php
public function __construct($instance, $roles, $permissions);

public function getEntity();

public function getUsername();

public function getRoles();

public function getPermissions();
```

Implementing all these methods, our `MyUser` class looks like this:

```php
<?php
# application/security/providers/MyUser.php

use Luthier\Auth\UserInterface;

class MyUser implements UserInterface
{
    private $user;

    private $roles;

    private $permissions;

    public function __construct($entity, $roles, $permissions)
    {
        $this->user        = $entity;
        $this->roles       = $roles;
        $this->permissions = $permissions;
    }

    public function getEntity()
    {
        return $this->user;
    }

    public function getUsername()
    {
        return $this->user->email;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }
}
```

And our file structure like this:

```
application
    |- security
    |   |- providers
    |       | - MyUserProvider.php
    |       | - MyUser.php
```

#### <a name="user-load"></a>  Users load

Users must be obtained from somewhere, and the following method to implement, `loadUserByUsername()`, fulfills that function.

There are only two possible results of the user load:

* **Successful load**: A matching username and password was found. The User Provider returns an object that represents the user.
* **Failed load**: No user found. Any of the following exceptions are thrown: `UserNotFoundException`, `InactiveUserException`, `UnverifiedUserException` or `PermissionNotFoundException`.

The simplest example is to declare an array within the same User Provider, which contains the available users:

```php
$users = [
    [
        'name'      => 'John Doe',
        'email'     => 'john@doe.com',
        'password'  => 'foo123',
        'active'    => 1,
        'verified'  => 1,
    ],
    [
        'name'      => 'Alice Brown',
        'email'     => 'alice@brown.com',
        'password'  => 'bar456',
        'active'    => 1,
        'verified'  => 1,
    ]
];
```

Having said that, we will update the code with the following:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => 'foo123',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => 'bar456',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if($user->password != $password)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }
}
```

Now our User Provider is able to search the array and return a *user object* in case of a match, or throw a `UserNotFoundException` exception if no user is found.

However, as a general rule, passwords don't usually (and shouldn't) be stored directly. Instead, a *hash* generated with a one-way encryption algorithm is stored.

Consider this new user array:

```php
$users = [
    [
        'name'      => 'John Doe',
        'email'     => 'john@doe.com',
        'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
        'active'    => 1,
        'verified'  => 1,
    ],
    [
        'name'      => 'Alice Brown',
        'email'     => 'alice@brown.com',
        'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
        'active'    => 1,
        'verified'  => 1,
    ]
];
```

The passwords of each one are still exactly the same, the difference is that now the stored is your *hash* and not the password in plain text, so a comparison `$user-> password == $password` is not enough.

#### <a name="password-hash-and-verification"></a> Password hash and its verification

The following methods to implement are responsible for generating and validating password hashes in the User Provider:

* `hashPassword()`: *[string]* receives a password in plain text and returns its hash

* `verifyPassword()`: *[bool]* receives a password in plain text and password hash, validating that they match

The logic and implementation is at the discretion of the developer. In our case, we will use the `blowfish` algorithm, leaving the code like this:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if(!$this->verifyPassword($password, $user->password))
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
```

You have probably noticed that the `$password` argument of the `loadUserByUsername()`method must be defined as optional. This is so because at the beginning of each request, Luthier CI tries to reload the last authenticated user with its user Provider, and this is only possible if it is possible to obtain users from a relatively safe data store in the session, such as your *id* or *username*.

Therefore, we must modify our code a bit to ensure that the User Provider is still able to obtain users even if no password is provided:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if($password !== NULL)
        {
            if(!$this->verifyPassword($password, $user->password))
            {
                throw new UserNotFoundException('Invalid user credentials!');
            }
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
```

<div class="alert alert-danger">
    <i class="fa fa-times" aria-hidden="true"></i>
    <strong>Don't use the md5() or sha1() functions for password hashes</strong>
    <br />
    These algorithms are very efficient and anyone (with a modern computer and enough free time) can try to "break" the encryption by brute force. There is a section that talks about <a href="http://php.net/manual/es/faq.passwords.php">password hashes</a> in the documentation of PHP, and is without a doubt a mandatory reading for those who worry about this important aspect of security.
</div>


#### <a name="validate-that-an-user-is-active-and-verified"></a> Validate that a user is active and verified

All that remains are to implement the methods `checkUserIsActive()` and `checkUserIsVerified()` which, as their names suggest, validate that a user is *active* and their information is *verified*.

The criteria for a user to be *active* and *verified* is of your choice. In our case, for a user to be *active* its value `active` must be equal to` 1`, and for it to be *verified* its value `verified` must be equal to `1` as well.

By implementing both methods, our User Provider now looks like this:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if($password !== NULL)
        {
            if(!$this->verifyPassword($password, $user->password))
            {
                throw new UserNotFoundException('Invalid user credentials!');
            }
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    final public function checkUserIsActive(UserInterface $user)
    {
        /*
         * The getEntity() method is used to return an array / object / entity with the
         * user data. In our case, it is an object, so we can use
         * the following chained syntax:
         */
        if($user->getEntity()->active == 0)
        {
            throw new InactiveUserException();
        }
    }


    final public function checkUserIsVerified(UserInterface $user)
    {
        /*
         * The same here:
         */
        if($user->getEntity()->verified == 0)
        {
            throw new UnverifiedUserException();
        }
    }
}
```

Done! You've already created your first user Provider and your attached User Instance. You are ready to authenticate users.

### <a name="working-with-user-providers"></a> Working with User Providers

The first thing you should do before using a User Provider is to upload it to your application. To do this, use the static `loadUserProvider()` method of the `Auth` class.

For example, to load the previous User Provider, the syntax is as follows: 

```php
$myUserProvider = Auth::loadUserProvider('MyUserProvider');
```

#### <a name="user-login"></a> User login

To perform a login, use the `loadUserByUsername()` method of your User Provider, where the first argument is the username/email and the second is your password:

```php
// We load a User Provider:
$myUserProvider = Auth::loadUserProvider('MyUserProvider');

// Returns the user object corresponding to 'john@doe.com':
$john = $myUserProvider->loadUserByUsername('john@doe.com', 'foo123');

// Returns the corresponding user object to 'alice@brown.com':
$alice = $myUserProvider->loadUserByUsername('alice@brown.com', 'bar456');
```

The User Providers are designed in such a way that it is also possible to log in only with the username/email:

```php
$alice = $myUserProvider->loadUserByUsername('alice@brown.com');
```

Any error during the login will produce an exception, which should be caught and handled as the case may be:

```php
// ERROR: The password of john@doe.com is incorrect!
// (An exception 'UserNotFoundException' will be thrown)
$jhon = $myUserProvider->loadUserByUsername('john@doe.com', 'wrong123');

// ERROR: The user anderson@example.com doesn't exist!
// (An exception 'UserNotFoundException' will be thrown)
$anderson = $myUserProvider->loadUserByUsername('anderson@example.com', 'test123');
```

#### <a name="advanced-user-login"></a> Advanced user login

The fact that the User Provider returns a user doesn't mean that they are really authorized to log in. The `checkUserIsActive()` and `checkUserIsVerified()` methods add convenient additional checks.

Consider the following array of users:

```php
$users = [
    [
        'name'      => 'Alex Rodriguez',
        'email'     => 'alex@rodriguez.com',
        'password'  => '$2y$10$2nXHy1LyNL217hfyINGKy.Ef5uhxa1FdmlMDw.nbGOkSEJtT6IJWy',
        'active'    => 0,
        'verified'  => 1,
    ],
    [
        'name'      => 'Alice Brown',
        'email'     => 'alice@brown.com',
        'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
        'active'    => 1,
        'verified'  => 0,
    ],
    [
        'name'      => 'Jessica Hudson',
        'email'     => 'jessica@example.com',
        'password'  => '$2y$10$IpNrG1VG53DrborE4Tl6LevtVgVfoO9.Ef9TBVgH9I10DLRnML9gi',
        'active'    => 1,
        'verified'  => 1,
    ],
];
```

And the following login code:

```php
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

function advanced_login($username)
{
    $myUserProvider = Auth::loadUserProvider('MyUserProvider');

    try
    {
        $user = $myUserProvider->loadUserByUsername($username);
                $myUserProvider->checkUserIsActive($user);
                $myUserProvider->checkUserIsVerified($user);
    }
    catch(UserNotFoundException $e)
    {
        return 'ERROR: User not found!';
    }
    catch(InactiveUserException $e)
    {
        return 'ERROR: Inactive user!';
    }
    catch(UnverifiedUserException $e)
    {
        return 'ERROR: Unverified user!';
    }

    return 'OK: Login success!';
}

var_dump( advanced_login('alex@rodriguez.com') );  // ERROR: Inactive user!
var_dump( advanced_login('alice@brown.com') );     // ERROR: Unverified user!
var_dump( advanced_login('jack@grimes.com') );     // ERROR: User not found!
var_dump( advanced_login('jessica@example.com') ); // OK: Login success!
```

Although `alex@rodriguez.com` and `alice@brown.com` exist within the user array, according to the user provider the first one is inactive and the second is not verified, and as the user `jack@grimes .com` doesn't exist, the only user that can log in is `jessica@example.com`.

So that you don't have to define the `advanced_login` function again and again in your applications, there are already two methods that do the same thing: `Auth::attempt()` and `Auth::bypass()`, the first one is used for logins by username and password and the second for logins by username only.

Except for the handling of exceptions, the following expressions are equivalent to the previous code:

```php
Auth::bypass('alex@rodriguez.com', 'MyUserProvider');
Auth::bypass('alice@brown.com', 'MyUserProvider');

Auth::attempt('alex@rodriguez.com', 'foo123', 'MyUserProvider');
Auth::attempt('alice@brown.com', 'bar456', 'MyUserProvider');
```

### <a name="sessions"></a> Sessions

What is the use of being able to log in if the authenticated user doesn't persist in browsing? The `Auth` class includes functions for storing and obtaining users in the session.

#### <a name="storing-an-user-in-the-session"></a> Storing a user in the session

To store a user in the session, use the static method `store()`:

```php
$alice = $myUserProvider->loadUserByUsername('alice@brown.com');
Auth::store($alice);
```

This will save the authenticated user during the rest of the navigation, as long as you don't delete the session or it expires.

####  <a name="retrieving-an-user-from-the-session"></a> Retrieving a user from the session

To obtain the user stored in the session, use the static method `user()`:
```php
$alice = Auth::user();
```

This method returns an object of **user instance**, or `NULL` if no user is stored. 

Example:

```php
$alice = Auth::user();

// The user entity
// (The returned value depends on the User Provider, although the most common is that it is an object)
$alice->getEntity();

// An array with the roles that the User Provider has assigned to the user
$alice->getRoles();

// An array with the permissions that the User Provider has assigned to the user
$alice->getPermissions();
```

You can check if a user is anonymous (or invited) using the static method `isGuest()`:

```php
if( Auth::isGuest() )
{
    echo "Hi Guest!";
}
else
{
    echo "Welcome " . Auth::user()->getEntity()->name . "!";
}
```

#### <a name="custom-session-data"></a> Custom session data

To obtain and store your own session data (related to authentication) in one place, use the static method `session()`, whose first argument is the name of the value to be stored, and the second argument is the assigned value.

Example:

```php
// Store a value
Auth::session('my_value', 'foo');

// Get a value
$myValue = Auth::session('my_value');
var_dump( $myValue ); // foo

// Get ALL stored values
var_dump( Auth::session() ); // [ 'my_value' => 'foo' ]
```

#### <a name="deleting-custom-session"></a> Deleting the current session

To remove ALL data from the current authentication session (including the authenticated user that is currently stored) use the static method `destroy`:

```php
Auth::destroy();
```

### <a name="user-operations"></a> Users operations

There are two operations available to perform with authenticated users: the verification of roles and the verification of permissions.

#### <a name="roles-verification"></a> Roles verification 

To verify that a user has a certain role, use the static method `isRole()`, whose first argument is the name of the role to verify:

```php
Auth::isRole('user');
```

You can supply a different user object to the one stored in session as a second argument:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isRole('admin', $user);
```

#### <a name="permissions-verification"></a> Permissions verification 

To verify that the user has a specific permission, use the static method `isGranted()`, whose first argument is the name of the permission to verify:

```php
Auth::isGranted('general.read');
```

You can supply a different user object to the one stored in session as a second argument:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isGranted('general.read', $user);
```

### <a name="controller-based-authentication"></a> Controller-based authentication

So far you have seen the elements of the Luthier CI Authentication Framework working separately. The good news is that you can make them work together! and all thanks to a methodology that we call **Controller-based authentication**.

Controller-based authentication consists of the implementation of two interfaces, one in a controller and the other in a middleware, both of your choice, that automate the user authentication process.

#### <a name="general-configuration"></a> General configuration

You can create (although not required) a file called `auth.php` inside the `config` folder of your application to configure the options for Driver-based Authentication. The meaning of each option is explained in the [SimpleAuth documentation](./simpleauth#general-configuration)

This is an example of a configuration file:

```
<?php
# application/config/auth.php

$config['auth_login_route']  = 'login';

$config['auth_logout_route'] = 'logout';

$config['auth_login_route_redirect'] = 'dashboard';

$config['auth_logout_route_redirect'] = 'homepage';

$config['auth_route_auto_redirect'] = [];

$config['auth_form_username_field'] = 'email';

$config['auth_form_password_field'] = 'password';

$config['auth_session_var'] = 'auth';
```

If the file doesn't exist, the default configuration will be used, as exposed above.

#### <a name="the-authentication-controller"></a> The authentication controller

An authentication driver is any CodeIgniter controller that implements the `Luthier\Auth\ControllerInterface` interface, which defines the following methods:

```php
public function getUserProvider();

public function getMiddleware();

public function login();

public function logout();

public function signup();

public function emailVerification($token);

public function passwordReset();

public function passwordResetForm($token);
```

Let's start by creating a controller called `AuthController.php`, which implements all the required methods:

```php
<?php
# application/controllers/AuthController.php

defined('BASEPATH') OR exit('No direct script access allowed');

use Luthier\Auth\ControllerInterface;

class AuthController extends CI_Controller implements ControllerInterface
{
    public function getUserProvider()
    {
        return 'MyUserProvider';
    }

    public function getMiddleware()
    {
        return 'MyAuthMiddleware';
    }

    public function login()
    {
        $this->load->view('auth/login.php');
    }

    public function logout()
    {
        return;
    }

    public function signup()
    {
        $this->load->view('auth/signup.php');
    }

    public function emailVerification($token)
    {
        $this->load->view('auth/email_verification.php');
    }

    public function passwordReset()
    {
        $this->load->view('auth/password_reset.php');
    }

    public function passwordResetForm($token)
    {
        $this->load->view('auth/password_reset_form.php');
    }
}
```

The values returned by the `getUserProvider()` and `getMiddleware()` methods correspond to the **User Provider** and the middleware with the **authentication events** that will be used during the process that follows. In the case of the User Provider, it will be the same as the previous examples, `MyUserProvider`:

```php
public function getUserProvider()
{
    return 'MyUserProvider';
}
```

While for the middleware with the **authentication events** one will be used called `MyAuthMiddleware` (which does not yet exist) and which we will talk about later:

```php
public function `getMiddleware()
{
    return 'MyAuthMiddleware';
}
```

The `login()` and `logout()` methods defines the start and end of the session. When a user logs in, the request will be intercepted and handled automatically by Luthier CI, so that in our controller we only have to show a view with the login form:

```php
public function login()
{
    $this->load->view('auth/login.php');
}
```

The logout will also be handled by Luthier CI, so our `logout()` method does absolutely nothing at the controller level:

```php
public function logout()
{
    return;
}
```

The implementation of the remaining methods depends on you, but we give you an idea of what their functions should be:

* **signup()**: Method with all the logic of the user registry. Here you must show a registration form and process it (save the user in a database, etc.)

* **emailVerification(** *string* `$token` **)**: Its responsible for verifying the email of a newly registered user. Typically, you have been sent an email containing a link with a *verification token* (`$token`) over here.

* **passwordReset()**: Displays a form for password reset.

* **passwordResetForm(** *string* `$token` **)**: Check a password reset request. Almost always it is an email that is sent to the user and that contains a link to here with a *password reset token* (`$token`)

#### <a name="the-login-form"></a> The login form

Our `login()` method refers to a view called `auth/login.php`. Let's create it:

```html
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in</title>
</head>
<body>
    <h1>Log in</h1>
    <form method="post">
        <input type="text" name="username" required />
        <input type="password" name="oasswird" required />
        <button type="submit">Log in</button>
    </form>
</body>
</html>
```

Then, we have to add the following path in our `web.php` file:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
```

When accessing the url `/login` the login form that we have created must appear:

<p align="center">
    <img src="https://ingenia.me/uploads/2018/06/18/luthier-ci-login-screen.png" alt="Login screen" class="img-responsive" />
</p>


You can obtain an arrangement with the errors that occurred during the authentication process and use it in your views to inform the user. Use the `Auth::messages()` method, as you will see below:

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in</title>
</head>
<body>
    <h1>Log in</h1>
    <?php

        // We will help ourselves with an array with the translations of the returned
        // error codes (they are always the same)

        $errorMessages = [
            'ERR_LOGIN_INVALID_CREDENTIALS' => 'Incorrect email or password',
            'ERR_LOGIN_INACTIVE_USER'       => 'Inactive user',
            'ERR_LOGIN_UNVERIFIED_USER'     => 'Unverified user',
        ];
    ?>

    <?php foreach(Auth::messages() as $type => $message){ ?>
        <div class="alert alert-<?= $type ;?>">
            <?= $errorMessages[$message] ;?>
        </div>
    <?php } ?>

    <form method="post">
        <input type="email" name="email" required />
        <input type="password" name="password" required />
        <button type="submit">Log in</button>
    </form>
</body>
</html>
```

Your login form is ready!

Feel free to try any combination of username/password available in your User Provider. When you log in, you will be redirected to the path that you have defined in the option `$config ['auth_login_route_redirect']`, or if there is no such path, to the root url of your application.

#### <a name="logout"></a> Log out

Now we are going to configure the logoff. The only thing that is needed is to define the route that will be used, and by default it will be the one you called `logout`:

```php
Route::get('logout', 'AuthController@logout')->name('logout');
```

Our file of routes will be, finally, similar to this:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
Route::get('logout', 'AuthController@logout')->name('logout');
```

#### <a name="authentication-events"></a> Authentication events

Do you remember the `getMiddleware()` method of our controller? Returns the name of a *special* middleware: the middleware with the **authentication events**.

We are going to create a middleware called `MyAuthMiddleware` that extends the abstract class `Luthier\Auth\Middleware` and that, by implementing all the required methods, it will look like this:

```php
<?php
# application/middleware/MyAuthMiddleware.php

defined('BASEPATH') OR exit('No direct script access allowed');

use Luthier\Route;
use Luthier\Auth\UserInterface;

class MyAuthMiddleware extends Luthier\Auth\Middleware
{
    public function preLogin(Route $route)
    {
        return;
    }

    public function onLoginSuccess(UserInterface $user)
    {
        return;
    }

    public function onLoginFailed($username)
    {
        return;
    }

    public function onLoginInactiveUser(UserInterface $user)
    {
        return;
    }

    public function onLoginUnverifiedUser(UserInterface $user)
    {
        return;
    }

    public function onLogout()
    {
        return;
    }
}
```

Each method corresponds to an authentication event, explained below:

* **preLogin**: Event triggered when the user visits the login path, regardless of whether logs in or not.
* **onLoginSuccess**: Event triggered immediately after a successful login session, and before the redirect that follows.
* **onLoginFailed**: Event triggered after a failed session attempt, and before the redirect that follows.
* **onLoginInactiveUser**: Event triggered if an `InactiveUserException` exception is thrown within the User Provider, corresponding to an inactive user login error.
* **onLoginUnverifiedUser**: Event triggered if an `UnverifiedUserException` exception is thrown inside the User Provider, corresponding to an error by login of an unverified user.
* **onLogout**: Event triggered immediately after the user closes session.








