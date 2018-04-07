# Luthier-CI

[![Build Status](https://scrutinizer-ci.com/g/ingeniasoftware/luthier/badges/build.png?b=master)](https://scrutinizer-ci.com/g/ingeniasoftware/luthier/build-status/master)
[![Total Downloads](https://poser.pugx.org/luthier/luthier/downloads)](https://packagist.org/packages/luthier/luthier)
[![Latest Unstable Version](https://poser.pugx.org/luthier/luthier/v/unstable)](https://packagist.org/packages/luthier/luthier)

Laravel-like routing and Middleware for CodeIgniter. Luthier-CI makes the development of large apps more enjoyable and easy!

**UNDER CONSTRUCTION . DO NOT USE THIS IN PRODUCTION UNTIL THE PROJECT REACH A STABLE RELEASE.**

## Key features

* Laravel-inspired static Route class
* Route groups
* Middleware support

## Installation

**Note:** this tutorial is assuming that you have a fresh CodeIgniter installation

#### Step 1: Get Luthier-CI with Composer

```
composer require luthier/luthier dev-master
```

#### Step 2: Configure Hooks and Composer in your CodeIgniter app

Go to your `config/config.php` file and make sure that you have both *hooks* and *composer
autoload* enabled:

```php

$config['enable_hooks'] = TRUE;

$config['composer_autoload'] = TRUE;
```

#### Step 3: Connect Luthier-CI with CodeIgniter

In your `config/hooks.php` file put the following in the very first `$hook` declaration

```php
$hook = Luthier\Hook::getHooks();
```

Then, in your  `config/routes.php` file put the following in the very first `$route` declaration

```php
$route = Luthier\Route::getRoutes();
```

#### Step 4: Add your routes

The first time that you run Luthier-CI, several files and folders are created:

* `routes/web.php` (Default HTTP-Based routes, start here!)
* `routes/api.php` (AJAX routes)
* `routes/cli.php` (CLI routes, not working yet)
* `controllers/Luthier.php` (Default fake controller)

Some examples:
```php
// Single route
Route::get('foo', 'bar@baz');

// Route with parameters
Route::get('blog/{slug}', 'blog@post');
```

You can use anonymous functions/callbacks in your routes, without a controller:

```php
Route::get('foo', function(){
    // The ci() function is an alias of get_instance()
    ci()->load->view('some_view');
});
```

## Routes

#### Named routes

You can assign names to your routes so you don't have to worry about future path changes:

```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo']);
```

In your views, you can use the function `route()` with the desired name to retrieve the actual path:

```php
<a href="<?= route('foo');?>">My link!</a>
// <a href="http://[App Base Url]/hello/world">Link</a>
```

#### Route namespaces

If you have subdirectories in your controllers folder, you can specify a *pseudo-namespace* in your routes to indicate to CodeIgniter the path to the controller.

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

#### The default controller:

Luthier-CI automatically set any GET route to '/' path as your default controller, however,
you can set it explicitly with:

```php
Route::set('default_controller', 'welcome/index');
// Note that this is a bind to $route['default_controller'] index and must be in CI
// native format
```
### Route groups

You can group your routes in a convenient way using the `Route::group()` method:

```php
Route::group('prefix', function(){
    Route::get('bar', ['uses' => 'test@bar']);
    Route::get('baz', ['uses' => 'test@baz']);
});
```
*Full documentation will be available soon...*