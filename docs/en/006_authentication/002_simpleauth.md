# SimpleAuth

With SimpleAuth you can add a customizable and ready-to-use login form in CodeIgniter.

SimpleAuth consists of a controller (`SimpleAuthController`) a middleware (`SimpleAuthMiddleware`) and a library (`Simple_auth`) all built with the **Luthier CI Authentication Framework**.

<!-- %index% -->

### SimpleAuth installation

The installation is done through the `make` command of the [Luthier CI Built-in CLI Tools](../cli#undef?relative_url=..%2Fcli%23undef). It is necessary to configure a connection to a database (in `application/config/database.php`) and activate the migrations (in `application/config/migration.php`) before proceeding with the installation.

#### Step 1: Copy the necessary files

Open a terminal and execute the following command in the root folder of your application:

```
php index.php luthier make auth
```

#### Step 2: Install the database

To install the database, run the following from the command line:

```
php index.php luthier migrate
```

<div class="alert alert-info">
    If an error occurs, make sure the connection parameters to the database are correct.
</div>

#### Step 3: Define the routes

In your `web.php` route file, add the following line:

```php
Route::auth();
```

The `Route::auth()` method is a shortcut to create all the necessary routes:

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

When visiting the `/login` URL of your application you should see your new login screen.

<div class="alert alert-warning">
    <strong>The logout path</strong><br />
    By default, the logout route only accepts POST requests, so navigating to the <code>/logout</code> URL will not work. You should use an HTML form that points to that route, however, if you want to accept GET requests use <code>Route::auth(FALSE)</code>
</div>

### SimpleAuth controller

El controlador de SimpleAuth (`SimpleAuthController`) contiene la lógica de cara al usuario para las operaciones de autenticación, tales como el **inicio de sesión**, el **registro de usuario** y el **restablecimiento de contraseña**. Un controlador de SimpleAuth recién creado se ve parecido a esto:

The SimpleAuth controller (`SimpleAuthController`) contains the user-facing logic for authentication operations, such as **login**, **registration** and **password reset**. A SimpleAuth controller looks like this:

```php
<?php
# application/controllers/SimpleAuthController.php

defined('BASEPATH') OR exit('No direct script access allowed');

/* (...) */

class SimpleAuthController extends Luthier\Auth\SimpleAuth\Controller
{

    /**
     * (...)
     */
    public function getSignupFields()
    {
        return [ ... ];
    }

    /**
     * (...)
     */
    public function getUserFields()
    {
        return [ ... ];
    }
}
```

The extended `Luthier\Auth\SimpleAuth\Controller` class already provides all the necessary logic so, unless you want to create something custom, it is not necessary to modify much here.

#### Customize the user registration form

You can change the fields of the registration form by modifying the returned array of the `getSignupFields()` method. This is an example of the structure of the array:


```php
public function getSignupFields()
{
    return [
        'Field name 1' => [
            'Field type',
            'Field label',
            [ /* HTML5 attributes array */ ],
            [ /* CI Validation rules array */ ] ,
            [ /* CI Validation error essages array (Optional) */ ]
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

The `getUserFields()` method returns an array with the columns of the database table where users are stored. Each element of the array **must match** the user registration form field names:

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

### SimpleAuth Middleware

The SimpleAuth Middleware (`SimpleAuthMiddleware`) protects those routes where the user authentication is required. This Middleware automatically verifies the current status of the user:

* If the user **is authenticated**, the request continues normally.
* If the user **is not authenticated**, it will try to restore the session using the _Remember Me_ feature.
* If it is not possible to restore any previous session, the user will be redirected to the login screen.

You can use the SimpleAuth Middleware in your routes and route groups like any other Middleware, and even combine it with your own Middleware to add additional security layers:

```php
<?php
# application/routes/web.php

Route::auth();
Route::get('/', 'FrontendController@homepage')->name('homepage');
Route::group('dashboard', ['middleware' => ['SimpleAuthMiddleware']], function(){
    Route::get('/', 'UserArea@dashboard');
});
```

### SimpleAuth library

The SimpleAuth library contains methods to perform operations that involve users. To use the SimpleAuth library you must first load it:

```php
$this->load->library('Simple_auth');
```

##### Get the current user

To get the current authenticated user, use the `user()` method. This method returns a **user instance** if the user is authenticated, or `NULL` otherwise:

```php
$user = $this->simple_auth->user();
```

You can access the user data as properties of the returned object:

```php
$user = $this->simple_auth->user();

$firstName = $user->first_name;
$lastName = $user->last_name;
```

<div class="alert alert-info">
    The <code>user()</code> method and the <strong>user instances</strong> are discussed in detail in the <strong>Luthier CI Authentication Framework</strong> documentation.
</div>

##### Check if user is guest (anonymous)

The `isGuest()` method returns `TRUE` if the user is anonymous (not logged in) or `FALSE` otherwise:

```php
$this->simple_auth->isGuest();
```

##### Check user roles

To verify if a user has a specific role, use the `isRole($role)` method, which returns `TRUE` if the user owns the `$role` role, or `FALSE` otherwise:

```php
$this->simple_auth->isRole('ADMIN');
```

##### Check user permissions

To verify if a user has a specific permission, use the `isGranted($permission)` method, which returns `TRUE` if the user has the `$permission` permission, or `FALSE` otherwise:

```php
$this->simple_auth->isGranted('general.read');
```

To verify if a user belongs to a role that begins with a specific phrase/category, use the character (**\***):

```php
$this->simple_auth->isGranted('general.*');
```

##### Check if user is fully authenticated

The `isFullyAuthenticated()` method returns `TRUE` if the user is fully authenticated, or `FALSE` otherwise:

```php
$this->simple_auth->isFullyAuthenticated();
```

A fully authenticated user is one who has logged in through the login form and not through the _Remember Me_ feature.

##### Prompt password

The `promptPassword($route)` method redirects to `$route` if the user is not fully authenticated (see `isFullyAuthenticated()` method).

This is useful for check if a useris authenticated through the _Remember Me_ feature.

##### Search an user

The `searchUser($search)` method returns an object with the first user that matches `$search`, or `NULL` if no user is found. The search criteria vary depending on the type of supplied `$search` variable:

* If `$search` is an **integer**, the user will be searched by its primary key (ID).
* If `$search` is a **string**, the user will be searched by the value that matches the column set for the username (simpleauth_username_col parameter)
* If `$search` is an **array**, the search will be performed as the `where($search)` CodeIgniter QueryBuilder native method.

Example:

```php
$this->simple_auth->searchUser(1);
$this->simple_auth->searchUser('admin@admin.com');
$this->simple_auth->searchUser(['gender' => 'm', 'active' => 1]);
```

##### Update a user

The `updateUser($search, $values)` method updates the first user found by the `$search` criteria (see `searchUser()` method) with the new values ​​supplied in `$values` ​array.

Example:

```php
$this->simple_auth->updateUser(1, ['first_name' => 'John']);
$this->simple_auth->updateUser('admin@admin.com', ['gender' => 'f']);
```

##### Create a user

The `createUser($user)` method creates a user with the data of `$data` array. Each index corresponds to a column of the user database table.

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

<div class="alert alert-info">
    This method automatically creates the password hash for the name of the column that matches the value set in the <code>simpleauth_password_col</code> option
</div>

### Access Control Lists (ACL)

**Access Control Lists** provides more control of user permissions. When this feature is used, a userhas one or more permissions assigned. Together, those permissions grant (or deny) the access to certain resources of the application.

<div class="alert alert-info">
    In SimpleAuth there are no <em>user groups</em> or anything similar. User permissions are stored as a permissions tree within a database.
</div>

<div class="alert alert-info">
    There is no method to create or delete permissions, so you must do it manually. The tables used by ACL, however, are created by the migrations included with SimpleAuth
</div>

Consider the following `user_permissions_categories` table:

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

And the following `user_permissions` table:

```
ID      USERNAME    PERMISSION_ID
---------------------------------
1       anderson    2
2       anderson    5
3       julio       3
4       julio       6
```

When the user `anderson` logs in, he will have the following permissions:

```
general.read
general.delete.local
```

And when the user `julio` logs in, he will have the following permissions:

```
general.write
general.delete.global
```

##### Check if a permission exists

The `permissionsExists($permission)` method returns `TRUE` if the `$permission` exists in the ACL database table, or FALSE otherwise.

Example:

```php
$this->simple_auth->permissionExists('general.read');
```

##### Grant a permission

The `grantPermission($permission, $username)` method assigns the `$permission` to the `$username` user and returns `TRUE` if the operation was successful, or `FALSE` otherwise. If `$username` is omitted, the operation will be performed on the current authenticated user.

Example:

```php
$this->simple_auth->grantPermission('general.read');
```

##### Revoke a permission

The `revokePermission($permission, $username)` method revokes the `$permission` of the `$username` user, returning `TRUE` if the operation was successful, or `FALSE` otherwise. If the `$username` is omitted, the operation is performed on the current authenticated user.

Example:

```php
$this->simple_auth->revokePermission('general.read');
```

### Views and translations

You can change the design (skin) of forms rendered by SimpleAuth by choosing between the default views or your  views. Default views in SimpleAuth have the advantage of being translated into several languages. The languages ​​supported by SimpleAuth are the following:

* English
* Spanish
* Italian

##### Set the SimpleAuth skin

To change the SimpleAuth skin, modify the `simpleauth_skin` option of the SimpleAuth configuration file:

```php
# application/config/auth.php

$config['simpleauth_skin'] = 'default';
```

The language used by the skins is taken from the value of the `language` option (`$config['language']`) of the framework main configuration file (`application/config/config.php`)

<div class="alert alert-info">
    If the current language is not found among the languages ​​supported by SimpleAuth, English will be used
</div>

##### Using your own views

In total, 6 views are used by SimpleAuth:

* **login.php**: Login view
* **signup.php**: User Registration View
* **password_prompt.php**: Current password confirmation view (Remember Me feature)
* **password_reset.php**: View of the password reset request form
* **password_reset_form.php**: View of the password reset form
* **message.php**: View of a generic message

To override a view, create a file with the same name in the `applications/views/simpleauth` folder.

For example:

```php
application/views/simpleauth/login.php
application/views/simpleauth/message.php
application/views/simpleauth/password_prompt.php
application/views/simpleauth/password_reset.php
application/views/simpleauth/password_reset_form.php
application/views/simpleauth/signup.php
```

### SimpleAuth settings

The SimpleAuth settings are stored in the `application/config/auth.php` file. This file is created automatically during the installation of SimpleAuth.

##### Feature Activation/Deactivation

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_enable_signup** | *bool* | Enable or disable the user registration form |
| **simpleauth_enable_password_reset** | *bool* | Enable or disable the password reset form |
| **simpleauth_enable_remember_me** | *bool* | Enable or disable the _Remember Me_ feature |
| **simpleauth_enable_email_verification** | *bool* | Enable or disable user email verification. You must load and configure the [Email Library](https://codeigniter.com/user_guide/libraries/email.html) to use this feature |
| **simpleauth_enforce_email_verification** | *bool* | Forces users to verify their emails to login. You must load and configure the [Email Library](https://codeigniter.com/user_guide/libraries/email.html) to use this feature |
| **simpleauth_enable_brute_force_protection** | *bool* | Enable or disable the defense against brute force login attempts |
| **simpleauth_enable_acl** | *bool* | Enable or disable Access Control Lists |

##### General settings

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_user_provider** | *string* | *User provider* used by SimepleAuth |
| **auth_login_route** | *string* | Login route. If you use the `Route::auth()` method to define the SimpleAuth routes this value will be ignored |
| **auth_logout_route** | *string* | Logout route. If you use the `Route::auth()` method to define the SimpleAuth routes this value will be ignored |
| **auth_login_route_redirect** | *string* | Route to be redirected after login |
| **auth_logout_route_redirect** | *string* |  Route to be redirected after logout |
| **auth_route_auto_redirect** | *array* | Routes that will activate an automatic redirection to the `auth_login_route_redirect route`, in case the user is already authenticated |
| **auth_form_username_field** | *string* | Field of the login form corresponding to the username/email |
| **auth_form_password_field** | *string* | Field of the login form corresponding to the password |
| **auth_session_var** | *string* | Name of session variable used by the Luthier CI Authentication Framework |

##### View settings

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_skin** | *string* | Skin used in the views included by SimpleAuth |
| **simpleauth_assets_dir** | *string* | Public URL for SimpleAuth assets (css, js, etc.) |

##### ACL settings

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_acl_map** | *array* | An associative array of categories and permission groups (`'name' => 'Category ID'`) used by the ACL feature. Setting this improves considerably the database performance. |

##### Configuración de emails

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_email_configuration** | *array\|null* | Array with the **Email Library** configuration used by SimpleAuth. Leave in `NULL` to use the same of the application |
| **simpleauth_email_address** | *string* | Email address that will appear in the `from` field of emails sent by SimpleAuth |
| **simpleauth_email_name** | *string* | Name of the remitent of emails sent by SimpleAuth |
| **simpleauth_email_verification_message** | *string\|null* | Email template with the email verification instructions that is sent by SimpleAuth after the user registration. Leave in `NULL` to use the default message. |
| **simpleauth_password_reset_message** | *string\|null* | Email template with instructions for password reset. Leave in `NULL` to use the default message. |

##### "Remember Me" feature settings

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_remember_me_field** | *string* | Name of the login form field for the _Remember Me_ feature |
| **simpleauth_remember_me_cookie** | *string* | Name of the Cookie used by the _Remember Me_ feature |

##### Database settings

| Parameter | Type  | Decription |
| :--- | :---: | :--- |
| **simpleauth_users_table** | *string* | Name of the table where users are stored |
| **simpleauth_users_email_verification_table** | *string* | Name of the table where email verification tokens are stored |
| **simpleauth_password_resets_table** | *string* | Name of the table where the password reset tokens are stored |
| **simpleauth_login_attempts_table** | *string* | Name of the table where failed login attempts are stored |
| **simpleauth_users_acl_table** | *string* | Name of the table for ACL |
| **simpleauth_users_acl_categories_table** | *string* | Name of the table where ACL permission categories are stored |
| **simpleauth_id_col** | *string* | Name of the _ID_ column of users table |
| **simpleauth_username_col** | *string* | Name of the _username_ column of users table |
| **simpleauth_email_col** | *string* | Name of the _email_ column of users table  |
| **simpleauth_password_col** | *string* | Name of the _password_ column of users table  |
| **simpleauth_role_col** | *string* | Name of the _role_ column of users table. It's used for role comprobations |
| **simpleauth_active_col** | *string* | Name of the _active_ column of users table. The value of this column is a boolean, where `1` corresponds to an active user, and `0` to an inactive user. |
| **simpleauth_verified_col** | *string* | Name of the _verifed_ column of users table. The value of this column is a boolean, where `1` corresponds to a verified user, and `0` to a unverified user. |
| **simpleauth_remember_me_col** | *string* | Name of the column where tokens used by _Remember Me_  feature are stored |
