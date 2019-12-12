# Luthier CI Authentication Framework

The **Luthier CI Authentication Framework** is an architecture to build authentication systems within CodeIgniter.

It offers answers to two big dilemmas: where users are obtained and how they are available in your application.

During the process, the **User Providers** are used. A User Provider is a class that is responsible for obtaining from somewhere the user who intends to authenticate, functioning as an intermediary between CodeIgniter and, for example, a database, an API or even an in-memory user array.

This article is aimed at advanced users with very specific needs. If you are looking for a ready-to-use solution, see the [SimpleAuth](./simpleauth?relative_url=..%2Fsimpleauth) documentation. SimpleAuth is, in fact, an implementation of everything you will read next.

<!-- %index% -->

### Creation of User Providers

All User Providers are saved in the `application/security/providers` folder. In addition, your classes must implement the `Luthier\Auth\UserProviderInterface` interface, which defines the following methods:

```php
public function getUserClass();

public function loadUserByUsername($username, $password = null);

public function hashPassword($password);

public function verifyPassword($password, $hash);

public function checkUserIsActive(UserInterface $user);

public function checkUserIsVerified(UserInterface $user);
```

Let's start by creating a file called `MyUserProvider.php`, which will be our first User Provider:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserProviderInterface;

class MyUserProvider implements UserProviderInterface
{

}
```

#### User instances

**User Instances** are a logical representation of authenticated users: they contain (and return) all their details, roles and permissions.

The first method that we must implement in our User Provider is `getUserClass()`, which returns the name of the **User Instance** that will be used from now on.

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

The next step is to create the `MyUser` class. User Instance files are saved in the `application/security/providers` folder too. Both the class name and the file name must match.

User instances must implement the `Luthier\Auth\UserInterface` interface, which defines the following methods:

```php
public function __construct($instance, $roles, $permissions);

public function getEntity();

public function getUsername();

public function getRoles();

public function getPermissions();
```


Implementing all these methods, `MyUser` class looks like this:

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

#### User load

Users must be obtained from somewhere, and the following method to be implemented, `loadUserByUsername()`, does that work.

There are two possible results of user load process:

* **Load success**: User found. An object that represents the user is returned.
* **Load failed**: No user found. Any of the following exceptions are thrown: `UserNotFoundException`, `InactiveUserException`, `UnverifiedUserException` or `PermissionNotFoundException`.

The simplest example is to declare an array within the User Provider:

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

Let's update the User Provider code with the following:

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

Now our User Provider is able to search the array and return a user object in case there is a match, or throw a `UserNotFoundException` otherwise.

However, as a rule, passwords should not be stored as plain text. Instead, a hash generated with a one-way encryption algorithm should be used.

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

The passwords of each user remain the same but now is the password hash what is stored, so a `$user->password == $password` comparison will not work.

#### Password Hash and its verification

The following methods to implement are responsible for generating and validating password hashes in the User Provider:

* hashPassword($password): receives a plain text `$password` and returns its hash.
* verifyPassword($password, $hash): receives a plain text `$password` and a `$hash` password hash, and returns `TRUE` if the password corresponds to the hash, or `FALSE` otherwise.

Hashing implementation is at the discretion of the developer. In our case, we will use the `blowfish` algorithm, so the code will look like this:

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

You may have noticed that the `$password` argument of `loadUserByUsername()` method must be defined as optional. This is because at the time of processing incoming HTTP requests Luthier CI tries to reload the last authenticated user with its User Provider, and this is only possible if it is possible to obtain users from a relatively secure data of Store in the session, as an *id* or u*sername*.

Therefore, we must modify our code a bit to ensure that the User Provider is still able to obtain users even if no password is supplied:

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
    <strong>Do not use the md5() or sha1() functions for the password hash</strong><br />
    These algorithms are very efficient, so break the encryption by brute force is relatively simple. See the <a href="http://php.net/manual/es/faq.passwords.php">PHP documentation</a> for more details.
</div>

#### Validating that a user is active and verified

Only the `checkUserIsActive()` and `checkUserIsVerified()` methods are remain to implement. As their names suggest, the first validates that a user is *active*, and the second that is *verified*.

The criteria for a user to be *active* and *verified* is of your choice. In our case, `1` for active users, and `1` for verified users as well.

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
         * El método getEntity() se usa para devolver un arreglo/objeto/entidad con los
         * datos del usuario. En nuestro caso, es un objeto, por lo que podemos usar
         * la siguiente sintaxis encadenada:
         */
        if($user->getEntity()->active == 0)
        {
            throw new InactiveUserException();
        }
    }


    final public function checkUserIsVerified(UserInterface $user)
    {
        /*
         * Lo mismo aquí:
         */
        if($user->getEntity()->verified == 0)
        {
            throw new UnverifiedUserException();
        }
    }
}
```

