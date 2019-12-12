# Instalación

Instalar Luthier CI es muy sencillo y en la mayoría de los casos no toma más de 5 minutos. Asegúrate de cumplir con los requisitos descritos más abajo y de seguir los pasos de instalación.

<!-- %index% -->

### Requisitos

* PHP >= 5.6 (Compatible con PHP 7)
* CodeIgniter >= 3.0

### Pasos de Instalación de Luthier CI

#### Paso 1: Obtener Luthier CI

<div class="alert alert-info">
    <strong>Composer requerido</strong><br />
    Luthier CI se instala usando Composer. Puedes obtenerlo <a href="https://getcomposer.org/download/">aquí</a>.
</div>

Dirígete a la carpeta `application` de CodeIgniter y ejecuta el siguiente comando:

```bash
composer require luthier/luthier
```

#### Paso 2: Habilitar el autoload de Composer y los hooks

Es necesario que tanto el **autoload** de Composer y como los **hooks** estén habilitados en tu aplicación. En el archivo `config.php` modifica lo siguiente:

```php
<?php
# application/config/config.php

// (...)

$config['enable_hooks'] = TRUE;
$config['composer_autoload'] = TRUE;

// (...)
```

#### Paso 3: Conectar Luthier CI con tu aplicación

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

### Inicialización

La primera vez que Luthier CI se ejecuta en tu aplicación algunos archivos y carpetas son creados automáticamente:

* `routes/web.php`: Archivo de rutas HTTP
* `routes/api.php`: Archivo de rutas AJAX
* `routes/cli.php`: Archivo de rutas CLI
* `controllers/Luthier.php`: Controlador falso, necesario para usar algunas rutas
* `middleware`: Carpeta para guardar los archivos de middleware

`Luthier\Hook::getHooks()` devuelve un arreglo con los hooks usados por Luthier CI, incluído el necesario para su arranque.  `Luthier\Route::getRoutes()` devuelve un arreglo con las rutas en el formato que CodeIgniter entiende. Todo lo siguiente es la ejecución normal del framework.

<div class="alert alert-warning">
    <strong>Permisos de escritura</strong>
    <br />
    Si ocurren errores durante la creación de los archivos mencionados arriba es posible que se deba a permisos insuficientes. Asegúrate de que la carpeta <code>application</code> tenga permisos de escritura
</div>