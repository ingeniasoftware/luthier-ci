# Luthier

*Version 1.0a*

Luthier is a set of functions that extends CodeIgniter core and improves the development of large and complex sites.

## Key features

* Named routes
* Route groups
* Route *namespaces* and *prefix*
* Resource (RESTFul) routes
* Middleware

## Installation

**Step 1:**  Download and unzip inside your *application* folder.

**Step 2:** Open your *application/config/config.php* file an make sure that ```$config['enable_hooks']``` is ```TRUE```

**Step 3:** Define the following constants in *config/constants.php*:

```php
defined('DS')         OR define('DS', DIRECTORY_SEPARATOR);
defined('ROOTPATH')   OR define('ROOTPATH', dirname(BASEPATH).DS);
defined('CONFIGPATH') OR define('CONFIGPATH', ROOTPATH.'application'.DS.'config'.DS);
defined('MODULEPATH') OR define('MODULEPATH', ROOTPATH.'application'.DS.'modules'.DS);
defined('ASSETSPATH') OR define('ASSETSPATH', ROOTPATH.'assets'.DS);
```

**Step 4:**  In your *application/config/hooks.php* file, add this hooks:

```php
/**
 *  Luthier Route hook
 * ---------------------------------------------------------------------------------------
 */
$hook['pre_system'][] = function()
{
    require APPPATH.'luthier'.DS.'class'.DS.'Route.php';

    function route($name, $args = NULL)
    {
        return Route::getRouteByName($name, $args);
    }
};

/*
 * Luthier Middleware hook
 * ---------------------------------------------------------------------------------------
 */
$hook['post_controller_constructor'][] = function()
{
    require APPPATH.'luthier'.DS.'class'.DS.'Middleware.php';

    Middleware::init();
};
```

**And you're done!**

## Ussage and examples

### Routes

Luthier uses a Laravel inspired routing. You can define routes using the static methods of the ```Route``` class in your *config/routes.php* file.

```php
// application/config/routes.php

Route::home('home@index');
Route::get('about', ['uses' => 'page@about']);
Route::get('portfolio', ['uses' => 'page@portfolio']);
Route::get('contact', ['uses' => 'page@contact']);
$route = Route::register();
```

Luthier doesn't replace the CodeIgniter routing system. After defining your routes you must call the ```Route::register()``` method in the ```$route``` var to compile all your routes in the CodeIgniter built-in format:

```php
// The actual output:

$route['about'] = 'page/about';
$route['portfolio'] = 'page/portfolio';
$route['contact'] = 'page/contact';

$route['default_controller'] = 'home/index';
$route['404_override'] = ''; // Default value
$route['translate_uri_dashes'] = FALSE; // Default value
```

Luthier supports the most used HTTP Verbs: GET, POST, PUT, PATCH, DELETE, but you can add more if you want.

#### Single method routes

*Example 1: Defining a GET route:*

```php
Route::get('foo/bar', ['uses' => 'testcontroller@index']);
```
This will be triggered with a GET request over the path *foo/bar* and will call the ```index()``` method of ```testcontroller```

*Example 2: Defining a POST route:*

```php
Route::post('foo/bar', ['uses' => 'testcontroller@another']);
```

This will be triggered with a POST request over the path *foo/bar* and will call the ```another()``` method of ```testcontroller```

As you probably noticed, both routes have the same path. Don't worry, Luthier can handle the correct controller over multiples paths depending of the request method.

#### Named routes

You can assign names to your routes so you don't have to worry about future path changes:

 ```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo']);
```

In your views, you can use the function ```route()``` with the desired name to retrieve the actual path:

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

You can set the default controller using the method ```home()```:

```php
Route::home('home@index');
```

It will set the ```Home.php``` file inside the ```controllers``` folder with the method ```index()``` as your default controller.

### Route groups

You can group your routes in a convenient way using the ```group()``` method:

```php
Route::group(['prefix' => 'foo'], function(){
    Route::get('bar', ['uses' => 'test@bar']);
    Route::get('baz', ['uses' => 'test@baz']);
});
```

The route groups allows to encapsule a set of routes with a prefix and namespace.

While you can ommit the namespace, the route group prefix is mandatory.

## Middleware

The middleware allows you to add *layers* in your requests before accesing the controllers. All the middleware must extend the ```Middleware``` class and must be saved in the ```middleware```. Both the filename and the class must have the suffix ```_middleware```.

### Creating middlewares

Your middlewares must be saved in the *application/middleware* folder. Both the file name and the class name must have the suffix ```_middleware```.

*Basic example:*

```php
// application/middelware/Auth_middleware.php
class Auth_middleware extends Middleware
{
    // You must call the parent constructor:
     public function __construct()
     {
           // This emulates the controller singleton made by
           // CodeIgniter, so you have access the the instance
           // using the $this->CI property
           parent::__construct();
     }

    public function run()
    {
         if( is_null( $this->CI->session->userdata('logged_in') )
         {
              echo 'You must be logged in to view this resource!';
              die;
          }

    }
}
```
All your middleware must extend the ```Middleware``` class. The method ```run()``` is the entry point of the middleware.

### Including middlewares in your routes

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

For the complete documentation, look the repository's [wiki](https://github.com/ingeniasoftware/luthier/wiki)!

**Tired of the CodeIngiter views?, try Twig:**

[Twig library for CodeIgniter](https://github.com/andersonsalas/ci_twig)
