# Routes

The route creation is a fundamental task during the development of any web application. Luthier CI improves the routing of CodeIgniter so that building large applications is not excessively complicated.

<!-- %index% -->

### Differences between CodeIgniter and Luthier CI routing

The way how routes are handled by CodeIgniter is modified by Luthier CI during its execution:

* In CodeIgniter, by default, routes are accessible through any HTTP verb. With Luthier CI it is mandatory to define the accepted HTTP verbs in each path.
* In CodeIgniter it is possible to access controllers without defining routes, while with Luthier CI only defined routes are detected.
* With Luthier CI each route is an independent and unique entity, with well-defined parameters and the ability to build URLs from them.
* With Luthier CI it is possible to use anonymous functions as controllers and even build a complete web application without using a single controller.

### Types of routes

Three types of routes are available in Luthier CI:

* **HTTP routes**: accessed under HTTP requests and defined in the `application/routes/web.php` file.
* **AJAX routes**: accessed only under AJAX requests and defined in the `application /routes/api.php` file.
* **CLI routes**: accessed only under a CLI (Command Line Interface) environment and defined in the `application/routes/cli.php` file.


<div class="alert alert-success">
    Although you can define AJAX routes in the web.php file, the ideal is to do it in <code>api.php</code>
</div>

### Syntax

If you have used Laravel then you will know how to write routes in Luthier CI, because its syntax is very similar. This is an example of a Luthier CI route:

```php
Route::get('foo', 'bar@baz');
```

Where:

* **foo** is the route URL, and
*  **bar@baz** is the name of the pointed controller and method, separated by **@**.

The `Route::get()` method states that the route accepts only `GET` requests.

<div class="alert alert-warning">
    If you define two or more routes with the same URL and the same HTTP verb, the first one will always be used.
</div>

You can define routes for the verbs GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS and TRACE using the following methods of the `Route` class:

```php
Route::post('foo', 'bar@baz');
Route::put('foo', 'bar@baz');
Route::patch('foo', 'bar@baz');
Route::delete('foo', 'bar@baz');
Route::head('foo', 'bar@baz');
Route::options('foo', 'bar@baz');
Route::trace('foo', 'bar@baz');
```

You can pass an array with the route _properties_ as a third argument:

```php
Route::get('test', 'controller@method', ['prefix' => '...', 'namespace' => '...', (...)] );
```
:
It is also possible to accept multiple HTTP verbs in a route, using the `Route::match()` method:

```php
Route::match(['GET', 'POST'], 'path', 'controller@method', [ (...) ]);
```

#### Namespaces

The **namespace** property tells CodeIgniter the sub-directory where the controller is located:

```php
// The controller will point to application/controllers/foo/Bar.php
Route::get('hello/world', 'bar@index', ['namespace' => 'admin']);
```

<div class="alert alert-info">
    Note that this is not a PHP <em>namespace</em>, but a directory name.
</div>

#### Prefixes

Use the **prefix** property to add prefixes to routes:

