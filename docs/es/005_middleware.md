[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] Explora el concepto de Middleware que Luthier CI introduce en tus aplicaciones de CodeIgniter y aprende a utilizarlo con ejemplos prácticos)

# Middleware

### Contenido

1. [Introducción](#introduction)
2. [Puntos de ejecución de middleware](#middleware-execution-points)
3. [Crear un middleware](#create-a-middleware)
4. [Asignar un middleware](#assign-a-middleware)
   1. [Middleware global](#global-middleware)
   2. [Middleware de ruta](#route-middleware)
5. [Ejecutar un middleware](#run-a-middleware)
   1. [Parámetros de middleware](#middleware-parameters)
   2. [Middleware externo](#external-middleware)

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
    Este es el comportamiento de CodeIgniter y Luthier CI no lo modifica.
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

Todo el middleware debe guardarse en la carpeta `application/middleware`. Un middleware es cualquier clase PHP que implemente la interfaz `Luthier\MiddlewareInterface`, con un método público llamado `run()`.

Ejemplo:

```php
<?php
# application/middleware/TestMiddleware.php

class TestMiddleware implements Luthier\MiddlewareInterface
{
    public function run()
    {
        // Este es el punto de entrada del middleware
    }
}
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Implementar la interfaz <code>MiddlewareInterface</code> será obligatorio</strong>
    <br />
    A partir de la versión 0.3.0 el uso de clases de Middleware que no implementen la interfaz <code>Luthier\MiddlewareInterface</code> está OBSOLETO y dejará de funcionar en la próxima versión
</div>

Para poder asignar un middleware en tu aplicación es necesario que tanto el nombre de la clase como el nombre el archivo sean exactamente iguales. Además, debes tener cuidado de no usar el mismo nombre de algún otro recurso del framework, como un controlador, modelo, librería, etc.

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Añade el sufijo <em>Middleware</em></strong>
    <br />
    Una forma de evitar conflictos es añadiendo el sufijo <em>Middleware</em> al nombre del middleware.
</div>

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Crea un middleware desde la linea de comandos</strong>
    <br />
    Si has activado las herramientas CLI integradas de Luthier CI, usa el comando <code>luthier make middleware [nombre]</code> para crear un nuevo middleware
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

En el contexto de un **grupo de rutas**, el middleware es otra propiedad más, así que va en el segundo argumento del método `group()`:

```php
Route::group('site', ['middleware' => ['AuthMiddleware']], function(){

});
```

Por último, en el contexto de una **ruta individual**, el middleware es también otra propiedad más, así que va en el tercer argumento:

```php
Route::put('foo/bar','controller@method', ['middleware' => ['TestMiddleware']]);
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Sólo en el punto pre_controller</strong>
    <br />
    Cuando asignas un middleware a rutas y grupos de rutas, el punto de ejecución SIEMPRE es <code>pre_controller</code>
</div>

### <a name="run-a-middleware"></a> Ejecutar un middleware

Para ejecutar un middleware desde un controlador usa el método `run()` de la propiedad `middleware`:

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


#### <a name="middleware-parameters"></a> Parámetros de middleware

El método `run()` de la propiedad `middleware` admite un segundo argumento con los parámetros del middleware:

```php
// $args puede ser cualquier tipo de variable:

$args = ['foo' => 'bar'];
$this->middleware->run('AuthMiddleware', $args);
````

#### <a name="external-middleware"></a> Middleware externo

Es posible ejecutar middleware a partir de una clase externa, siempre y cuando posea un método público llamado `run()`

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