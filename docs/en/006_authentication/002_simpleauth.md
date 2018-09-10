[//]: # ([author] Julio Cedeño)
[//]: # ([meta_description] With SimpleAuth you can add a login and user registration to your application in less than 5 minutes!)

# SimpleAuth

### Contents

1. [Introduction](#introduction)
2. [Installation](#installation)
   1. [Step 1: Copy the required files](#step-1-copy-required-files)
   2. [Step 2: Install the database](#step-2-install-the-database)
   3. [Step 3: Define the routes](#step-3-define-the-routes)
3. [SimpleAuth Controller](#simpleauth-controller)
   1. [Customize the user registration form](#signup-form-personalization)
4. [SimpleAuth Middleware](#simpleauth-middleware)
5. [SimpleAuth Library](#simpleauth-library)
   1. [Basic functions](#simpleauth-library-basic-functions)
      1. [Obtaining the current user](#obtaining-the-current-user)
      2. [Verify if a user is a guest (anonymous)](#verifying-if-user-is-guest)
      3. [Verify the role of a user](#verifying-the-user-role)
      4. [Verify the user's permissions](#verifying-the-user-permissions)
   2. [Access Control List (ACL) functions](#simpleauth-library-acl-functions)
   3. [Other functions](#simpleauth-library-other-functions)
6. [Views and translations](#views-and-translations)
   1. [Setting the SimpleAuth skin](#establishing-simpleauth-skin)
   2. [Setting the SimpleAuth language](#establishing-simpleauth-language)
   3. [Using your own views](#using-your-own-views)
7. [SimpleAuth configuration](#simpleauth-configuration)
   1. [General configuration](#general-configuration)
   2. [Enabling/Disabling features](#enabling-disabling-features)
   3. [Views configuration](#views-configuration)
   4. [Configuration of Access Control Lists (ACL)](#access-control-list-configuration)
   5. [Email onfiguration](#email-configuration)
   6. [Configuration of the "Remind me" functionality](#remember-me-functionality-configuration)
   7. [Database configuration](#database-configuration)

### <a name="introduction"></a> Introduction

With **SimpleAuth** you can add a login and user registration to your application in less than 5 minutes! SimpleAuth consists of a controller (`SimpleAuthController`), a middleware (`SimpleAuthMiddleware`), a library (`Simple_auth`) and other elements built from the **Luthier CI Authentication Framework**.
### <a name="installation"></a> Installation

As the installation is done through the `make` command of the [Built-in CLI Tools of Luthier CI](../cli#built-in-cli-tools), be sure to define these commands in your routing file `cli .php`:

```php
<?php
# application/routes/cli.php

Luthier\Cli::maker();      // 'luthier make' command
Luthier\Cli::migrations(); // 'luthier migrate' command
```

In addition, its necessary to configure correctly the connection to the database (in `application/config/database.php`) and the migrations (in `application/config/migration.php`) before starting.

#### <a name="step-1-copy-required-files"></a> Step 1: Copy the required files

Run in the root folder of your application:

```
php index.php luthier make auth
```

If everything goes well, you should have the following new files:

```
application
    |- config
    |   |- auth.php
    |
    |- controllers
    |   |- SimpleAuthController.php
    |
    |- libraries
    |   |- Simple_Auth.php
    |
    |- middleware
    |   |- SimpleAuthMiddleware.php
    |
    |- migrations
    |   |- 20180516000000_create_users_table.php
    |   |- 20180516000001_create_password_resets_table.php
    |   |- 20180516000002_create_email_verifications_table.php
    |   |- 20180516000003_create_login_attempts_table.php
    |   |- 20180516000004_create_user_permissions_categories_table.php
    |   |- 20180516000005_create_user_permissions_table.php
    |
    |- security
    |   |- providers
    |       |- User.php
    |       |- UserProvider.php
```

#### <a name="step-2-install-the-database"></a> Step 2: Install the database

Run in the root folder of your application:

```
php index.php luthier migrate
```

You should be able to see the following output:

```
MIGRATED: 20180516000000_create_users_table.php
MIGRATED: 20180516000001_create_password_resets_table.php
MIGRATED: 20180516000002_create_email_verifications_table.php
MIGRATED: 20180516000003_create_login_attempts_table.php
MIGRATED: 20180516000004_create_user_permissions_categories_table.php
MIGRATED: 20180516000005_create_user_permissions_table.php
```

#### <a name="step-3-define-the-routes"></a> Step 3: Define the routes

In your `web.php` file, add the following line:

```php
Route::auth();
```

Which is a shortcut to define all of these routes:

```php
Route::match(['get', 'post'], 'login', 'SimpleAuthController@login')->name('login');
Route::post('logout', 'SimpleAuthController@logout')->name('logout');
Route::get('email_verification/{token}', 'SimpleAuthController@emailVerification')->name('email_verification');
Route::match(['get', 'post'], 'signup', 'SimpleAuthController@signup')->name('signup');
Route::match(['get', 'post'], 'confirm_password', 'SimpleAuthController@confirmPassword')->name('confirm_password');
Route::group('password-reset', function(){
    Route::match(['get','post'], '/', 'SimpleAuthController@passwordReset')->name('password_reset');
    Route::match(['get','post'], '{token}', 'SimpleAuthController@passwordResetForm')->name('password_reset_form');
});
```

If you have followed all the steps correctly, when visiting the url `/login` you should see your new login screen:

<img src="https://ingenia.me/uploads/2018/06/11/simpleauth_login_screenshot.png" alt="SimpleAuth login screen" class="img-responsive center"/>

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Information about the session logout path</strong>
    <br />
    By default, the route <code>logout</code> only accepts POST requests, so a link to the url <code>/logout</code> wont work to close the session, unless an HTML form that points to that route is used. To allow GET requests, use <code>Route::auth(FALSE)</code>
</div>

### <a name="simpleauth-controller"></a> SimpleAuth Controller

The SimpleAuth controller (`SimpleAuthController`) contains authentication actions such as login, user registration, password reset, among others. It looks similar to this:

```php
<?php
# application/controllers/SimpleAuthController.php

defined('BASEPATH') OR exit('No direct script access allowed');

/* (...) */

class SimpleAuthController extends Luthier\Auth\SimpleAuth\Controller
{

    /**
     * Sign up form fields
     *
     * (...)
     */
    public function getSignupFields()
    {
        return [ /* (...) */ ];
    }

    /**
     * Fillable database user fields
     *
     * (...)

     * @access public
     */
    public function getUserFields()
    {
        return [ /* (...) */ ];
    }
}
```

Unless you want to customize SimpleAuth, you don't need to add anything else to this driver, since the class you extend (`Luthier\Auth\SimpleAuth\Controller`) already defines the authentication logic and, in your routing file, `Route::auth()` already defines all routes that should point here.

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Overwriting methods eliminates any base functionality</strong>
    <br/>
    It may seem obvious, but if you overwrite any method of the SimpleAuth driver you will lose the system of skins (topics), translated views, the user registration form constructor, and other useful functions that are pre-configured, described below
</div>

#### <a name="signup-form-personalization"></a> Customize the user registration form

You can change the fields of the registration form to your liking. To do this, the `getSignupFields()` method of your SimpleAuth driver must return an array that defines its structure, with the following syntax:

```php
public function getSignupFields()
{
    return [
        'Field name 1' => [
            'Field type',
            'Field label',
            [ /* HTML5 attributes array */ ],
            [ /* CI Validation rules array */] ,
            [ /* CI Validation error essages array (Optional)*/]
        ],
        'Field name 2' => [
            'Field type',
            'Field label',
            [ /* ... */ ],
            [ /* ... */ ] ,
        ],

        // ( ... )

        'Field name N' => [
            'Field type',
            'Field label',
            [ /* ... */ ],
            [ /* ... */ ] ,
        ]
    ];
}
```

On the other hand, the `getUserFields()` method of your SimpleAuth driver must return an array with the fields of that form that will be stored in the new user, where each element of the array matches both the field of that registration form and with the name of the column of the users table in your database:

```php
public function getUserFields()
{
    return [
        'first_name',
        'last_name',
        'username',
        'gender',
        'email',
        'password',
        'role',
    ];
}
```

Laravel users will notice that this is exactly the same behavior of the `$fillable` property of the EloquentORM models, but applied to the SimpleAuth user registration form.

### <a name="simpleauth-middleware"></a> SimpleAuth Middleware 

The SimpleAuth middleware (`SimpleAuthMiddleware`) is the first line of defense for routes that require user pre-authentication. This middleware is automatically responsible for verifying the current state of the user:

* If the user is **authenticated**, the request is still normal
* If the user is NOT **authenticated**, an attempt will be made to restore the session using the "Remember me" functionality (if activated)
* If its not possible to restore any previous session, the user will be redirected to the login screen

You can use the SimpleAuth middleware in as many routes and route groups as you want, and even combine it with your own middleware to add additional layers of security.

Example:

```php
<?php
# application/routes/web.php

// SimpleAuth default routes:

Route::auth();

// Public routes:

Route::get('/', 'FrontendController@homepage')->name('homepage');
Route::get('/about', 'FrontendController@about')->name('about');
Route::match(['get','post'], '/contact', 'FrontendController@contact')->name('contact');

// Protected routes: access here without being authenticated will direct to the
//                   login screen

Route::group('dashboard', ['middleware' => ['SimpleAuthMiddleware']], function(){
    Route::get('/', 'UserArea@dashboard');
});
```

### <a name="simpleauth-library"></a> SimpleAuth Library

The SimpleAuth library is a *wrapper* of the `Auth` class of the Luthier CI Authentication Framework, in the format of a native CodeIgniter library, so all its methods are available to you with a syntax that you should already know.

To start using the SimpleAuth Library, you must load it into the framework:

```php
$this->load->library('Simple_auth');
```

#### <a name="simpleauth-library-basic-functions"></a> Basic functions

*NOTE: Not all methods of the `Luthier\Auth` class are relevant when you are using SimpleAuth, so we only list those that may be useful*


##### <a name="obtaining-the-current-user"></a> Obtaining the current user

To obtain the user that is authenticated in your application, use the `user()` method, which returns a *user object*, or `NULL` if no authenticated user exists:

```php
// The current user object:
$userObject = $this->simple_auth->user();

// With the user object you have access to:
// ...the user entity of the database:
$user = $userObject->getEntity();

// ...their roles:
$roles = $userObject->getRoles();

// ...and its permissions:
$permissions = $userObject->getPermissions();
```

If you are using the default SimpleAuth User Provider, you can directly access the current user's data without having to use the `getEntity()` method. The following expressions are equivalent:

```php
$this->simple_auth->user()->getEntity()->first_name;

$this->simple_auth->user()->first_name;
```

##### <a name="verifying-if-user-is-guest"></a> Verify if a user is a guest (anonymous)

To quickly verify if a user is invited, use the `isGuest()` method, which returns `TRUE` if the user has NOT yet logged in, and` FALSE` otherwise:

```php
$this->simple_auth->isGuest();
```

##### <a name="verifying-the-user-role"></a> Verify the role of a user

To verify if a user has a specific role, use the method `isRole($role)`, which returns `TRUE` if the user has the role `$role`, or `FALSE` if he doesn't own it or there is no authenticated user:

```php
$this->simple_auth->isRole('ADMIN');
```

##### <a name="verifying-the-user-permissions"></a> Verify the user's permissions

To verify if a user has a specific permission, use the method `isGranted($permission)`, which returns `TRUE` if the user has the permission `permission`, or `FALSE` if it doesn't have it or there is no authenticated user

Example:

```php
$this->simple_auth->isGranted('general.read');
```

An alternative syntax is available, to verify if a user belongs to a role that begins with a specific phrase/category:

```php
// The following will give TRUE for permits that begin with 'general.'
$this->simple_auth->isGranted('general.*');
```

#### <a name="simpleauth-library-acl-functions"></a> Access Control List (ACL) functions

Access Control Lists (ACLs) are an optional authentication functionality used to set specific permissions to each authenticated user. A user can, therefore, have a role and several assigned permissions that guarantee (or deny) access to certain resources of the application.

In SimpleAuth there are no *user groups* or anything similar, user permissions are stored in a variable depth permission tree (the sub-permission limit depends on you).

Consider the following permissions:

```
ID      NAME        PARENT_ID
-----------------------------
1       general     [null]
2       read        1
3       write       1
4       delete      1
5       local       4
6       global      4
```

And this assignment of permissions:

```
ID      USERNAME    PERMISSION_ID
---------------------------------
1       anderson    2
2       anderson    5
3       julio       3
4       julio       6
```

When, for example, the user `anderson` logs in, you will have the following permissions:

```
general.read
general.delete.local
```

And when the user `julio` logs in, he will have the following permissions:

```
general.write
general.delete.global
```

The permission tree is stored in the `user_permissions_categories` table, while the permission assignments are stored in the `user_permissions` table, both created by the migrations that are included with SimpleAuth. There is no automated method to create or delete permissions, so you must do it manually.

---

These are the ACL functions available in the SimpleAuth library:

##### <a name="simpleauth-library-permissionsexists-method"></a> permissionsExists(*string* **$permission**) *: [bool]*

Verify that permission `$permission` exists in the table of the Access Control List (ACL).

Example:

```php
$this->simple_auth->permissionExists('general.read');
```

##### <a name="simpleauth-library-grantpermission-method"></a> grantPermission(*string* **$permission**, *string* **$username** = *NULL*) *: [bool]*

Assigns the permission `$permission` to the user `$username`, returning `TRUE` if the operation was successful or `FALSE` otherwise.

```php
// Assigning the 'general.read' permission to the current user
$this->simple_auth->grantPermission('general.read');
```

##### <a name="simpleauth-library-revokepermission-method"></a> revokePermission(*string* **$permission**, *string* **$username** = *NULL*) *: [bool]*

Revokes the permission `$permission` to the user `$username`, returning `TRUE` if the operation was successful or `FALSE` otherwise.

```php
// Revoking the 'general.read' permission to the current user
$this->simple_auth->revokePermission('general.read');
```

#### <a name="simpleauth-library-other-functions"></a> Other functions

The following functions are useful for special tasks related to user authentication:

##### <a name="simpleauth-library-isfullyauthenticated-method"></a> isFullyAutenticated() *: [bool]*

Returns `TRUE` if the user is fully authenticated, `FALSE` otherwise. A fully authenticated user is one who has logged in directly and NOT through the "Remember me" functionality.

##### <a name="simpleauth-library-promptpassword-method"></a> promptPassword(*string* **$route** = `'confirm_password'`) *: [bool]*

Redirects automatically to the path `$route` in case the user is not fully authenticated. This function is useful to request the authenticated user again through the "Remember me" function to confirm your password.

##### <a name="simpleauth-library-searchuser-method"></a> searchUser(*mixed* **$search**) *: [object|null]*

Returns an object with the user found under the criterion `$search`, or `NULL` in case it doesn't find any. Depending on the type of variable `$search`, this method performs three types of searches:

* **int**: It will search and return the user with the primary key that matches (configuration `simpleauth_id_col`)
* **string**: It will search and return the first user that matches the value of the column set for the user name during the login (configuration `simpleauth_username_col`)
* **array**: It is equivalent to the `where($search)` method of the CodeIgniter QueryBuilder.

Example:

```php
// It will search the user with ID 1
$this->simple_auth->searchUser(1);

// It will search the user with the username/email column equal to 'admin@admin.com'
$this->simple_auth->searchUser('admin@admin.com');

// It will search for the user whose column value 'gender' is 'm' and 'active' is equal to 1
$this->simple_auth->searchUser(['gender' => 'm', 'active' => 1]);
```

##### <a name="simpleauth-library-updateuser-method"></a> updateUser(*int|string* **$search**) *: [void]*

Updates the user found under the `$search` criterion. Depending on the type of variable `$search`, this method performs two different types of updates:

* **int**: Will search and update the first user with the value of the primary key that matches (configuration `simpleauth_id_col`)
* **string**: Will search and update the first user with the column value set for the username during the login that matches (configuration `simpleauth_username_col`)

Example:
```php
// It will replace the user's data with ID 1
$this->simple_auth->updateUser(1, ['first_name' => 'John']);

// It will replace the user's data with the user name / email column equal to 'admin@admin.com'
$this->simple_auth->searchUser('admin@admin.com', ['gender' => 'f']);
```

##### <a name="simpleauth-library-createuser-method"></a> createUser(*array* **$data**) *: [void]*

Create a new user in the database with the values of the `$data` array. Each index of the `$data` array corresponds to a column in the user table, defined in the `simpleauth_users_table` configuration

Example:

```php
$this->simple_auth->createUser(
    [
        'first_name' => 'Admin',
        'last_name'  => 'Admin',
        'username'   => 'admin',
        'email'      => 'admin@admin.com',
        'password'   => 'admin',
        'gender'     => 'm',
        'role'       => 'admin',
        'verified'   => 1
    ]
);
```

This function automatically creates the password hash if the name of the column matches the name set in the `simpleauth_password_col` configuration


### <a name="views-and-translations"></a> Views and translations

SimpleAuth gives you the possibility to choose between predetermined designs (skins) or use your own views. The designs included in SimpleAuth have the advantage of being translated into several languages. At the moment, the supported languages are the following:

* English
* Spanish

#### <a name="establishing-simpleauth-skin"></a> Setting the SimpleAuth skin

To change the skin used in the views, modify the `simpleauth_skin` option in the SimpleAuth configuration file:

```php
# application/config/auth.php

$config['simpleauth_skin'] = 'default';
```

#### <a name="establishing-simpleauth-language"></a> Setting the SimpleAuth language

The language used by the skins depends on the value of the `language` option (`$config['language']`) of the main configuration file of the framework (`application/config/config.php`). If the current language is not found among those supported by SimpleAuth, English (`english`) will be used.

#### <a name="using-your-own-views"></a> Using your own views

You can use your own views without having to overwrite SimpleAuth driver methods. In total, 6 views are used by SimpleAuth:

* **login.php**: Login view
* **signup.php**: User registration view
* **password_prompt.php**: Current password confirmation view ("Remind me" functionality)
* **password_reset.php**: View of the password reset request form
* **password_reset_form.php**: View of the password reset form
* **message.php**: View of a generic message

Therefore, to use your own views, just create a file with the name of the view to be replaced, inside a `simpleauth` folder (if it does not exist, you must create it first) in your `views` folder. For example:

```php
application/views/simpleauth/login.php
application/views/simpleauth/message.php
application/views/simpleauth/password_prompt.php
application/views/simpleauth/password_reset.php
application/views/simpleauth/password_reset_form.php
application/views/simpleauth/signup.php
```

### <a name="simpleauth-configuration"></a> SimpleAuth configuration

The SimpleAuth configuration is located in the `application/config/auth.php` file. Next, a brief explanation of each element:

#### <a name="general-configuration"></a> General configuration

* **auth_login_route**: *[string]* Login path. If you use the `Route::auth()` method to define SimpleAuth routes, this value will be ignored.
* **auth_logout_route**: *[string]* Logout path. If you use the `Route::auth()` method to define SimpleAuth routes, this value will be ignored.
* **auth_login_route_redirect**: *[string]* Redirect path in case of successful login.
* **auth_logout_route_redirect**: *[string]* Redirect path immediately after logout.
* **auth_route_auto_redirect**: *[array]* Routes that will activate an automatic redirection to the `auth_login_route_redirect` path in case the user is already authenticated.
* **auth_form_username_field**: *[string]* Name of the field of the login form corresponding to the username/email to authenticate.
* **auth_form_username_field**: *[string]* Name of the field of the login form corresponding to the user password to be authenticated.
* **auth_session_var**: *[string]* Name of the session variable used by the Luthier CI Authentication module.

#### <a name="enabling-disabling-features"></a> Enabling/Disabling features

* **simpleauth_enable_signup**: *[bool]* Activates the user registration form.
* **simpleauth_enable_password_reset**: *[bool]* Activates the password reset form.
* **simpleauth_enable_remember_me**: *[bool]* Activates the "Remember Me" function based on cookie.
* **simpleauth_enable_email_verification**: *[bool]* Activates email verification during the user registration process. For it to work it is necessary that the emails of the framework are correctly configured.
* **simpleauth_enforce_email_verification**: *[bool]* When this option is `TRUE`, SimpleAuth will deny login to users who don't have their verified email account.
* **simpleauth_enable_brute_force_protection**: *[bool]* Enables the defense of brute force login attacks.
* **simpleauth_enable_acl**: *[bool]* Activates the Access Control Lists (ACL)

#### <a name="views-configuration"></a> Views configuration

* **simpleauth_skin**: *[string]* Skin used in the views included by SimpleAuth. By default it is `default`.
* **simpleauth_assets_dir**: *[string]* Public URL relative to the application where the resources (css, js, etc) of the SimpleAuth views will be saved.

#### <a name="access-control-list-configuration"></a> Configuration of Access Control Lists (ACL)

* **simpleauth_acl_map**: *[array]* Associative arrangement with the names and IDs of categories and groups of permission categories used by the Access Control Lists. Configuring this drastically reduces the number of queries in the database, especially when you have a deep permission tree.

#### <a name="email-configuration"></a> Email configuration

* **simpleauth_email_configuration**: *[array | null]* Fix with the custom configuration that will be supplied during the initialization of the email library for SimpleAuth emails. Leave in `null` to use the same of the application.
* **simpleauth_email_address**: *[string]* Email address that will appear in the `from` field of emails sent by SimpleAuth.
* **simpleauth_email_name**: *[string]* Name that will appear next to the `from` field in emails sent by SimpleAuth.
* **simpleauth_email_verification_message**: *[string | null]* Automatic message with the instructions for email verification sent to the user after successful registration in the application. Leave it in `null` to use the default SimpleAuth message, which is translated into the current language of the application. Note: in order for messages containing HTML to be displayed correctly, the email library must be configured first.
* **simpleauth_password_reset_message**: *[string | null]* Automatic message with instructions for password reset. Leave in `null` to use the default SimpleAuth message translated into the current language of the application. Note: in order for messages containing HTML to be displayed correctly, the email library must be configured first.

#### <a name="remember-me-functionality-configuration"></a> Configuration of the "Remind me" functionality

* **simpleauth_remember_me_field**: *[string]* Field name of the login form corresponding to the "Remind me" functionality.
* **simpleauth_remember_me_cookie**: *[string]* Name of the cookie used for "Remind me" functionality.

#### <a name="database-configuration"></a> Database configuration

* **simpleauth_user_provider**: *[string]* User provider used by SimepleAuth.
* **simpleauth_users_table**: *[string]* Name of the table where users are stored.
* **simpleauth_users_email_verification_table**: *[string]* Name of the table where the email verification tokens are stored.
* **simpleauth_password_resets_table**: *[string]* Name of the table where the password reset tokens are stored.
* **impleauth_login_attempts_table**: *[string]* Name of the table where unsuccessful login attempts are stored, used for defense against brute force login attacks.
* **simpleauth_users_acl_table**: *[string]* Name of the table where the granted user permissions are stored, used by the Access Control Lists (ACL).
* **simpleauth_users_acl_categories_table**: *[string]* Name of the table where the permission tree used by the Access Control Lists (ACL) are stored.
* **simpleauth_id_col**: *[string]* Name of the identification column of the user table.
* **simpleauth_username_col**: *[string]* Name of the column corresponding to the username of the user table. This column is the one that will be used during the user authentication process.
* **simpleauth_email_col**: *[string]* Name of the column corresponding to the email of the user table. This column is the one that will be used for emails from the library.
* **simpleauth_email_first_name_col**: *[string]* Name of the column corresponding to the first name (or name) of the user table. This column is the one that will be used for emails from the library.
* **simpleauth_password_col**: *[string]* Name of the corresponding column the password in the user table. This column is the one that will be used during the user authentication process.
* **simpleauth_role_col**: *[string]* Name of the column corresponding to the role in the user table. This column will be used to check user roles in the library.
* **simpleauth_active_col**: *[string]* Name of the column corresponding to the user's status. In the database, it must be defined as a column of type INT, where the value `0` corresponds to a user **disabled** and` 1` to a user **activated**. Its used during the login session.
* **simpleauth_verified_col**: *[string]* Name of the column corresponding to the verification status of the user's email. In the database, it must be defined as a column of type INT, where the value `0` corresponds to a user **disabled** and` 1` to a user **activated**. Its used during the login session.
* **simpleauth_remember_me_col**: *[string]* Name of the column where the token used by the "Remember me" functionality is stored, if activated.


