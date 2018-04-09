# Luthier-CI

[![Latest Stable Version](https://poser.pugx.org/luthier/luthier/v/stable?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![Total Downloads](https://poser.pugx.org/luthier/luthier/downloads?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![Latest Unstable Version](https://poser.pugx.org/luthier/luthier/v/unstable?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![License](https://poser.pugx.org/luthier/luthier/license?format=flat-square)](https://packagist.org/packages/luthier/luthier)
[![composer.lock](https://poser.pugx.org/luthier/luthier/composerlock?format=flat-square)](https://packagist.org/packages/luthier/luthier)

Laravel-like routing and Middleware support for CodeIgniter 3. **Luthier-CI** makes the development of CodeIgniter apps even more enjoyable and simple!  
  
A design goal of Luthier-CI is to have the less side effects possible and be deeply integrated to the framework, so basically your app must be working just like before the installation, no matters what libraries, hooks, helpers o third party packages you have currently.

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
* `controllers/Luthier.php`: Fake controller, needed to run route callbacks
  
During the initialization CodeIgniter will run some hooks: `Luthier\Hook::getHooks()` returns all Luthier-CI related hooks, included the needed to boot. At this phase, Luthier-CI parses and compiles all the routes defined in the first three files. Then, when the framework loads the routes, `Luthier\Route::getRoutes()` returns the actual array of routes. All after this is just the normal execution of the framework.

## Routes

#### Basic route definition

To add routes, use the static methods of the `Route` class facade:

```php
<?php 
# application/routes/web.php

// This is a very simple route that points to 'baz' method of 'bar' controller
// on '/foo' path under a GET request:
Route::get('foo', 'bar@baz');

// To add a route parameter, enclose with curly brackets {}
Route::get('blog/{slug}', 'blog@post');

// To make a parameter optional, add a ? just before closing the curly brackets
// (Luthier-CI will make all the fallback routes for you)
Route::get('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');

// The (:any) and (:num) CodeIgniter route placeholders are availables to use, with this syntax:
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');

// Custom regex? it's possible too:
Route::post('main/{((es|en)):_locale}/about', 'about@index');
```

#### Sticky parameters
  
What if you want to define a global route parameter and make it available to all inherited routes? This can be done with help of *sticky* parameters. A sticky parameter starts with an underscore `_` and has a few singularities:  
  
* Is NOT passed in the arguments of the pointed controller's method  
* The parameter value is the same of the current route value and is setted by default if is not present, making it *sticky* (if the subsequent routes shares that parameter, of course)  
  
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
        // assuming that the path is 'shop/en/category/1':
        var_dump($id, $this->param('_locale');
        // 1, 'en'
    }
}
```

#### Custom route callbacks:

You can use an anonymous function (AKA Lambda functions) in a route action, instead a `controller@method` definition:

```php
Route::get('foo', function(){
    // ( ... )
});
```

To access to the CodeIgniter instance inside route callbacks, use the `ci()` function:

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

Use the `route()` function with the route name to retrieve the compiled path:

```php
<a href="<?= route('about_us');?>">My link!</a>
// <a href="http://[App Base Url]/company/about_us">Link</a>
```

If the route have parameters, pass a second argument to the function with an array of their values:

```php
<?= route('route_name', ['param1' => 'value2', 'param2' => 'value2' ... ]); ?>
```

#### Namepsaces

If you have subdirectories inside your controllers folder, use the *namespace* property to indicate to CodeIgniter the path to the controller. (Please note that this is not an actual *namespace*, is just the directory path to the controller)

```php
// This points to application/controllers/admin/Testcontroller.php
Route::get('hello/world', 'testcontroller@index', ['namespace' => 'admin']);
```

#### Prefixes

You can set a *prefix* to your routes with the following syntax:

```php
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

So the route will be accessed with the 'admin/hello/world' path instead 'hello/world'
  
#### Route Groups

You can group your routes in a convenient way using the `Route::group()` method. All routes
inside the group will share the *prefix* (first argument) and, optionally, another properties (*namespace*, *middleware*, etc.) 

```php
// Simple route group
Route::group('prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz);
});

// Route group with shared properties
Route::group('prefix', ['namespace' => 'foo', 'middleware' => ['Admin','IPFilter']], function(){
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
  
In fact, the middleware is an extension of the controller, because the framework singleton is built at this point. So you will be able of execute all the libraries/functions that are normally available inside the controllers.

#### Middleware execution points

In global context, there's two execution points available:

* **pre_controller**:  middleware will be executed right after the controller constructor, *BUT* before any controller action were performed. Please notice that the controller constructor is called at this point, because the own CodeIgniter's execution sequence (wich Luthier-CI don't modify) so if you need to do some actions *before* any middleware is executed, you must define a method called *preMiddleware()* in your controller and Luthier-CI will call it first.
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
````

The middleware files must be saved in the `application/middleware` folder. If not exists, you must create it. A middleware file is any php class with a public `run()` method, which is the entry point. It's strongly adviced to name all your middleware with CamelCase and avoid name conflicts with your controllers.  
  
#### Manually running middleware
  
In your controllers, you can call a middleware with the `middleware->run()` method:  
  
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
  
You can use callbacks in your middleware definitions and works exactly as route actions callbacks:  
  
```php
Route::middleware(function(){
    ci()->load->library('twig');
}, 'pre_controller');
```

## Roadmap
  
* CLI Routes :(
* Write a better and in-depth documentation 
* Probably more, feel free to make all your suggestions!

## Donate

Enjoying Luthier-CI? Donate with [Paypal](https://paypal.me/andersalasm) and help me to make more cool stuff!