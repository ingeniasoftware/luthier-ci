# Bienvenido

**Luthier CI** es un plugin de CodeIgniter con añadidos destinados a facilitar la construcción de sitios web y APIs en general.

Esta documentación asume que tienes conocimientos básicos sobre CodeIgniter. Si nunca has usado CodeIgniter te recomendamos leer su [documentación oficial](https://www.codeigniter.com/user_guide) (en inglés)

Luthier CI es software libre y está disponible bajo una licencia MIT.

### Características

#### Enrutamiento mejorado

Con Luthier CI instalado en tu aplicación, en lugar de definir un arreglo de rutas como éste:

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

Puedes escribirlo de una forma más compacta:

```php
Route::group('catalog', function(){
    Route::get('cars/{category_id}/{filter_by?}', 'CarsController@catalog');
    Route::match(['get','post'], 'bikes/{category_id}/{filter_by?}', 'BikesController@catalog');
    Route::get('airplanes/{category_id}/{filter_by?}', 'AirplanesController@catalog');
});
```

#### Middleware

Luthier CI introduce el concepto de _Middleware_ en el framework.

Con el Middleware puedes definir la lógica que se ejecuta antes de que las peticiones entrantes sean manejadas por los controladores de tu aplicación, algo que de otra forma serían muy tedioso de implementar usando _librerías_ y _helpers_.

#### Fácil instalación

Una de las metas cuando se diseñó Luthier CI era que la instalación fuese lo más intuitiva y transparente posible tanto para el usuario como para el framework.

¡Y lo hemos logrado! Olvídate de seguir engorrosas instrucciones de instalación que involucran descargar y copiar archivos.

### Comunidad y soporte

Tus comentarios y sugerencias son bienvenida. Para reportar errores y proponer cambios por favor visita el repositorio de [Luthier CI en Github](https://github.com/ingeniasoftware/luthier-ci).