Done! You have already created your first User Provider and its attached User Instance. You are ready to authenticate users.

### Working with User Providers

The first thing you should do before using a User Provider is to load it into your application. To do this, use the `Auth::loadUserProvider()` method:

```php
$myUserProvider = Auth::loadUserProvider('MyUserProvider');
```

#### Login

To perform a login, use the `loadUserByUsername($username, $password)` method of your User Provider:

```php
$myUserProvider = Auth::loadUserProvider('MyUserProvider');

$john = $myUserProvider->loadUserByUsername('john@doe.com', 'foo123');

$alice = $myUserProvider->loadUserByUsername('alice@brown.com', 'bar456');
```

Any error during the login will throw an exception, which should be caught and handled:

```php
// A 'UserNotFoundException' will be thrown
$jhon = $myUserProvider->loadUserByUsername('john@doe.com', 'wrong123');

// A 'UserNotFoundException' will be thrown
$anderson = $myUserProvider->loadUserByUsername('anderson@example.com', 'test123');
```

#### Advanced Login

The `checkUserIsActive()` and `checkUserIsVerified()` methods add additional login checks.

Consider the following user array:

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

And the following code:

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

Although `alex@rodriguez.com` and `alice@brown.com` exist within the user array, according to the User Provider the first one is *inactive* and the second one is *unverified*, and since the user jack@grimes.com does not exist, The only user who can log in is `jessica@example.com`.

So you don't have to define the `advanced_login` function over and over again in your applications, there are already two methods that do the same: `Auth::attempt() and `Auth::bypass()`. The first one is used for logins by name username and password and the second one for logins by username only:

```php
Auth::bypass('alex@rodriguez.com', 'MyUserProvider');
Auth::bypass('alice@brown.com', 'MyUserProvider');

Auth::attempt('alex@rodriguez.com', 'foo123', 'MyUserProvider');
Auth::attempt('alice@brown.com', 'bar456', 'MyUserProvider');
```

### Sessions

The `Auth` class includes functions for storing and obtaining users in the session.

#### Storing a user in the session


To store a user in the session, use the `Auth::store($user)` method, where `$user` is a **User Instance**:

```php
$alice = $myUserProvider->loadUserByUsername('alice@brown.com');
Auth::store($alice);
```

#### Getting a user from the session

To get the user stored in the session, use the `Auth::user()` method, that returns a **User Instance** object, or `NULL` if no authenticated user is stored in the session.

```php
$currentUser = Auth::user();
```

You can check if a user is anonymous (guest) by using the `Auth::isGuest()` method:

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

#### Custom session data

To get and store your custom session data, use the method `Auth::session($name, $value)`, where `$name` is the sesion variable name, and `$value` the value to assign:

Example:

```php
// Store a value
Auth::session('my_value', 'foo');

// Get a value
$myValue = Auth::session('my_value');

// Get ALL stored values
var_dump( Auth::session() );
```

#### Deleting the current session

To delete ALL data from the current session, use the `Auth::destroy()` method:

```php
Auth::destroy();
```

### Operations with users

There are two operations available to perform with authenticated users: **role verification** and **permission verification**.

#### Role verification

To verify that a user has a role, use the `Auth::isRole($role)` method, where `$role` is the role name:

```php
Auth::isRole('user');
```

A custom user object can be supplied as a second argument:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isRole('admin', $user);
```

#### Permission verification

To verify that a user has a permission, use the `Auth::isGranted($permission)` method, where `$permission` permission name:

```php
Auth::isGranted('general.read');
```

A custom user object can be supplied as a second argument:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isGranted('general.read', $user);
```