```php
// The URL will be 'admin/hello/world'
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

#### Named routes

It is advisable to assign a name to your routes. This will allow you to build URLs in your views and controllers:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

To get a route by name use the `route($name)` function, where `$name` is the name of the route:

```php
route('about_us');
```

<div class="alert alert-warning">
    Declaring two or more routes with the same name will result in an exception
</div>

#### Groups

Use the `Route::group method($prefix,$routes)` to define a group of routes, where `$prefix` is ​​the prefix and `$routes` is an anonymous function that contains the sub-routes:

```php
Route::group('my_prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

It is possible to assign **properties** to all routes within the group, using the syntax `Route::group($prefix, $properties, $routes)`:

```php
Route::group('prefix', ['namespace' => 'foo', 'middleware' => ['Admin','IPFilter']], function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

#### Resource Routes

Resource paths are a shortcut to create the routing of CRUD operations (**C**reate, **R**ead, **Up**date, **D**elete) for a controller.

To create a resource route, use the `Route::resource($name,$controller)` method, where `$name` is the name/prefix of the routes and `$controller` is the name of the controller:


```php
Route::resource('photos','PhotosController');
```

Result:

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

It is possible to define partial resource routes, using the syntax `Route::resource($name, $controller, $include)`, where `$include` is an (inclusive) array of the paths to be created:


```php
Route::resource('photos','PhotosController', ['index','edit','update']);
```

Resultado:

```php
[Name]                 [Path]               [Verb]          [Controller action]
photos.index           photos               GET             PhotosController@index
photos.edit            photos/{id}/edit     GET             PhotosController@edit
photos.update          photos/{id}          PUT, PATCH      PhotosController@update
```

#### Default controller

Luthier CI automatically sets any route defined with the `/` URL and the **GET** HTTP verb as the default controller.

You can explicitly define the default controller using the `Route::set('default_controller', $name)` method, where `$name` is the default controller:

```php
Route::set('default_controller', 'welcome/index');
```

#### Anonymous functions as routes

It is not necessary to provide a controller/method name to define a route in Luthier CI. You can use anonymous functions (or closures) as controllers:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});
```

<div class="alert alert-info">
    To access the instance (singleton) of CodeIgniter within anonymous functions, use the  <code>ci()</code> helper
</div>

### Route parameters

Parameters are dynamic sections of a route URL, allowing that multiple URLs resolves to the same route. To define parameters, enclose them between {keys}, for example:
Los parámetros son secciones dinámicas de la URL de una ruta, haciendo posible que múltiples URLs resuelvan a la misma ruta. Para definir parámetros, enciérralos entre `{llaves}`, por ejemplo:

```php
Route::post('blog/{slug}', 'blog@post');
```

<div class="alert alert-warning">
    You cannot define two or more parameters with the same name
</div>

#### Optional parameters

To set a parameter as optional, add a `?` before closing the keys:

```php
Route::put('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');
```

Keep in mind that after the first parameter defined as optional ALL others parameters must be optional.

<div class="alert alert-success">
    Luthier CI will generate for you the complete route tree for all optional parameters, so you don't have to worry about writing more routes besides the main one.
</div>

#### Regular expressions in parameters

You can limit the content of a route parameter to a specific character set:

```php
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');
```

The `num:` and `any:` placeholders are equivalent to `(:num)` and `(:any)`, respectively.

It is also possible to use a regular expression to define route parameters:

```php
Route::get('main/{((es|en)):_locale}/about', 'about@index');
```

The above is equivalent to `/^(is|en)$/`.

#### Sticky parameters

When you work with route groups that define parameters, they must be declared as arguments in their controller methods *recursively*. Depending on the complexity of your application, the inherited parameters will accumulate, making the methods of your controllers have a very large number of arguments.

The **sticky parameters** helps you deal with this problem.

An adhesive parameter is any route parameter that begins with an underscore (`_`). They have the following properties:

* It is not necessary to define it in the arguments of the sub-routes controller methods.
* The value of the parameter will be taken from the URL and will be automatically supplied in the `route()` function, so it can be omitted, or overwritten by any other value.

Consider the following route group:

```php
Route::group('shop/{_locale}', function()
{
    Route::get('category/{id}', 'ShopCategory@categoryList')->name('shop.category');
    Route::get('product/{id}/details', 'ShopProduct@details')->name('shop.product.details');
});
```

The `shop.category` and `shop.product.details` routes share the `_locale` sticky parameter and, while is still required to be in the URL, you can skip it when you build routes within this group:

```php
// If the URL is 'shop/en/category/1', then {_locale} will be 'en' here:

echo route('shop.category', ['id' => 1]);
# shop/en/category/1

echo route('shop.category', ['id' => 2]); 
# shop/en/category/2

echo route('shop.category', ['id' => 3]); 
# shop/en/category/3
```

This is useful when you need to link to other variants of the current route:

```php
echo route('shop.category', ['_locale' => 'es', 'id' => 1]); 
# shop/es/category/1
```

Within the `ShopCategory` and `ShopProduct` controllers, their methods will have a single argument: `$id`:

```php
# application/controllers/ShopCategory.php
class ShopCategory extends CI_Controller
{
    public function categoryList($id)
    {
        // (...)
    }
}

# application/controllers/ShopProduct.php
class ShopProduct extends CI_Controller
{
    public function details($id)
    {
        // (...)
    }
}
```

To obtain the value of an adhesive parameter within a controller, use the `param($name)` method of the `route` property, where `$name` is the name of the parameter:


```php
public function categoryList($id)
{
    $locale = $this->route->param('_locale');
}
```