# Autenticación

CodeIgniter viene con todo lo necesario para construir tu propio  sistema de autenticación de usuarios, sin embargo, no incluye una librería dedicada exclusivamente a ésta función.

Luthier CI aborda el tema de la autenticación con un modelo inspirado en Symfony, buscando la flexibilidad que el desarrollador necesita para comenzar a trabajar rápidamente, sin reinventar la rueda.

### Activar el sistema de autenticación

Por defecto, las capacidades de autenticación de Luthier CI están desactivadas. Para activarlas ve al archivo `application/config/hooks.php` y modifica el método `Luthier\Hook::getHooks()` con lo siguiente:

```php
<?php
# application/config/hooks.php

defined('BASEPATH') OR exit('No direct script access allowed');

// (...)

$hook = Luthier\Hook::getHooks(
    [
        'modules' => ['auth']
    ]
);
```





