# Luthier-CI

[![Build Status](https://scrutinizer-ci.com/g/ingeniasoftware/luthier/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ingeniasoftware/luthier/build-status/master)
[![Total Downloads](https://poser.pugx.org/luthier/luthier/downloads)](https://packagist.org/packages/luthier/luthier)
[![Latest Unstable Version](https://poser.pugx.org/luthier/luthier/v/unstable)](https://packagist.org/packages/luthier/luthier)

Laravel-like routing and Middleware support for CodeIgniter 3. **Luthier-CI** makes the development of CodeIgniter apps even more enjoyable and simple!

## Features

* Clean and easy instalation via hooks
* Global and per-route middleware
* Advanced routing: named parameters, optional parameters, default parameter values and "sticky" parameters
* Anonymous routes (using a callback instead a controller) 

## Installation

(**Note:** this tutorial is assuming that you have a fresh CodeIgniter installation)

#### Step 1: Get Luthier-CI with Composer

```
composer require luthier/luthier dev-master
```

#### Step 2: Enable Hooks and Composer autoload

Make sure that you have both *hooks* and *composer autoload* enabled:

```php
<?php
# application/config/config.php

$config['enable_hooks'] = TRUE;
$config['composer_autoload'] = TRUE;
```

#### Step 3: Connect Luthier-CI with CodeIgniter

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

* `routes/web.php`: Default HTTP-Based routes, start here!
* `routes/api.php`: AJAX routes
* `routes/cli.php`: CLI routes (not working yet)
* `controllers/Luthier.php`

In order to start using Luthier-CI, define your routes in the `web.php` file.

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

// You can define a parameter as optional:
Route::get('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');
// It produces:
// $route['categories'] = 'clients/list';
// $route['categories/(:any)'] = 'clients/list/$1';
// $route['categories/(:any)/(:any)'] = 'clients/list/$1/$2';
// $route['categories/(:any)/(:any)/(:any)'] = 'clients/list/$1/$2/$3';

// The (:any) and (:num) CodeIgniter route placeholders works as expected:
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');

// Custom regex? it's possible too:
Route::post('main/{((es|en)):_locale/about', 'about@index');
```

#### Custom route callbacks:

```php
Route::get('foo', function(){
    // The ci() function is an alias of &get_instance()
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

*Example:*

```php
Route::get('hello/world', 'testcontroller@index', ['namespace' => 'admin']);
```

Will be point to *application/controllers/admin/Testcontroller.php*

#### Route prefix

You can set a *prefix* to your routes with the following syntax:

```php
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

So the route will be accessed with the 'admin/hello/world' path instead 'hello/world'

#### Default controller

Luthier-CI automatically set any GET route to '/' path as your default controller, however,
you can set it explicitly with:

```php
Route::set('default_controller', 'welcome/index');
// Note that this is a bind to $route['default_controller'] index and must be in CI
// native format
```
#### Groups

You can group your routes in a convenient way using the `Route::group()` method. All routes
inside the group will share the *prefix* (first argument) and, optionally, another properties
like *namespace* and *middleware*

```php
// Basic example:
Route::group('prefix', function(){
    Route::get('bar', ['uses' => 'test@bar']);
    Route::get('baz', ['uses' => 'test@baz']);
});
```
*Full documentation and website coming soon...*