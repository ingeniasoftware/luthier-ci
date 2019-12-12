# Depuración

Puedes añadir [PHP Debug Bar](http://phpdebugbar.com) a tu aplicación gracias a la integración de Luthier CI con esta fantástica herramienta.

<div class="alert alert-info">
    Al ser una herramienta principalmente de desarrollo, se desactivará automáticamente en cualquier entorno distinto a <strong>development</strong>
</div>

<!-- %index% -->

### Activación

Por defecto, las capacidades de depuración de Luthier CI están desactivadas. Para activarlas, ve a tu archivo `application/config/hooks.php` y modifica el método `Luthier\Hook::getHooks()` con lo siguiente:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks(
    [
        'modules' => ['debug']
    ]
);
```

### Mensajes de depuración

Para añadir mensajes de depuración usa el método `Debug::log()`:

```php
use Luthier\Debug;

Debug::log($variable, $type);
```

Donde `$variable` es la variable (o expresión) a depurar, y `$type` es el tipo de mensaje, que puede ser `'info'`, `'warning'` o `'error'`:

Ejemplo:

```php
<?php
# application/controllers/TestController.php

use Luthier\Debug;

defined('BASEPATH') OR exit('No direct script access allowed');

class TestController extends CI_Controller
{
    public function index()
    {
        Debug::log('Welcome to Luthier-CI ' . LUTHIER_CI_VERSION . '!');
        Debug::log('Hello world!','info');
        Debug::log('This is a warning, watch out!','warning');
        Debug::log('Oh snap! an error was occurred!','error');
        $this->load->view('welcome_message');
    }
}
```

El método `Debug::log()` acepta un tercer argumento `$dataCollector`, que es el nombre del [recolector de datos](http://phpdebugbar.com/docs/data-collectors.html) donde se guardará el mensaje:

```php
Debug::log('Custom data collector','error','my_custom_data_collector');
```

Si necesitas almacenar un mensaje para ser mostrado en la siguiente solicitud (por ejemplo, luego de enviar un formulario) utilza el método `Debug::logFlash()`:

```php
Debug::logFlash('Hey! this will be available in the next request','info');
```

<div class="alert alert-warning">
    <strong>Requiere que existan datos en el buffer de salida</strong><br />
    Luthier CI agrega el código de PHP Debug Bar en el buffer de salida ANTES de ser procesado y enviado al navegador por <strong>Librería Output de CodeIgniter</strong>. Por lo tanto, es necesario haber utilizado al menos una vez la función <code>$this->load->view()</code> o haber definido explícitamente un buffer de salida sobre el cual trabajar. Las sentencias <code>echo</code> NO producen ningún buffer de salida interno. Además, detener la ejecución del script con las funciones <code>die</code> o <code>exit</code> evitará que se muestre PHP Debug Bar.
</div>

### Añadir recolectores de datos externos

Para añadir un recolector de datos a la instancia de PHP Debug Bar, usa el método estático `Debug::addCollector()`:

```php
use Luthier\Debug;

Debug::addCollector(new MyCollector());
```