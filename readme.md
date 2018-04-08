# Luthier-CI

[![Build Status](https://scrutinizer-ci.com/g/ingeniasoftware/luthier/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ingeniasoftware/luthier/build-status/master)
[![Total Downloads](https://poser.pugx.org/luthier/luthier/downloads)](https://packagist.org/packages/luthier/luthier)
[![Latest Unstable Version](https://poser.pugx.org/luthier/luthier/v/unstable)](https://packagist.org/packages/luthier/luthier)

Laravel-like routing and Middleware support for CodeIgniter 3. **Luthier-CI** makes the development of CodeIgniter apps even more enjoyable and simple!  
  
A design goal of Luthier-CI is to have not side efects in your application, so basically all must be working as expected, no matters what libraries, hooks, helpers o third party packages you have installed.  

## Features

* Clean and easy instalation via hooks
* Global and per-route middleware
* Advanced routing: prefixes, namespaces, anonymous functions as routes, route groups, named parameters, optional parameters, default parameter values and "sticky" parameters

## Installation

(**Note:** this tutorial is assuming that you have a fresh CodeIgniter installation)

#### Get Luthier-CI with Composer

```
composer require luthier/luthier
```

#### Enable Hooks and Composer autoload

Make sure that *hooks* and *composer autoload* are enabled:

```php
<?php
# application/config/config.php

$config['enable_hooks'] = TRUE;
$config['composer_autoload'] = TRUE;
```

#### Connect Luthier-CI with CodeIgniter

Set the hooks:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks();
```

Set the Luthier routes:

```php
<?php
# application/config/routes.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$route = Luthier\Route::getRoutes();
```

The first time that you run Luthier-CI, several files and folders are created:

* `routes/web.php`: Default HTTP-Based routes
* `routes/api.php`: AJAX routes
* `routes/cli.php`: CLI routes (not working yet)
* `controllers/Luthier.php`
  
Luthier-CI parses and compiles all the routes defined in the first three files. During the framework initialization, CodeIgniter will seek the defined hooks (`Luthier\Hook::getHooks()` returns all needed hooks in order to run Luthier-CI) then loads the routes (`Luthier\Route::getRoutes()` returns an array of all defined routes in the native framework format) and finally the executions continues normally.

## Routes

#### Basic route definition

To add routes, use the static methods of the `Route` class facade:

```php
<?php 
# application/routes/web.php

// This is a very simple route that points to 'baz' method of 'bar' controller
// on '/foo' path under a GET request:
Route::get('foo', 'bar@baz');

// To add a route parameter, enclose with {brackets}:
Route::get('blog/{slug}', 'blog@post');

// Optional parameter example:

Route::get('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');

// $route['categories']['GET'] = 'clients/list';
// $route['categories/(:any)']['GET'] = 'clients/list/$1';
// $route['categories/(:any)/(:any)']['GET'] = 'clients/list/$1/$2';
// $route['categories/(:any)/(:any)/(:any)']['GET'] = 'clients/list/$1/$2/$3';

// The (:any) and (:num) CodeIgniter route placeholders works as expected:
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');

// Custom regex? it's possible too:
Route::post('main/{((es|en)):_locale/about', 'about@index');
```

#### Custom route callbacks:

You can use an anonymous function (AKA Lambda functions) in a route action:

```php
Route::get('foo', function(){
    // ( ... )
});
```

To access to the CodeIgniter instance, use the `ci()` function:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});
```

#### Named routes

You can assign names to your routes so you don't have to worry about future path changes:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

In your views, you can use the function `route()` with the desired name to retrieve the actual path:

```php
<a href="<?= route('about_us');?>">My link!</a>
// <a href="http://[App Base Url]/company/about_us">Link</a>
```

#### Namepsaces

If you have subdirectories inside your controllers folder, you can specify a *pseudo-namespace* in your routes to indicate to CodeIgniter the path to the controller.


```php
Route::get('hello/world', 'testcontroller@index', ['namespace' => 'admin']);
```

Will be point to *application/controllers/admin/Testcontroller.php*

#### Prefixes

You can set a *prefix* to your routes with the following syntax:

```php
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

So the route will be accessed with the 'admin/hello/world' path instead 'hello/world'


#### Groups

You can group your routes in a convenient way using the `Route::group()` method. All routes
inside the group will share the *prefix* (first argument) and, optionally, another properties
like *namespace* and *middleware*

```php
Route::group('prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz);
});
```

#### Default controller

Luthier-CI automatically set any GET route to '/' path as your default controller, however,
you can set it explicitly with:

```php
// Note that this is a bind to $route['default_controller'] index and must be in CI
// native format:
Route::set('default_controller', 'welcome/index');
```

## Middleware

Luthier-CI adds the concept of middleware to the framework. Think on the middleware as a set of "layouts" that the user will pass thru with every request until reach to the desired resource. You can, for example, perform a user validation before enter to a controller, and redirect it to another place in case of failure. An advantage of the middleware is that you can assign it on a specific route/group or even define it as global middleware, and will be executed on all routes!

#### Middleware execution points

In global context, there's two points available

* **pre_controller**:  middleware will be executed right after the controller constructor, *BUT* before any controller action were performed. Please notice that the controller constructor is called at this point, because the own CodeIgniter's execution sequence (wich Luthier-CI don't modify) so if you need to do some actions *before* any middleware is executed, you must define a method called *preMiddleware()* in your controller and Luthier-CI will call it first.
* **post_controller**: middleware will be executed exactly in the `post_controller` hook point.
  
In a route context, there's only one execution points and works as the **pre_controller** point in global context.

#### Middleware definition

All the middleware must be defined in the routes files (`application/routes/web.php`, `application/routes/api.php`, `application/routes/cli.php`)

```php
# Route middleware:
Route::put('foo/bar','controller@method', ['middleware' => ['Test']]);

# Route group middleware:
Route::group('site', ['middleware' => ['Tdmin']], function(){
    // ...
});

# Global middleware:
Route::middleware('Admin', 'pre_controller');
````

The middleware files must be saved in the `application/middleware` folder. If not exists, you must create it. A middleware file is any php class with a public `run()` method, which is the entry point. It's strongly adviced to name all your middleware with CamelCase and avoid name conflicts with your controllers.  
  
#### Manually running middleware
  
In your controllers, you can call to a middleware with the `middleware->run()` method:  
  
```php
<?php

class MyController extends CI_Controller
{
    public function __construct()
    {   
        parent::__construct();
        $this->middleware->run('Admin');
    }
  
    // ...
}
````
  
#### Callbacks as middleware
  
You can use callbacks in your middleware definitions and will works exactly as route actions callbacks:  
  
```php
<?php
# config/routes/web.php

Middleware(function(){
    ci()->load->library('twig');
}, 'pre_controller');
```

## Donate

Enjoying Luthier-CI? Donate with [Paypal](paypal.me/andersalasm)