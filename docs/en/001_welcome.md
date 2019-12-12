# Welcome

**Luthier CI** is a CodeIgniter plugin that adds features designed to facilitate the construction of websites and APIs in general.

This documentation assumes that you have basic knowledge about CodeIgniter. If you have never used CodeIgniter, we recommend you read their [official documentation](https://www.codeigniter.com/user_guide).

Luthier CI is free software and is available under an MIT license.

### Features

#### Improved routing

With Luthier CI installed in your application, instead of defining a route array like this:

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

You can write it in a more compact way:

```php
Route::group('catalog', function(){
    Route::get('cars/{category_id}/{filter_by?}', 'CarsController@catalog');
    Route::match(['get','post'], 'bikes/{category_id}/{filter_by?}', 'BikesController@catalog');
    Route::get('airplanes/{category_id}/{filter_by?}', 'AirplanesController@catalog');
});
```

#### Middleware

Luthier CI introduces the concept of _Middleware_ in the framework.

With the Middleware, you can define the logic that is executed before the incoming requests are handled by the controllers of your application, something that would otherwise be very tedious to implement using _libraries_ and _helpers_.

#### Easy installation

When Luthier CI was designed, one of the goals was that the installation would be the most intuitive and transparent as possible. Forget about complex installation instructions that involve downloading and copying files.

### Community and support

Your comments and suggestions are welcome. To report errors and propose changes please visit the [Luthier CI repository] in Github.