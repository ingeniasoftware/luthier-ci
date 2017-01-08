# Luthier

*Version 1.0a*
*By the Ingenia Software team*

Luthier is a sub-framework of CodeIgniter that improves the Routing and introduces Middleware functions in your controllers.

Has been inspired in the Laravel static routes and makes the job of writting APIs a little bit more easy.

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
    Middleware::routeMiddleware();
};
```

**And you're done!**

## Ussage and examples

You don't need to make nothing special, just start adding routes using the static methods of the ```Route``` class in your *config/routes.php* file.

After you added all your routes, call the method ```Route::register()``` on the ```$route``` var:

```php
// application/config/routes.php

// (All your routes ...)
$route = Route::register();
```

#### Single method routes

```php
Route::get('foo/bar', ['uses' => 'testcontroller@index']);
```

This will be triggered with a GET request over the path 'foo/bar' and will call the ```index()``` method of ```testcontroller```

```php
Route::post('foo/bar', ['uses' => 'testcontroller@another']);
```

This will be triggered with a POST request over the path 'foo/bar' and will call the ```another()``` method of ```testcontroller```

Notice that you can handle multiples HTTP verbs over the same path

#### Named routes

You can assign names to your routes so you don't have to worry about future path changes:

 ```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo']);
```

In your views, you can use the function ```route()``` to retrieve the actual path:

```php
<a href="<?= route('foo');?>">My link!</a>
//Produces: <a href="http://myapp.com/hello/world">Link</a>
```

#### Namespaces and prefix

Probably you have subdirectories in your controllers folder, so you can specify a *pseudo-namespace* in your routes to indicate to CodeIgniter the actual folder structure to reach the desired controller.

The route:

 ```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo', 'namespace' => 'admin']);
```
Will be point to *application/controllers/admin/Testcontroller.php*

You can set a *prefix* to your routes

 ```php
Route::get('hello/world', ['uses' => 'testcontroller@index', 'as' => 'foo', 'prefix' => 'admin']);
```

So the route will be accesed with the 'admin/hello/world' instead 'hello/world' . This makes more sense in the routes groups.

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

You can assign an prefix and a namespace to your routes. However, the prefix is mandatory.

### Route middleware

This allows you to add *layers* in your requests before accesing the controllers. All the middleware must extend the ```Middleware``` class and must be saved in the ```middleware```. Both the filename and the class must have the suffix ```_middleware```.

Here's a basic example:

```php
// application/middelware/Auth_middleware.php
class Auth_middleware extends Middleware
{
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

In your route definition:
```php
Route::get('foo', ['uses' => 'test@controller', 'middleware' => ['Auth']]);
```

The documentation of the complete features of Luthier will be placed in the repository [wiki](https://github.com/ingeniasoftware/luthier/wiki) soon!


* [Twig library for CodeIgniter]()
