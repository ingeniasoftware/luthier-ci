[//]: # ([author] Anderson Salas, translated by Julio Cedeño)
[//]: # ([meta_description] Read the official documentation of Luthier CI and discover the possibilities it offers, see examples, use cases and much more)

# Welcome

### About Luthier CI

**Luthier CI** is a plugin for CodeIgniter that adds interesting features, designed to made easy the construction of large websites and APIs in general. It was made to integrate as best as possible with the framework, so everything that already exists in your application should continue to work without problems after installing Luthier CI.

This documentation assumes that you have basic knowledge about CodeIgniter. If you have never used CodeIgniter, a good starting point is their [official documentation](https://www.codeigniter.com/user_guide)

Luthier CI is free software and is available under a MIT license.

### Features

#### Improved routing

Luthier CI replaces the way you define routes in your application by a syntax inspired by Laravel.

For example, instead of defining a big array of routes similar to this:

```php
$route['catalog/cars/(:any)']['GET'] = 'CarsController/catalog/$1';
$route['catalog/cars/(:any)/(:any)']['GET'] = 'CarsController/catalog/$1/$2';
$route['catalog/bikes/(:any)']['GET'] = 'BikesController/catalog/$1';
$route['catalog/bikes/(:any)']['POST'] = 'BikesController/catalog/$1';
$route['catalog/bikes/(:any)/(:any)']['GET'] = 'BikesController/catalog/$1/$2';
$route['catalog/bikes/(:any)/(:any)']['POST'] = 'BikesController/catalog/$1/$2';
$route['catalog/airplanes/(:any)']['GET'] = 'AirplanesController/catalog/$1/$2';
$route['catalog/airplanes/(:any)/(:any)']['GET'] = 'AirplanesController/catalog/$1/$2';
```

... you can write it in a more compact way:

```php
Route::group('catalog', function(){
    Route::get('cars/{category_id}/{filter_by?}', 'CarsController@catalog');
    Route::match(['get','post'], 'bikes/{category_id}/{filter_by?}', 'BikesController@catalog');
    Route::get('airplanes/{category_id}/{filter_by?}', 'AirplanesController@catalog');
});
```

In addition, Luthier CI helps you to keep your routes organized, since each type of route has it's own file where it must be defined: there is one file for HTTP routes, another for AJAX routes and another for CLI routes.

#### Middleware

Luthier CI introduces the concept of _Middleware_ in the framework.

Used correctly, the middleware can help you create filters and actions on your controllers that, otherwise, would be very tedious to implement using _libraries_ and _helpers_.

You can use the middleware both in specific routes and in groups of routes, or even globally in your application.

#### Easy installation

Luthier CI is installed through Composer and uses CodeIgniter _hooks_ to integrate into your application. Forget about copying or moving files or following huge lists of steps to get Luthier CI working. In most cases the installation takes no more than 5 minutes!

### Community and support

To report errors and propose changes please visit the [Luthier CI repository on Github](https://github.com/ingeniasoftware/luthier-ci)