# Middleware

Middleware is a series of _layers_ that incoming HTTP requests must go through before reaching the controllers of your application. This concept is introduced to CodeIgniter by Luthier CI and opens up a range of possibilities in the framework.

<!-- %index% -->

### Middleware execution points

There are two possible execution points for the Middleware in Luthier CI, and they are related to the loading and execution of CodeIgniter controllers:

* `pre_controller`: the Middleware defined at this point will run after the controller's constructor, BUT before any other method is executed.
* `post_controller`: the Middleware defined at this point will run exactly on the `post_controller` CodeIgniter native hook.

<div class="alert alert-warning">
    The controller constructor <strong>always</strong> runs first
</div>

It is possible that at some point you need to execute code before your Middleware, and the way to do it is by defining a public method in your controller called `preMiddleware`:

```php
<?php
# application/controllers/TestController.php

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function preMiddleware()
    {
        // (...)
    }
}
```

<div class="alert alert-warning">
    When you use <strong>anonymous functions</strong> as controllers there is no way to execute arbitrary code before Middleware
</div>

### Create a Middleware

All Middleware should be stored in the `application/middleware` folder. A Middleware is any PHP class that implements the `Luthier\MiddlewareInterface` interface.

The `Luthier\MiddlewareInterface` interface only defines a method called `run()`, which is the entry point of the Middleware.

For example:

```php
<?php
# application/middleware/TestMiddleware.php

class TestMiddleware implements Luthier\MiddlewareInterface
{
    public function run()
    {
        // (...)
    }
}
```

<div class="alert alert-warning">
    As of version 0.3.0, the use of Middleware classes that do not implement the <code>Luthier\MiddlewareInterface</code> interface is OBSOLETE and will stop working in the next version
</div>

It is necessary that both the class name and the file name are exactly the same.

<div class="alert alert-warning">
    Avoid using a name of another framework existing resource, such as a controller, model, or library.
</div>

<div class="alert alert-success">
    A good practice is to add the <em>Middleware</em> suffix to the name of your Middleware.
</div>

### Assign a Middleware

You can assign a Middleware in different contexts:

#### Global Middleware

As the name implies, global Middleware runs on all incoming requests from your application:

```php
Route::middleware([name], [exec_point?]);
```

Where `name` is the name of the Middleware and `exec_point` is the execution point (by default, pre_controller)

You can use an anonymous function instead of the name of a middleware:

```php
Route::middleware(function(){
    ci()->load->view('global_header');
});
```

#### Middleware in route groups

Route middleware is defined as any other property. In the case of **route groups**, it goes in the second argument of the `Route::group()` method:

```php
Route::group('site', ['middleware' => ['AuthMiddleware']], function(){

});
```

For an **individual route**, the Middleware is also defined as a property:

```php
Route::put('foo/bar','controller@method', ['middleware' => ['TestMiddleware']]);
```

<div class="alert alert-warning">
    In individual routes and route groups, the execution point is ALWAYS <code>pre_controller</code>
</div>

### Run a middleware programatically

To run a Middleware from a controller, use the `run()` method of the `middleware` property:

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

This method supports a second argument with the parameters of the Middleware:

```php
$this->middleware->run('AuthMiddleware', ['foo' => 'bar']);
````

It is possible to run Middleware from an external class, as long as it has a `run()` public method

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