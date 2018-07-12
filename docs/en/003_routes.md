[//]: # ([author] Anderson Salas, translated by Julio Cedeño)
[//]: # ([meta_description] Luthier-routing CI thoroughly explained. Learn more about routes and new syntax inspired by laravel that is within your reach)

# Routes

### Contents

1. [Introduction](#introduction)
2. [Route types](#route-types)
3. [Syntax](#syntax)
   1. [Namespaces](#namespaces)
   2. [Prefixes](#prefixes)
   3. [Named routes](#named-routes)
   4. [Callbacks as routes](#callbacks-as-routes)
   5. [Groups](#groups)
   5. [Resource routes](#resource-routes)
   6. [Default controller](#default-controller)
4. [Parameters](#parameters)
   1. [Optional parameters](#optional-parameters)
   2. [Parameter regex](#parameter-regex)
   3. ["Sticky" parameters](#sticky-parameters)

### <a name="introduction"></a> Introduction

Luthier CI changes the behavior of the CodeIgniter router:

* In CodeIgniter, by default, the routes are accessible under any HTTP verb. With Luthier CI it's mandatory to define the accepted HTTP verbs for each route and any request that doesn't match these parameters will generate a 404 error.
* In CodeIgniter it's possible to access the controllers directly from the URL without the need to define routes. On the other hand, with Luthier CI, trying to access a path that is not defined (even if the URL matches the name of the controller and the method) will generate a 404 error.
* In CodeIgniter the route parameters are simple regular expressions that point to controllers, in Luthier CI a route is an independent and unique entity, which contains well-defined parameters and the ability to build URLs from them.
* In CodeIgniter you can only create routes that point to controllers. With Luthier CI it's possible to use anonymous functions as controllers and even build a complete web application without using a single controller.

### <a name="route-types"></a> Route types

You can work with three types of routes:

* **HTTP routes**: they're accessed under HTTP requests and are defined in the `application/routes/web.php` file
* **AJAX routes**: they're accessed only under AJAX requests and are defined in the `application/routes/api.php` file
* **CLI routes**: they're accessed only under a CLI (Command Line Interface) environment and are defined in the `application/routes/cli.php` file

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>AJAX routes go in api.php</strong>
    <br />
    Although you can define AJAX routes in the <code>web.php</code> file, it is best to do so in <code>api.php</code>
</div>

### <a name="syntax"></a> Syntax

If you have used Laravel then you will know how to use Luthier CI, since it's syntax is identical. This is the simplest possible example of a route:

```php
Route::get('foo', 'bar@baz');
```

Where **foo** is the URL of the route and **bar@baz** is the name of the controller and method (separated by @) to which it points. By using the `get()` method you are telling Luthier CI that the route will be available under GET requests.

<div class="alert alert-info">
    <i class="fa fa-info-circle" aria-hidden="true"></i>
    <strong>The first route is the one that wins</strong>
    <br />
    If you define two or more routes with the same URL and the same HTTP verb, the first will be returned ALWAYS
</div>

Luthier CI allows you to define HTTP routes with the verbs GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS and TRACE:

```php
Route::post('foo', 'bar@baz');
Route::put('foo', 'bar@baz');
Route::patch('foo', 'bar@baz');
Route::delete('foo', 'bar@baz');
Route::head('foo', 'bar@baz');
Route::options('foo', 'bar@baz');
Route::trace('foo', 'bar@baz');
```

Also, you can pass an array with the _properties_ of the route as a third argument (explained later).

```php
Route::get('test', 'controller@method', ['prefix' => '...', 'namespace' => '...', (...)] );
```

To accept multiple HTTP verbs in a route, use the `match()` method:

```php
Route::match(['GET', 'POST'], 'path', 'controller@method', [ (...) ]);
```

#### <a name="namespaces"></a> Namespaces


The namespace property tells CodeIgniter the sub-directory where the controller is located. (Note that this is not a PHP namespace, it's a directory name)

```php
// The controller is located in application/controllers/admin/Testcontroller.php
Route::get('hello/world', 'testcontroller@index', ['namespace' => 'admin']);
```

#### <a name="prefixes"></a> Prefixes


Use the `prefix` property to add prefixes to the routes:

```php
// The URL will be 'admin/hello/world' instead of 'hello/world'
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

#### <a name="named-routes"></a> Named routes

You can (and, in fact, it's advisable) assign a name to your routes. This will allow you to call them from other places:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

To obtain a route by it's name use the `route()` function, whose first argument is the name of the route and a second optional argument is an array with the values of the parameters of that route. For example, to obtain the previous route, just write `route('about_us')`:

```php
// http://example.com/company/about_us
<a href="<?= route('about_us');?>">My link!</a>
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Duplicated names</strong>
    <br />
    You can not call two or more routes with the same name
</div>

#### <a name="groups"></a> Groups

You can create groups of routes using the `group()` method, where the first argument is the prefix they will have in common, and the second argument is an anonymous function with the sub-routes:

```php
Route::group('prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

In addition, it's possible to assign properties in common for the groups of routes. This is an example of the extended syntax:

```php
Route::group('prefix', ['namespace' => 'foo', 'middleware' => ['Admin','IPFilter']], function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

#### <a name="resource-routes"></a> Resource routes

Resource routes allow you to define CRUD operations (**C**reate, **R**ead, **U**pdate, **D**elete) for a controller on a single line. Example:

```php
Route::resource('photos','PhotosController');
```

Produces:

```php
[Name]                 [Path]               [Verb]          [Controller action]
photos.index           photos               GET             PhotosController@index
photos.create          photos/create        GET             PhotosController@create
photos.store           photos               POST            PhotosController@store
photos.show            photos/{id}          GET             PhotosController@show
photos.edit            photos/{id}/edit     GET             PhotosController@edit
photos.update          photos/{id}          PUT, PATCH      PhotosController@update
photos.destroy         photos/{id}          DELETE          PhotosController@destroy
```

In addition, it is possible to create partial resource routes, passing a third argument with an array of the actions to be filtered:

```php
Route::resource('photos','PhotosController', ['index','edit','update']);
```

Produces:

```php
[Name]                 [Path]               [Verb]          [Controller action]
photos.index           photos               GET             PhotosController@index
photos.edit            photos/{id}/edit     GET             PhotosController@edit
photos.update          photos/{id}          PUT, PATCH      PhotosController@update

#### <a name="default-controller"></a> Default controller

Luthier CI automatically sets any route defined with the URL **/** and the HTTP verb **GET** as the default controller, however you can explicitly set it using the `set()` method and this special syntax:

```php
// Note that the value is binded to the special 'default_controller' route of CodeIgniter and you must
// use the native syntax:
Route::set('default_controller', 'welcome/index');
```

#### <a name="callbacks-as-routes"></a> Callbacks as routes

You can use anonymous functions (also called _closures_ or _lambda functions_) instead of pointing to a controller, for example:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});
```

To access the framework instance within the anonymous functions, use the `ci()` function.

### <a name="parameters"></a> Parameters

It's possible to define parameters in your routes, so that they can be dynamic. To add a parameter to a segment of the route, enclose it between `{curly brackets}`

```php
Route::post('blog/{slug}', 'blog@post');
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Duplicated parameters</strong>
    <br />
    You can not call two or more parameters with the same name
</div>

#### <a name="optional-parameters"></a> Optional parameters

To make an optional parameter, add a `?` before closing the curly brackets:

```php
Route::put('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');
```

Note that after the first optional parameter is defined, ALL the following parameters must be optional.

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Routes generated automatically</strong>
    <br />
    Luthier CI will generate the complete route tree for all the optional parameters for you, so you don't have to worry about writing more routes besides the main one.
</div>

#### <a name="parameter-regex"></a> Parameter regex

You can limit a parameter to a regular expression:

```php
// These are the equivalents of (:num) and (:any), respectively:
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');
```
Also, you can use a custom regular expression with the `{([expr]):[name]}` syntax:

```php
// This is equivalent to /^(es|en)$/
Route::get('main/{((es|en)):_locale}/about', 'about@index');
```

#### <a name="sticky-parameters"></a> "Sticky" parameters

It's possible that you need to define a parameter in a group of routes and that in turn is available in all its sub-routes, without having to define it in the arguments of all the methods in all the controllers, which is tedious. Thinking about that, Luthier CI offers the so-called **Sticky parameters**. An adhesive parameter starts with an underscore (`_`) and has some singularities:

* It's not passed in the arguments of the controller method to which the route points.
* In all the sub-routes that share the adhesive parameter, value will be taken from the URL and will be automatically supplied in the `route()` function, so you can omit it, or overwrite it for any other value.

Consider this example:

```php
Route::group('shop/{_locale}', function()
{
    Route::get('category/{id}', 'ShopCategory@categoryList')->name('shop.category');
    Route::get('product/{id}/details', 'ShopProduct@details')->name('shop.product.details');
});
```

The routes `shop.category` and `shop.product.details` shares the `_locale` sticky parameter. While that parameter is required to be in the URL, it's not mandatory that it be present in the array of parameter values when you use the `route()` function in this context. This is especially useful when you need to link to other variants of the current route:

```php
// If the URL is 'shop/en/category/1', {_locale} will be 'en' here:
echo route('shop.category', ['id' => 1]); # shop/en/category/1
echo route('shop.category', ['id' => 2]); # shop/en/category/2
echo route('shop.category', ['id' => 3]); # shop/en/category/3

// You can overwrite that value for any other:
echo route('shop.category', ['_locale' => 'es', 'id' => 1]); # shop/es/category/1
```

An advantage of the sticky parameters is that you don't have to define them as arguments of all the methods of the pointed controllers. In the previous example, within the `ShopCategory` and `ShopProduct` controllers, their methods will have a single argument: `$id`, because it's the only one supplied by the router:

```php
<?php
# application/controllers/ShopCategory.php

defined('BASEPATH') OR exit('No direct script access allowed');

class ShopCategory extends CI_Controller
{

    // Define the method as categoryList($_locale, $id) will not work: it is
	// waiting for exactly 1 argument:
    public function categoryList($id)
    {

    }
}
```

```php
<?php
# application/controllers/ShopProduct.php

defined('BASEPATH') OR exit('No direct script access allowed');

class ShopProduct extends CI_Controller
{
    // Same here:
    public function details($id)
    {

    }
}
```

To obtain the value of an sticky parameter, use the `param()` method of the `route` property within the controller:

```php
<?php
# application/controllers/ShopCategory.php

defined('BASEPATH') OR exit('No direct script access allowed');

class ShopCategory extends CI_Controller
{
    public function categoryList($id)
    {
        $locale = $this->route->param('_locale');
    }
}
```
