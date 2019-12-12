# Middleware

El Middleware es una serie de _capas_ que deben atravesar las peticiones HTTP entrantes antes de llegar a los controladores de tu aplicación. Este concepto es introducido a CodeIgniter por Luthier CI y abre un abanico de posibilidades en el framework.

<!-- %index% -->

### Puntos de ejecución de Middleware

Hay dos puntos de ejecución posibles para el Middleware en Luthier CI, y están relacionados con la carga y ejecución de los controladores de CodeIgniter:

* `pre_controller`: el Middleware definido en este punto se ejecutará después del constructor del controlador, _PERO_ antes de que cualquier otro método sea ejecutado.
* `post_controller`: el Middleware definido en este punto se ejecutará exactamente en el hook `post_controller` nativo de CodeIgniter.

<div class="alert alert-warning">
    El constructor del controlador <strong>siempre</strong> se ejecuta primero
</div>

Es posible que en algún momento necesites ejecutar código antes del Middleware, y la manera de hacerlo es definiendo un método público en tu controlador llamado `preMiddleware`:

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
    Cuando usas <strong>funciones anónimas</strong> como controladores no existe una forma de ejecutar código arbitrario antes del Middleware
</div>

### Crear un Middleware

Todo el Middleware debe guardarse en la carpeta `application/middleware` de tu aplicación. Un Middleware es cualquier clase PHP que implemente la interfaz `Luthier\MiddlewareInterface`.

La interfaz `Luthier\MiddlewareInterface` sólo define un método llamado `run()`, que es el punto de entrada del Middleware.

Por ejemplo:

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
    A partir de la versión 0.3.0 el uso de clases de Middleware que no implementen la interfaz <code>Luthier\MiddlewareInterface</code> está OBSOLETO y dejará de funcionar en la próxima versión
</div>

Es necesario que tanto el nombre de la clase como el nombre el archivo sean exactamente iguales. 

<div class="alert alert-warning">
    Evita usar un nombre que entre en conflicto con otro recurso existente del framework, como un controlador, modelo, o librería.
</div>

<div class="alert alert-success">
    Una buena práctica es añadir el sufijo <em>Middleware</em> al nombre de tus Middleware.
</div>

### Asignar un Middleware

Puedes asignar un Middleware en diferentes contextos de tu aplicación:
#### Middleware global

Como su nombre lo indica, el Middleware global se ejecuta en todas las peticiones entrantes de tu aplicación.

Por ejemplo:

```php
Route::middleware([name], [exec_point?]);
```

Donde `name` es el nombre del Middleware y `exec_point` es el punto de ejecución, que por defecto es `pre_controller`.

Puedes usar una función anónima en lugar del nombre de un middleware:

```php
Route::middleware(function(){
    ci()->load->view('global_header');
});
```

#### Middleware en grupos de ruta

El Middleware de ruta se define como cualquier otra propiedad. En el caso de los **grupos de rutas**, va en el segundo argumento del método `Route::group()`:

```php
Route::group('site', ['middleware' => ['AuthMiddleware']], function(){

});
```

Para una **ruta individual**, el Middleware también se define como una propiedad:

```php
Route::put('foo/bar','controller@method', ['middleware' => ['TestMiddleware']]);
```

<div class="alert alert-warning">
    Cuando asignas un Middleware a rutas y grupos de rutas, el punto de ejecución SIEMPRE es <code>pre_controller</code>
</div>

### Ejecutar un middleware programáticamente

Para ejecutar un Middleware desde un controlador, usa el método `run()` de la propiedad `middleware`:

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

Éste método admite un segundo argumento con los parámetros del Middleware:

```php
$this->middleware->run('AuthMiddleware', ['foo' => 'bar']);
````

Es posible ejecutar Middleware a partir de una clase externa, siempre y cuando posea un método público llamado `run()`

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