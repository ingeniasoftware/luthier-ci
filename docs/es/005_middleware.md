[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] Explora el concepto de Middleware que Luthier-CI introduce en tus aplicaciones de CodeIgniter y aprende a utilizarlo con ejemplos prácticos)

# Middleware

### Contenido

1. [Introducción](#introduction)
2. [Puntos de ejecución de middleware](#middleware-execution-points)
3. [Crear un middleware](#create-a-middleware)
4. [Asignar un middleware](#assign-a-middleware)
  1. [Middleware global](#global-middleware)
  2. [Middleware de ruta](#route-middleware)
  3. [Llamando a un middleware desde un controlador](#calling-a-middleware-from-a-controller)

### <a name="introduction"></a> Introducción

Piensa en el middleware como una serie de _capas_ que deben atravesar las solicitudes hechas a un recurso de tu aplicación para poder llegar a él.

Con el middleware puedes, por ejemplo, validar que un usuario esté logueado y tenga permisos suficientes para de acceder a ciertas secciones de tu aplicación, y redirigirlo a otro lugar en caso contrario.

El middleware es, de hecho, una extensión del controlador, porque el _singleton_ del framework ya se ha construído en este punto y puedes obtenerlo usando la funcion `ci()`.

### <a name="middleware-execution-points"></a> Puntos de ejecución de middleware

Están disponibles dos puntos de ejecución:

* `pre_controller`: el middleware definido en este punto se ejecutará después del constructor del controlador, _PERO_ antes de que cualquier acción del controlador sea efectuada.
* `post_controller`: el middleware definido en este punto se ejecutará exactamente en el hook `post_controller` nativo de CodeIgniter.

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>El constructor del controlador siempre se ejecuta primero</strong>
    <br />
    Este es el comportamiento de CodeIgniter y Luthier-CI no lo modifica.
</div>

Es posible que en algún momento requieras ejecutar código antes del middleware, la manera de hacerlo es definiendo un método público en tu controlador llamado `preMiddleware`:

```php
<?php
# application/controllers/TestController.php

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function preMiddleware()
    {
        // Esto se ejecutará después del constructor (si existe), pero antes del middleware
    }
}
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>No disponible en funciones anónimas como rutas</strong>
    <br />
    Cuando usas funciones anónimas como rutas no existe una forma de ejecutar código arbitrario antes del middleware
</div>

### <a name="create-a-middleware"></a> Crear un middleware

Todo el middleware debe guardarse en la carpeta `application/middleware`. Un middleware es cualquier clase PHP con un método público llamado `run()`.

Ejemplo:

```php
<?php
# application/middleware/Test_middleware.php

class Test_middleware
{
    public function run()
    {
        // Este es el punto de entrada del middleware
    }
}
```

Para poder asignar un middleware en tu aplicación es necesario que tanto el nombre de la clase como el nombre el archivo sean exactamente iguales. Además, debes tener cuidado de no usar el mismo nombre de algún otro recurso del framework, como un controlador, modelo, librería, etc.

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Añade el sufijo _middleware</strong>
    <br />
    Una forma de evitar conflictos es añadiendo el sufijo _middleware al nombre del middleware.
</div>

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Crea un middleware desde la linea de comandos</strong>
    <br />
    Si has activado las herramientas CLI integradas de Luthier-CI, usa el comando <code>luthier make middleware [nombre]</code> para crear un nuevo middleware
</div>

### <a name="assign-a-middleware"></a> Asignar un middleware

Puedes asignar un middleware en diferentes contextos de tu aplicación:

#### <a name="global-middleware"></a> Middleware global

Para definir un middleware en un contexto **global**, la sintaxis es la siguiente:

```php
Route::middleware([name], [exec_point?]);
```

Donde `name` es el nombre del middleware y `exec_point` es el punto de ejecución, que por defecto es `pre_controller`.

Puedes usar una función anónima en lugar del nombre de un middleware:

```php
Route::middleware(function(){
    ci()->load->view('global_header');
});
```

#### <a name="route-middleware"></a> Middleware de ruta

En el contexto de un **grupo de rutas**, el middleware es otra propiedad más, así que va en el tercer argumento del método `group()`:

```php
Route::group('site', ['middleware' => ['Admin']], function(){

});
```

Por último, en el contexto de una **ruta individual**, el middleware es también otra propiedad más, así que va en el tercer argumento:

```php
Route::put('foo/bar','controller@method', ['middleware' => ['Test']]);
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Sólo en el punto pre_controller</strong>
    <br />
    Cuando asignas un middleware a rutas y grupos de rutas, el punto de ejecución SIEMPRE es <code>pre_controller</code>
</div>

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>No admiten funciones anónimos</strong>
    <br />
    El middleware asignado en rutas y grupos de rutas no admite el uso de funciones anónimas
</div>

#### <a name="calling-a-middleware-from-a-controller"></a> Llamando a un middleware desde un controlador

Aunque quizás no sea la forma más generalizada de usarlo, puedes llamar a un middleware desde un controlador, para ello usa el método `run()` de la propiedad `middleware`:

```php
<?php
# application/controllers/TestController.php

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function __construct()
    {
        $this->middleware->run('Auth_middleware');
    }
}
```