[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] Aprende cómo obtener Luthier CI e instalarlo en tu aplicación de CodeIgniter con instrucciones paso a paso ¡no toma más de 5 minutos!)

# Instalación

### Contenido

1. [Requisitos](#requirements)
2. [Instalación](#installation)
   1. [Obtener Luthier CI](#get-luthier-ci)
   2. [Habilitar el autoload de Composer y los hooks](#enable-composer-autoload-and-hooks)
   3. [Conectar Luthier CI con tu aplicación](#connect-luthier-ci-with-your-application)
3. [Inicialización](#initialization)

### <a name="requirements"></a> Requisitos

* PHP >= 5.6 (Compatible con PHP 7)
* CodeIgniter >= 3.0

### <a name="installation"></a> Instalación

#### <a name="get-luthier-ci"></a> Obtener Luthier CI

<div class="alert alert-info">
    <i class="fa fa-info-circle" aria-hidden="true"></i>
    <strong>Composer requerido</strong>
    <br />
    Luthier CI se instala a través de Composer. Puedes obtenerlo <a href="https://getcomposer.org/download/">aquí</a>.
</div>

Dirígete a la carpeta `application` y ejecuta el siguiente comando:

```bash
composer require luthier/luthier
```

#### <a name="enable-composer-autoload-and-hooks"></a> Habilitar el _autoload_ de Composer y los _hooks_

Para que Luthier CI funcione es necesario que tanto el **autoload** de Composer y como los **hooks** estén habilitados. En el archivo `config.php` modifica lo siguiente:

```php
<?php
# application/config/config.php

// (...)

$config['enable_hooks']      = TRUE;
$config['composer_autoload'] = TRUE;

// (...)
```

#### <a name="connect-luthier-ci-with-your-application"></a> Conectar Luthier CI con tu aplicación

En el archivo `hooks.php`, asigna los hooks de Luthier CI a la variable `$hook`:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks();
```

En el archivo `routes.php`, asigna las rutas de Luthier CI a la variable `$route`:

```php
<?php
# application/config/routes.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$route = Luthier\Route::getRoutes();
```

### <a name="initialization"></a> Inicialización

La primera vez que Luthier CI se ejecuta algunos archivos y carpetas son creados automáticamente:

* `routes/web.php`: Archivo de rutas HTTP
* `routes/api.php`: Archivo de rutas AJAX
* `routes/cli.php`: Archivo de rutas CLI
* `controllers/Luthier.php`: Controlador falso, necesario para usar algunas rutas
* `middleware`: Carpeta para guardar los archivos de middleware

Durante la inicialización del framework los _hooks_ son llamados: `Luthier\Hook::getHooks()` devuelve un arreglo con los hooks usados por Luthier CI, incluído el necesario para su arranque. En este punto, Luthier CI analiza y compila todas las rutas definidas en los tres primeros archivos mencionados anteriormente. Entonces, cuando el framework carga las rutas en el archivo `application/config/routes.php`, `Luthier\Route::getRoutes()` devuelve un arreglo con las rutas en el formato que CodeIgniter entiende. Todo lo siguiente es la ejecución normal del framework.

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Permisos de escritura</strong>
    <br />
    Si obtienes errores durante la creación de los archivos base de Luthier CI, es posible que se deba a permisos insuficientes. Asegúrate de que la carpeta <code>application</code> tenga permisos de escritura
</div>