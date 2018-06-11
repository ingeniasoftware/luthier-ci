[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] ...)

# Autenticación

### Introducción

CodeIgniter incluye todas las herramientas necesarias para construir un sistema de autenticación de usuario. Desafortunadamente, carece de una interfaz o librería integrada que sea fácil de implementar y, por sobre todo, fácil de escalar y mantener.

Luthier-CI aborda el problema usando un modelo de autenticación inspirado en Symfony, con el que se busca la mayor flexibilidad posible para que el desarrollador pueda comenzar a trabajar rápidamente, sin necesidad de reinventar la rueda.

### Herramientas de Autenticación disponibles

La Autenticación de Luthier-CI viene en dos sabores, **SimpleAuth** y la **Librería de Autenticación Estándar de Luthier-CI**.

#### SimpleAuth

Si lo que necesitas es sistema de autenticación personalizable y fácil de usar, **SimpleAuth** es perfecto para ti. Ha sido diseñado para el caso más común de autenticación: el inicio de sesión tradicional a través de un formulario y una base de datos.

Algunas de sus características:

* Pantalla de inicio de sesión y registro de usuario
* Verificación de correo electrónico al registrarse
* Restablecimiento de contraseña
* Funcionalidad "Recuérdame" basada en cookies (opcional)
* Roles de usuario simples
* Listas de Control de Acceso (ACL) (opcional)
* Proveedor de usuario incluído y configurable
* Protección contra ataques de fuerza bruta durante el inicio de sesión (opcional)
* Definición automática de rutas (Con el método `Route::auth()`)
* Múltiples vistas y skins disponibles para elegir, traducidos a varios idiomas

La SimpleAuth incluye una librería que no es diferente a cualquier otra librería de CodeIgniter: una vez instalada, basta con cargarla en el framework usando el método `$this->load->library('simple_auth')` y comenzar a utilizarla.

#### Librería de Autenticación Estándar de Luthier-CI

La **Librería de Autenticación Estándar de Luthier-CI** comprende una serie de clases, middleware e interfaces que definen el proceso de autenticación de usuario en el framework de la forma más abstracta y generalizada posible. Con ella podrás realizar las siguientes tareas:

* Carga de **Proveedores de usuario**
* Inicio de sesión mediante usuario y contraseña
* Inicio de sesión forzada (bypass) mediante nombre de usuario
* Validación de estado de autenticación
* Validación de roles de usuario
* Validación de permisos de usuario a través de Listas de Control de Acceso (ACL)
* Manejo de las variables de sesión relacionadas a la autenticación de usuario
* Autenticación basada en controladores

Una pieza clave de Librería de Autenticación Estándar de Luthier-CI son los **Proveedores de usuario**. Un Proveedor de usuario es una clase que se se encarga de obtener de algún lugar al usuario que se pretende autenticar.

Nota que la Librería constituye la base de la autenticación, pero su implementación depende de ti: debes crear tu propio Proveedor de usuario, controladores y, posiblemente, tus propias librerías y middleware. A cambio, ganas un control absoluto del proceso de autenticación y la posibilidad de autentificar usuarios desde prácticamente cualquier parte.

Los Proveedores de usuario no están limitados a una base de datos: puedes obtener los usuarios desde una API, o incluso desde un fichero estático.

Para usar la Librería de Autenticación Estándar de Luthier-CI se deben llamar a los métodos estáticos de su clase principal, la clase `Auth` (`Luthier\Auth`) que se incluye automáticamente en el framework durante el arranque de Luthier-CI.







