# Luthier

*Version 1.0.2-alpha*  

Improved routing and middleware support for CodeIgniter Framework. Luthier is a set of classes that extends the framework and helps with the development of large and complex applications.

## Key features

* Laravel-inspired static Route class
* Route groups
* Resource (RESTFul) controllers
* Middleware support

## Installation

#### Step 1: Get Luthier with Composer

```
composer require luthier/luthier 1.0.2-alpha
```

**Please note:** Luthier is still an Alpha version. Avoid use it on production environments!

#### Step 2: Enable *hooks* and *Composer autoload* in your application

Go to your `config/config.php` file and make sure that:  
  
```php
$config['enable_hooks'] = TRUE;
```
  
and  
  
```php
$config['composer_autoload'] = TRUE;
```  
  
#### Step 3: Initialize the Luthier hooks
  
Go to your `config/hooks.php` file and add this at the top of the script:  
  
```php
use Luthier\Core\Loader as LuthierLoader;

$hook = LuthierLoader::init();
```

#### Step 4: Add your routes
  
Now you can start adding routes in your `config/routes.php` file using the `Route` static methods. In the first line add the following:
  
```php
use Luthier\Core\Route;
```

And then, for example:

```php
Route::get('foo', ['uses' => 'controller@method', 'as' => 'my-awesome-named-route']);
```
  
Once you added all your routes, call the `Route::register()` method in the `$route` variable to compile all your routes.

Example:

```php
use Luthier\Core\Route;

Route::get('foo', ['uses' => 'controller@method', 'as' => 'my-awesome-named-route']);
Route::home('home@index'); // Default controller

$route = Route::register(); 
// This compile all the Luthier routes in the CodeIgniter's native format

```


**and you're done!**


## Routes


#### Example 1: Defining a GET route:

```php
Route::get('foo/bar', ['uses' => 'testcontroller@index']);
```
This will be triggered with a GET request over the path *foo/bar* and will call the `index()` method of `testcontroller`

#### Example 2: Defining a POST route:

```php
Route::post('foo/bar', ['uses' => 'testcontroller@another']);
```

This will be triggered with a POST request over the path *foo/bar* and will call the `another()` method of `testcontroller`

As you probably noticed, the anterior routes have the same path. Don't worry, Luthier can handle the correct controller over multiples paths depending of the request method.
  
Luthier works with the most common HTTP Verbs:
  
* Route::get();
* Route::post();
* Route::put();
* Route::patch();
* Route::delete();

However, you can use any what you want, with the `Route::add()` method:

```php
Route::add('OPTIONS', ['uses' => 'foo@bar']);
```
  

#### Named routes

You can assign names to your routes so you don't have to worry about future path changes:

```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo']);
```

In your views, you can use the function `route()` with the desired name to retrieve the actual path:

```php
<a href="<?= route('foo');?>">My link!</a>
//Produces: <a href="http://myapp.com/hello/world">Link</a>
```

#### Route namespaces

If you have subdirectories in your controllers folder, you can specify a *pseudo-namespace* in your routes to indicate to CodeIgniter the actual folder structure to reach the desired controller.

*Example:*

```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo', 'namespace' => 'admin']);
```

Will be point to *application/controllers/admin/Testcontroller.php*

#### Route prefix

You can set a *prefix* to your routes with the following syntax:

```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo', 'prefix' => 'admin']);
```

So the route will be accesed with the 'admin/hello/world' instead 'hello/world'

#### The default controller:

You can set the default controller using the method `home()`:

```php
Route::home('home@index');
```

It will set the ```Home.php``` file inside the `controllers` folder with the method `index()` as your default controller.

### Route groups

You can group your routes in a convenient way using the `group()` method:

```php
Route::group(['prefix' => 'foo'], function(){
    Route::get('bar', ['uses' => 'test@bar']);
    Route::get('baz', ['uses' => 'test@baz']);
});
```

The route groups allows to encapsulate a set of routes with a prefix and namespace.

While you can omit the namespace, the route group prefix is mandatory.

## Middleware

The middleware allows you to add *layers* in your requests before accessing the controllers.

### Creating middleware

All your middleware must be saved in the *application/middleware* folder. Both the file name and the class name must have the `_middleware` suffix .

*Basic example:*

```php
<?php
// application/middelware/Auth_middleware.php

use Luthier\Core\Middleware;

class Auth_middleware extends Middleware
{

    public function __construct()
    {
    	 // You MUST call the parent constructor:
         parent::__construct();
    }

	// This is the middleware entry point:
    public function run()
    {
         // The current CodeIgniter instance is available as $this->CI
         if( is_null( $this->CI->session->userdata('logged_in') )
         {
              echo 'You must be logged in to view this resource!';
              die;
         }

    }
}
```
All your middleware must extend the `Middleware` class. The method `run()` is the entry point of the middleware.

### Including middleware in your routes

*Example 1: single route middleware*

```php
Route::get('foo', ['uses' => 'mycontroller@method', 'middleware' => ['Auth']]);
```

*Example 2: group middleware*

```php
Route::group(['prefix' => 'foo', 'middleware' => ['Auth'] ], function(){
    Route::get('bar', ['uses' => 'test@bar']);
    Route::get('baz', ['uses' => 'test@baz']);
});
```
