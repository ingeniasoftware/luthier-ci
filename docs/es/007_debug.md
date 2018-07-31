[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] Puedes añadir PHP Debug Bar a tu aplicación gracias a la integración de Luthier CI con esta fantástica herramienta.  )

# Depuración

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Característica experimental</strong>
    <br />
    Hemos hecho un esfuerzo para que todo funcione correctamente, pero es posible que ocurran errores de renderizado y/o carga de los assets requeridos por esta característica. Por favor, <a href="https://github.com/ingeniasoftware/luthier-ci/issues/new">notifícanos</a> si has tenido algún incidente durante su uso.
</div>

### Contenido

1. [Introducción](#introduction)
2. [Activación](#activation)
3. [Mensajes de depuración](#debug-messages)
4. [Añadir tus propios recolectores de datos](#add-your-own-data-collectors)


### <a name="introduction"></a> Introducción

Puedes añadir [PHP Debug Bar](http://phpdebugbar.com) a tu aplicación gracias a la integración de Luthier CI con esta fantástica herramienta.

### <a name="activation"></a> Activación

Para activar esta característica (que está desactivada por defecto) ve a tu archivo `application/config/hooks.php` y reemplaza:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks();
```

Por:

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

Deberás ver la barra de depuración en la parte inferior de la ventana:

<p align="center">
    <img src="https://ingenia.me/uploads/2018/06/19/luthier-ci-debugbar.png" alt="Luthier CI PHP Debug Bar" class="img-responsive" />
</p>

### <a name="debug-messages"></a> Mensajes de depuración

Para añadir mensajes de depuración, usa el método estático `log()` de la clase `Luthier\Debug`:

```php
# use Luthier\Debug;
Debug::log($variable, $type, $dataCollector);
```

Donde `$variable` es la variable a depurar, y `$type` es el tipo de mensaje, que puede ser `'info'`, `'warning'` o `'error'`.

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

Y el resultado:

<p align="center">
    <img src="https://ingenia.me/uploads/2018/06/19/luthier-ci-debugbar-log.png" alt="Luthier CI PHP Debug Bar" class="img-responsive" />
</p>

Un argumento `$dataCollector` opcional es el nombre del [recolector de datos](http://phpdebugbar.com/docs/data-collectors.html) donde se guardará el mensaje:

```php
Debug::log('Custom data collector','error','my_custom_data_collector');
```

Si necesitas almacenar un mensaje para ser mostrado en la siguiente solicitud (por ejemplo, luego de enviar un formulario) utilza el método `logFlash()`, cuya sintaxis es idéntica al método estático `log()`:

```php
Debug::logFlash('Hey! this will be available in the next request','info');
```

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Desactivado en entornos de producción</strong>
    <br />
    Si configuras el entorno de tu aplicación a <code>production</code> ésta característica se desactivará automáticamente, y cualquier código de depuración será ignorado
</div>

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Requiere que existan datos en el buffer de salida</strong>
    <br />
    Luthier CI agrega el código de PHP Debug Bar en el buffer de salida ANTES de ser procesado y enviado al navegador por librería <code>output</code> de CodeIgniter. Por lo tanto, es necesario haber utilizado al menos una vez la función <code>$this->load->view()</code> o haber definido explícitamente un buffer de salida sobre el cual trabajar. Las sentencias <code>echo</code> NO producen ningún buffer de salida interno. Además, detener la ejecución del script con las funciones <code>die</code> o <code>exit</code> evitará que se muestre PHP Debug Bar.
</div>

### <a name="add-your-own-data-collectors"></a> Añadir tus propios recolectores de datos

Es posible añadir tus propios recolectores de datos y almacenar mensajes en ellos. Para añadir un recolector de datos a la instancia de PHP Debug Bar, usa el método estático `addCollector()`:

```php
# use Luthier\Debug;
Debug::addCollector(new MyCollector());
```