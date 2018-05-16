<p align="center">
    <img src="https://ingenia.me/images/luthier-ci/logo.png" width="100" />
</p>

**Luthier-CI** is a plugin for CodeIgniter 3 that makes the development of APIs (and websites in general) more easy! For full documentation, visit the project [official website](https://luthier-ci.ingenia.me/en/docs) (disponible en [español](https://luthier-ci.ingenia.me/en/docs))

## Features

* Easy installation
* Laravel-like routing: prefixes, namespaces, anonymous functions as routes, route groups, CLI routes, named parameters, optional parameters, sticky parameters.
* Middleware support

## Requirements

* PHP >= 5.6.0 (PHP 7 compatible)
* CodeIgniter 3.x

## Installation

#### Step 1: Get Luthier-CI with Composer

```
composer require luthier/luthier
```

#### Step 2: Enable Hooks and Composer autoload

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

Set the Luthier-CI routes:

```php
<?php
# application/config/routes.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$route = Luthier\Route::getRoutes();
```

## Initialization

The first time that Luthier-CI runs, several files and folders are created:

* `routes/web.php`: Default HTTP-Based routes
* `routes/api.php`: AJAX routes
* `routes/cli.php`: CLI routes
* `controllers/Luthier.php`: Fake controller, necessary to use some routes
* `middleware`: Middleware folder

**Important**: Make sure that your `application` folder has write permission!

## Usage

To add routes, use the static methods of the `Route` class:

```php
<?php
# application/routes/web.php

// This points to 'baz' method of 'bar' controller at '/foo' path under a GET request:
Route::get('foo', 'bar@baz');

// To add a route parameter, enclose with curly brackets {}
Route::get('blog/{slug}', 'blog@post');

// To make a parameter optional, add a ? just before closing the curly brackets
// (Luthier-CI will make all the fallback routes for you)
Route::get('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');

// The (:any) and (:num) CodeIgniter route placeholders are available to use, with this syntax:
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');

// Custom regex? it's possible with this syntax:
Route::post('main/{((es|en)):_locale}/about', 'about@index');
```

Use the `Route::cli()` method to add command line routes. All CLI routes must be inside `routes/cli.php` file. This is an example of a CLI route:

```php
Route::cli('path','controller@method');
```

The `ci()` function returns the framework instance, acting as a *virtual* controller. This is useful if you use callbacks as routes:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});
```

You can assign names to your routes so you don't have to worry about future url changes:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

To retrieve a named route, use the `route()` function:

```php
<a href="<?= route('about_us');?>">My link!</a>
// <a href="http://example.com/company/about_us">Link</a>
```

If the route has parameters, pass a second argument to the function with an array of their values:

```php
<?= route('route_name', ['param1' => 'value2', 'param2' => 'value2' ... ]); ?>
```

#### Route Groups

Group routes is possible with the  `Route::group()` method. All routes
inside the group will share the *prefix* (first argument) and, optionally, another property (*namespace*, *middleware*, etc.)

```php
// Prefix only
Route::group('prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});

// Prefix and shared properties
Route::group('prefix', ['namespace' => 'foo', 'middleware' => ['Admin','IPFilter']], function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```


## Middleware

All the middleware must be defined in the routes files (`application/routes/web.php`, `application/routes/api.php`, `application/routes/cli.php`)

```php
# Route middleware:
Route::put('foo/bar','controller@method', ['middleware' => ['Test']]);

# Route group middleware:
Route::group('site', ['middleware' => ['Admin']], function(){
    // ...
});

# Global middleware:
Route::middleware('Admin', 'pre_controller');
```

The middleware files must be saved in the `application/middleware` folder. If not exists, you must create it first. A middleware file is any php class with a public `run()` method, which is the entry point. It's strongly advised to name all your middleware with CamelCase and avoid name conflicts with your controllers.

This is an example of a middleware:

```php
<?php
# application/middleware/Test_middleware.php

class Test_middleware
{
    public function run()
    {
        // Este es el punto de entrada del middleware
    }
}
```

## Donate

If you love our work, you can support us via [Paypal](https://paypal.me/andersalasm) or [Patreon](https://patreon.com/ingeniasoftware) 







