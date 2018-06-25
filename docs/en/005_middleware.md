[//]: # ([author] Anderson Salas, translated by Julio Cedeño)
[//]: # ([meta_description] Explore the concept of Middleware that Luthier CI introduces into your CodeIgniter applications and learn to use it with practical examples)

# Middleware

### Contents

1. [Introduction](#introduction)
2. [Middleware execution points](#middleware-execution-points)
3. [Create a middleware](#create-a-middleware)
4. [Assign a middleware](#assign-a-middleware)
   1. [Global Middleware](#global-middleware)
   2. [Route middleware](#route-middleware)
5. [Run a middleware](#run-a-middleware)
   1. [Middleware parameters](#middleware-parameters)
   2. [External middleware](#external-middleware)

### <a name="introduction"></a> Introduction

Think of middleware as a set of _layers_ that requests must go through in your application in order to reach a resource.

With the middleware you can, for example, validate that a user is logged in and has sufficient permissions to access certain sections of your application, and redirect it to another place otherwise.

The middleware is, in fact, an extension of the controller, because the singleton of the framework has already been built at this point and you can get it using the `ci()` function.

### <a name="middleware-execution-points"></a> Middleware execution points

Two execution points are available:

* `pre_controller`: the middleware defined at this point will be executed after the controller constructor, _BUT_ before any controller action is performed.
* `post_controller`: the middleware defined at this point will run exactly on the native `post_controller` hook of CodeIgniter.

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>The controller constructor always executed first</strong>
    <br />
    This is the behavior of CodeIgniter and Luthier CI does not modify it.
</div>

It's possible that at some point you need to execute code before the middleware, the way to do it is by defining a public method in your controller called `preMiddleware`:

```php
<?php
# application/controllers/TestController.php

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function preMiddleware()
    {
        // This will be executed after the constructor (if it exists), but before the middleware
    }
}
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Not available in callbacks as routes</strong>
    <br />
    When you use callbacks as routes there is no way to execute arbitrary code before the middleware
</div>

### <a name="create-a-middleware"></a> Create a middleware

All middleware must be saved in the `application/middleware` folder. A middleware is any PHP class with a `run()` public method.

Example:

```php
<?php
# application/middleware/TestMiddleware.php

class TestMiddleware implements Luthier\MiddlewareInterface
{
    public function run()
    {
        // This is the entry point of the middleware
    }
}
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Implementing the <code>MiddlewareInterface</code> interface will be mandatory</strong>
    <br />
    As of 0.3.0 version, the use of Middleware classes that do not implement the <code>Luthier\MiddlewareInterface </code> interface is DEPRECATED and will stop working in the next version</div>

In order to assign a middleware in your application it's necessary that both the name of the class and the name of the file are exactly the same. Also, be careful not to use the same name as some other resource in the framework, such as a controller, model, library, etc.

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Add <em>Middleware</em> suffix</strong>
    <br />
    One way to avoid conflicts is by adding the <em>Middleware</em> suffix to the middleware name.
</div>

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Create a middleware from the command line</strong>
    <br />
    If you have activated the built-in CLI tools of Luthier CI, use the <code>luthier make middleware [name]</code> command to create a new middleware
</div>

### <a name="assign-a-middleware"></a> Assign a middleware

You can assign a middleware in different contexts of your application:

#### <a name="global-middleware"></a> Global middleware

To define a middleware in a **global** context, use this syntax:

```php
Route::middleware([name], [exec_point?]);
```

Where `name` is the name of the middleware and `exec_point` is the execution point, which by default is `pre_controller`.

You can use an anonymous function instead of the name of a middleware:

```php
Route::middleware(function(){
    ci()->load->view('global_header');
});
```

#### <a name="route-middleware"></a> Route middleware

In the **route group** context, middleware is another property, so it goes in the third argument of the `group()` method:

```php
Route::group('site', ['middleware' => ['AuthMiddleware']], function(){

});
```

Finally, in the **individual route** context, middleware is also another property, so it goes in the second argument:

```php
Route::put('foo/bar','controller@method', ['middleware' => ['TestMiddleware']]);
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Only at the pre_controller point</strong>
    <br />
    When you assign a middleware to routes and route groups, the execution point is ALWAYS <code>pre_controller</code>
</div>

### <a name="run-a-middleware"></a> Run a middleware

For run a middleware from a controller, use the `run()` method of the `middleware` property:

```php
<?php
# application/controllers/TestController.php

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function __construct()
    {
        $this->middleware->run('AuthMiddleware');
    }
}
```

#### <a name="middleware-parameters"></a> Middleware parameters

The `run()` method of the `middleware` property supports a second argument with the middleware parameters:

```php
// $args can be any variable type:

$args = ['foo' => 'bar'];
$this->middleware->run('AuthMiddleware', $args);
````

#### <a name="external-middleware"></a> External middleware

It is possible to run middleware from an external class, as long as it has a public method called `run()`

```php
<?php
# application/controllers/TestController.php

defined('BASEPATH') OR exit('No direct script access allowed');

use Vendor\CustomMiddleware;

class TestController extends CI_Controller
{
    public function __construct()
    {
        $this->middleware->run(new CustomMiddleware());
    }
}
```