### Controller-based Authentication

So far you have seen the elements of the Luthier CI Authentication Framework working separately. The good news is that you can make them work together! thanks to a methodology called **Controller-based Authentication**.

Controller-based Authentication consists of the implementation of two interfaces, one in a controller and another in a middleware, both of your choice, that automate the user authentication process.

#### General configuration

You can create (although not mandatory) a  `auth.php` file inside your application's `config` folder to configure the Controller-based Authentication options:

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

<div class="alert alert-info">
    Si no existe el archivo se usará la configuración predeterminada, expuesta arriba.
</div>

#### Authentication controller

An **Authentication controller** is any CodeIgniter controller that implements the `Luthier\Auth\ControllerInterface` interface, which defines the following methods:

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

Let's start by creating an `AuthController.php` controller that implements all the interface required methods:

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

The values ​​returned by the `getUserProvider()` and `getMiddleware()` methods correspond to the **User Provider** and the middleware with the **authentication events** that will be used during the process that follows. In the case of the User Provider, we will use the same as the previous examples, `MyUserProvider`:


```php
public function getUserProvider()
{
    return 'MyUserProvider';
}
```

The middleware with the **authentication events** will be `MyAuthMiddleware` (does not exist yet) and which we will talk about later:

```php
public function `getMiddleware()
{
    return 'MyAuthMiddleware';
}
```

The `login()` and `logout()` methods defines the login and logout logic. When a user submits the login form, the request is intercepted and handled automatically by Luthier CI, so we only need to render a view here:


```php
public function login()
{
    $this->load->view('auth/login.php');
}
```

The logout will also be handled by Luthier CI, so our `logout()` method does nothing:

```php
public function logout()
{
    return;
}
```

The implementation of the remaining methods is up to you, but we give you an idea of ​​what their functions should be:

| Method | Work that does |
| :--- | :--- |
| **signup()** | The logic for user signup. Here you must show a registration form and process it (save the user in a database, etc.) |
| **emailVerification($token)** | Verifies the email of a newly registered user with a `$token` *verification token*, normally sent by email as a link |
| **passwordReset()** | Displays a password reset form |
| **passwordResetForm($token)** | Performs a password reset, after validating the `$token` *password reset token*, normally sent by email as a link to the user |

#### The login form

Our `login()` method refers to a `auth/login.php` view, let's create it:

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

Then, we add the following path in our `web.php` file:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
```

When accessing the `/login` url, the login form must appear.

You can get an array with the errors occurred during the authentication process with the `Auth::messages()` method, and use it in your views to inform to the user:

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

Your login form is ready! Feel free to try any username/password combination available in your User Provider. 

<div class="alert alert-info">
    When you log in, you will be redirected to the path you have defined in the <code>$config['auth_login_route_redirect']</code> option or, in case there is no such route, to the root url of your application.
</div>

#### Log out

Now we are going to configure the logout. All that is needed is to define the route that will be used, and by default it will be the one you have called `logout`:

```php
Route::get('logout', 'AuthController@logout')->name('logout');
```

Our route file will be, finally, similar to this:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
Route::get('logout', 'AuthController@logout')->name('logout');
```

#### Authentication events

Do you remember the `getMiddleware()` method of our controller? Returns the name of a special middleware: the middleware with **authentication events**.

We are going to create a middleware called `MyAuthMiddleware`, that extends the `Luthier\Auth\ Middleware` abstract class. After implementing all the required methods, will look like this:

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

Each method corresponds to an authentication event:

| Event | Description |
| :--- | :--- |
| **preLogin** | Event activated when the user visits the login route, regardless of whether or not they submit the login form or not |
| **onLoginSuccess** | Event activated immediately after a successful login, and before the redirection that follows |
| **onLoginFailed** | Event activated after a failed login attempt, and before the redirection that follows |
| **onLoginInactiveUser** | Event activated if an `InactiveUserException` exception is thrown within the User Provider |
| **onLoginUnverifiedUser** | Event activated if an `UnverifiedUserException` exception is thrown within the User Provider |
| **onLogout** | Event activated after the user logs off |