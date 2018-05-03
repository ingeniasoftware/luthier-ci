# Luthier-CI

[![Latest Stable Version](https://poser.pugx.org/luthier/luthier/v/stable?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![Total Downloads](https://poser.pugx.org/luthier/luthier/downloads?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![Latest Unstable Version](https://poser.pugx.org/luthier/luthier/v/unstable?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![License](https://poser.pugx.org/luthier/luthier/license?format=flat-square)](https://packagist.org/packages/luthier/luthier)

Laravel-like routing and Middleware support for CodeIgniter 3. **Luthier-CI** makes the development of CodeIgniter apps even more enjoyable and simple!

A design goal of Luthier-CI is to have the less side effects possible and be deeply integrated to the framework, so basically your app must be working just like before the installation, no matters what libraries, hooks, helpers o third party packages are enabled.

## Features

* Clean and easy installation via hooks
* Global and per-route middleware
* Advanced routing: prefixes, namespaces, anonymous functions as routes, route groups, CLI routes, named parameters, optional parameters, sticky parameters

## Requirements

* PHP >= 5.6.0 (PHP 7 compatible)
* CodeIgniter 3.x

## Installation

**Note:** this tutorial is assuming that you have a fresh CodeIgniter installation

#### Get Luthier-CI with Composer

```
composer require luthier/luthier
```

#### Enable Hooks and Composer autoload

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

Set the Luthier-CI routes:

```php
<?php
# application/config/routes.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$route = Luthier\Route::getRoutes();
```

The first time that Luthier-CI runs, several files and folders are created:

* `routes/web.php`: Default HTTP-Based routes
* `routes/api.php`: AJAX routes
* `routes/cli.php`: CLI routes
* `controllers/Luthier.php`: Fake controller, needed to run route callbacks
* `middleware`: Middleware folder

During the framework initialization some hooks are called: the first, `Luthier\Hook::getHooks()` returns all Luthier-CI related hooks, included the needed to boot. At this point, Luthier-CI parses and compiles all the routes defined in the first three files. Then, when the framework loads the routes, `Luthier\Route::getRoutes()` returns the actual array of routes. All after this is just the normal execution of the framework.

## Routes

#### Basic route definition

To add routes, use the static methods of the `Route` class facade:

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

#### CLI routes

Use the `Route::cli()` method to add command line routes. All CLI routes must be inside `routes/cli.php` file. This is an example of a CLI route:

```php
Route::cli('path','controller@method');
```

Read more about CLI usage in the [documentation](https://www.codeigniter.com/user_guide/general/cli.html)

##### Built-in CLI commands:

Since 0.2.0 version, Luthier-CI comes with some useful ready-to-use CLI commands. They are opt-in, so you must declare it in your CLI routes first:

```php
<?php
#application/routes/cli.php

// 'luthier make' command
Luthier\Cli::maker();

// 'luthier migrate' command
Luthier\Cli::migrations();

```

Due security reasons, both commands are disabled in `testing` and `production` environments.

##### `luthier make`

Creates a new framework file.

Syntax for making a controller, model, library, helper or middleware:

```
luthier make [controller|model|library|helper|middleware] [resource name]
```

Syntax for making a migration:

```
luthier make migration [migration name] [sequential|date?]
```

Read more about migrations in the [documentation](https://www.codeigniter.com/user_guide/libraries/migration.html)

##### `luthier migrate`

Runs (or rollback) a migration

Syntax:

```
luthier migrate [version|refresh|reverse?]
```

* `version` indicates the specific version to migrate
* `reverse` will rollback ALL migrations
* `refresh` will rollback and then run ALL migrations

#### Callbacks as routes:

It's possible to use an anonymous function as a route! here's an example:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});

Route::cli('path',function(){
    echo 'foo';
});
```

The `ci()` function returns the framework instance, acting as a *virtual* controller.

#### Named routes

You can assign names to your routes so you don't have to worry about future url changes:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

Use the `route()` function to retrieve the compiled url:

```php
<a href="<?= route('about_us');?>">My link!</a>
// <a href="http://[App Base Url]/company/about_us">Link</a>
```

If the route has parameters, pass a second argument to the function with an array of their values:

```php
<?= route('route_name', ['param1' => 'value2', 'param2' => 'value2' ... ]); ?>
```

#### Sticky parameters

What if you want to define a global route parameter and make it available to all inherited routes? This can be done with help of *sticky* parameters. A sticky parameter starts with an underscore `_` and has a few singularities:

* Is NOT passed in the arguments of the pointed controller's method
* The parameter value is the same of the current route value and is set by default if is not present, making it *sticky* (if the subsequent routes shares that parameter, of course)

Here's an example:

```php
Route::group('shop/{_locale}', function()
{
    Route::get('category/{id}', 'ShopCategory@categoryList')->name('shop.category');
    Route::get('product/{id}/details', 'ShopProduct@details')->name('shop.product.details');
});
```

Both `shop.category` and `shop.products.details` routes shares the `_locale` parameter. While is required to be in the path, is not mandatory to be present in the `route()` array of parameters if called in that context. So, if you visit the path `shop/en/category/1` (`shop.category` route) and want to link another categories without the tedious labor of specify the `_locale` parameter, just skip it:

```php
// If the path is 'shop/en/category/1', {_locale} will be 'en' here:
route('shop.category', ['id' => 1]); # shop/en/category/1
route('shop.category', ['id' => 2]); # shop/en/category/2
route('shop.category', ['id' => 3]); # shop/en/category/3

// however, you can override with another value:
route('shop.category', ['_locale' => 'es', 'id' => 1]); # shop/es/category/1
```

Inside the `ShopCategory` and `ShopProduct` controllers, their methods must define only one argument: $id, since only this will be passed:

```php
<?php

class ShopCategory extends CI_Controller
{
    // CategoryList($_locale, $id) will just not work

    public function CategoryList($id)
    {
        var_dump($id);
    }
}
```

So, how we get the `_locale` parameter value? calling the `route->param()` method:

```php
<?php

class ShopCategory extends CI_Controller
{
    public function CategoryList($id)
    {
        // Assuming that the path is 'shop/en/category/1':
        var_dump($id, $this->route->param('_locale');
        // 1, 'en'
    }
}
```

#### Namespaces

Use the *namespace* property to indicate to CodeIgniter the path to the controller. (Please note that this is not an actual *namespace*, is just the controller path)

```php
// This points to application/controllers/admin/Testcontroller.php
Route::get('hello/world', 'testcontroller@index', ['namespace' => 'admin']);
```

#### Prefixes

Prefixing routes is easy, just use the *prefix* property:

```php
//The route will be accessed with the 'admin/hello/world' path instead 'hello/world'
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
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

#### Default controller

Luthier-CI automatically set any GET route to '/' path as your default controller, however,
you can set it explicitly with:

```php
// Note that this is a bind to $route['default_controller'] index and must be in CI
// native format:
Route::set('default_controller', 'welcome/index');
```

## Middleware

Luthier-CI adds the concept of middleware to the framework. Think on the middleware as a set of "layouts" that the user will pass thru with every request until reach to the desired resource. You can, for example, perform a user validation before entering to a controller, and redirect it to another place in case of failure. An advantage of the middleware is that you can assign it on a specific route/group or even define it as global middleware

In fact, the middleware is an extension of the controller, because the framework singleton is built at this point. So, you will be able of execute all the libraries/functions that are normally available inside the controllers.

#### Middleware execution points

In global context, there's two execution points available:

* **pre_controller**:  middleware will be executed right after the controller constructor, *BUT* before any controller action were performed. Please notice that the controller constructor is called at this point, because the own CodeIgniter's execution sequence (which Luthier-CI don't modify) so if you need to do some actions *before* any middleware is executed, you must define a method called *preMiddleware()* in your controller and Luthier-CI will call it first.
* **post_controller**: middleware will be executed exactly in the `post_controller` hook point.

In a route context, there's only one execution points and works as same as the **pre_controller** point in global context.

#### Middleware definition

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

#### Manually running middleware

Use the `middleware->run()` method in your controllers to run a middleware on-demand:

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
```

#### Callbacks as middleware

You can use callbacks in middleware definitions, and works exactly as route callbacks:

```php
Route::middleware(function(){
    ci()->load->library('twig');
}, 'pre_controller');
```

## Roadmap

* Write a better and in-depth documentation
* Probably more, feel free to make all your suggestions!

## Donate

Enjoying Luthier-CI? Donate with [Paypal](https://paypal.me/andersalasm) and help us to make more cool stuff!

