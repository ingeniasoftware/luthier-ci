[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] Luthier CI incluye poderosas herramientas de autenticación de usuarios, inspiradas en Symfony, para que te preocupes por lo que realmente importa de tu aplicación)

# Autenticación

### Introducción

CodeIgniter incluye todas las herramientas necesarias para construir un sistema de autenticación de usuario. Desafortunadamente, carece de una interfaz o librería integrada que sea fácil de implementar, mantener y escalar.

Luthier CI aborda el problema usando un modelo de autenticación inspirado en Symfony, con el que se busca la mayor flexibilidad posible para que el desarrollador pueda comenzar a trabajar rápidamente, sin necesidad de reinventar la rueda.

### Activación

Al ser un módulo opcional, la funciones de autenticación de Luthier CI deben activarse primero. Para hacerlo, ve al archivo `application/config/hooks.php` y reemplaza:

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
        'modules' => ['auth']
    ]
);
```

### Herramientas de autenticación disponibles

La autenticación de Luthier CI viene en dos sabores: **SimpleAuth** y el **Framework de Autenticación de Luthier CI**.

#### SimpleAuth: la forma más rápida y divertida

Si lo que necesitas es un sistema de autenticación pre-configurado, personalizable y fácil de usar, **SimpleAuth** es perfecto para ti. Ha sido diseñado para el caso más común de autenticación: el inicio de sesión tradicional a través de un formulario y una base de datos.

Algunas de sus características:

* Pantalla de inicio de sesión y registro de usuario
* Verificación de correo electrónico al registrarse
* Restablecimiento de contraseña
* Roles de usuario
* Funcionalidad "Recuérdame" basada en cookies (opcional)
* Listas de Control de Acceso (ACL) (opcional)
* Funciona con todos los drivers de bases de datos de CodeIgniter
* Protección contra ataques de fuerza bruta durante el inicio de sesión (opcional)
* Definición automática de rutas (Con el método `Route::auth()`)
* Múltiples plantillas disponibles para elegir, traducidas a varios idiomas

#### Framework de Autenticación de Luthier CI: para usuarios avanzados

El **Framework de Autenticación de Luthier CI** es un conjunto de clases e interfaces que definen el proceso de autenticación de usuario de forma abstracta. Con él, podrás realizar las siguientes tareas:

* Carga de **Proveedores de usuario**
* Inicio de sesión mediante usuario y contraseña
* Inicio de sesión forzada (bypass) mediante nombre de usuario
* Validación de estado de autenticación
* Validación de roles de usuario
* Validación de permisos de usuario a través de Listas de Control de Acceso (ACL)
* Manejo de las variables de sesión relacionadas a la autenticación de usuario
* Autenticación basada en controladores

Nota que la Librería constituye la base de la autenticación ¡pero su implementación depende de ti!







