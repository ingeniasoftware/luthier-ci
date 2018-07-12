[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] Lee la documentación oficial de Luthier CI y descubre las posibilidades que te ofrece, mira ejemplos, casos de uso y mucho más)

# Bienvenido

### Acerca de Luthier CI

**Luthier CI** es un complemento para CodeIgniter que añade interesantes características pensadas para facilitar la construcción de sitios web grandes y APIs en general. Fué hecho para integrarse lo mejor posible con el framework, de modo que todo lo que ya existe en tu aplicación debe seguir funcionando sin problemas luego de instalar Luthier CI.

Esta documentación asume que tienes conocimientos básicos sobre CodeIgniter. Si nunca has usado CodeIgniter, un buen punto de partida es su [documentación oficial](https://www.codeigniter.com/user_guide) (en inglés)

Luthier CI es software libre y está disponible bajo una licencia MIT.

### Características

#### Enrutamiento mejorado

Luthier CI reemplaza la forma en que defines las rutas en tu aplicación por una sintaxis inspirada en Laravel.

Por ejemplo, en lugar de definir un enorme arreglo de rutas parecido a esto:

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

...puedes escribir lo mismo de forma más compacta:

```php
Route::group('catalog', function(){
    Route::get('cars/{category_id}/{filter_by?}', 'CarsController@catalog');
    Route::match(['get','post'], 'bikes/{category_id}/{filter_by?}', 'BikesController@catalog');
    Route::get('airplanes/{category_id}/{filter_by?}', 'AirplanesController@catalog');
});
```

Además, Luthier CI te ayuda a mantener tus rutas organizadas, pues cada tipo de ruta tiene su propio archivo donde debe definirse: hay un archivo para rutas HTTP, otro para rutas AJAX y otro para rutas CLI.

#### Middleware

Luthier CI introduce el concepto de _Middleware_ en el framework.

Usado correctamente, el middleware puede ayudarte a crear filtros y acciones en tus controladores que, de otra forma, serían muy tediosas de implementar usando _librerías_ y _helpers_.

Puedes usar el middleware tanto en rutas específicas como en grupos de rutas, o incluso a nivel global en tu aplicación.

#### Fácil instalación

Luthier CI se instala a través de Composer y utiliza los _hooks_ de CodeIgniter para integrase en tu aplicación. Olvídate de copiar o mover archivos o de seguir listas enormes de pasos para poner a funcionar Luthier CI ¡en la mayoría de los casos la instalación no toma más de 5 minutos!

### Comunidad y soporte

Para reportar errores y proponer cambios por favor visita el repositorio de [Luthier CI en Github](https://github.com/ingeniasoftware/luthier-ci)