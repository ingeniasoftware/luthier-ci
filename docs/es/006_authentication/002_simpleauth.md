[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] ...)

# SimpleAuth

### Introducción

Con SimpleAuth puedes implementar un sistema de login en tu aplicación sin mucho esfuerzo en pocos minutos. SimpleAuth se compone de una librería (`Simple_auth`) un controlador (`SimpleAuthController`) un middleware (`SimpleAuthMiddleware`) un Proveedor de usuario (`UserProvider`) con su clase de usuario adjunta (`User`) y las migraciones para la base de datos, todo ello construído sobre la Librería de Autenticación Estándar de Luthier-CI, poniendo especial cuidado en ofrecer una experiencia libre de complicaciones.

### Instalacción

#### Requisitos previos

La funcionalidades de autenticación de Luthier-CI son un módulo opcional y deben activarse primero. Para hacerlo, ve al archivo `application/config/hooks.php` y reemplaza:

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
        'modules' => ['Auth']
    ]
);
```

Como la instalación se realiza a través del comando `make` de las [Herramientas CLI Incorporadas de Luthier-CI](../cli#built-in-cli-tools), debes verificar que estén activadas:

```php
<?php
# application/routes/cli.php

Luthier\Cli::maker();      // Comando 'luthier make'
Luthier\Cli::migrations(); // Comando 'luthier migrate'
```

Por último, es necesario que configures correctamente la conexión a la base de datos utilizada por tu aplicación en el archivo `application/config/database.php` y, por último, que las migraciones estén activadas (en `application/config/migration.php`)

#### Paso 1: Copiar los archivos necesarios

Ejecuta en la carpeta raíz de tu aplicación:

```
php index.php luthier make auth
```

Si todo sale bien, deberás tener los siguientes nuevos archivos:

```
application
    |- config
    |   |- auth.php
    |
    |- controllers
    |   |- SimpleAuthController.php
    |
    |- libraries
    |   |- Simple_Auth.php
    |
    |- middleware
    |   |- SimpleAuthMiddleware.php
    |
    |- migrations
    |   |- 20180516000000_create_users_table.php
    |   |- 20180516000001_create_password_resets_table.php
    |   |- 20180516000002_create_email_verifications_table.php
    |   |- 20180516000003_create_login_attempts_table.php
    |   |- 20180516000004_create_user_permissions_categories_table.php
    |   |- 20180516000005_create_user_permissions_table.php
    |
    |- security
    |   |- providers
    |       |- User.php
    |       |- UserProvider.php
```

#### Paso 2: Instalar la base de datos

Ejecuta en la carpeta raíz de tu aplicación:

```
php index.php luthier migrate
```

Deberás poder ver la siguiente salida:

```
MIGRATED: 20180516000000_create_users_table.php
MIGRATED: 20180516000001_create_password_resets_table.php
MIGRATED: 20180516000002_create_email_verifications_table.php
MIGRATED: 20180516000003_create_login_attempts_table.php
MIGRATED: 20180516000004_create_user_permissions_categories_table.php
MIGRATED: 20180516000005_create_user_permissions_table.php
```

#### Paso 3: Configurar las rutas

En tu archivo `web.php`, añade la siguiente línea:

```php
<?php
# application/routes/web.php

Route::auth();
```

#### Paso 4: Probar la instalación

Una vez realizados todos los pasos, accede a la ruta de inicio de sesión predeterminada: `http://example.com/login`. Deberías poder ver tu nueva pantalla de inicio de seión:

<img src="https://ingenia.me/uploads/2018/06/11/simpleauth_login_screenshot.png" alt="SimpleAuth login screen" class="img-responsive center" />

### Configuración

Toda la configuración de SimpleAuth recae sobre el archivo `application/config/auth.php`. A continuación, una breve explicación de cada elemento:

#### Configuración general

* **auth_login_route**: *[string]* Ruta de inicio de sesión. Si utilizas el método `Route::auth()` para definir las rutas de SimpleAuth, éste valor será omitido.
* **auth_logout_route**: *[string]* Ruta de cierre de sesión. Si utilizas el método `Route::auth()` para definir las rutas de SimpleAuth, éste valor será omitido.
* **auth_login_route_redirect**: *[string]* Ruta de redirección en caso de inicio de sesión exitoso.
* **auth_logout_route_redirect**: *[string]* Ruta de redirección inmediatamente después del cierre de sesión.
* **auth_route_auto_redirect**: *[array]* Rutas que activarán una redirección automática a la ruta `auth_login_route_redirect` en caso de que el usuario ya esté autenticado.
* **auth_form_username_field**: *[string]* Nombre del campo del formulario de inicio de sesión correspondiente al nombre de usuario/email a autenticar.
* **auth_form_username_field**: *[string]* Nombre del campo del formulario de inicio de sesión correspondiente a la contraseña de usuario a autenticar.
* **auth_session_var**: *[string]* Nombre de la variable de sesión utilizada por el módulo de Autenticación de Luthier-CI.

#### Activación/desactivación de características

* **simpleauth_enable_signup**: *[bool]* Activa el formulario de registro de usuario.
* **simpleauth_enable_password_reset**: *[bool]* Activa el formulario de restablecimiento de contraseña.
* **simpleauth_enable_remember_me**: *[bool]* Activa la función "Recuérdame" basada en cookie.
* **simpleauth_enable_email_verification**: *[bool]* Activa la verificación de correo electrónico durante el proceso de registro de usuario. Para que funcione es necesario que el envío de correos electrónicos del framework esté correctamente configurado.
* **simpleauth_enforce_email_verification**: *[bool]* Deniega el acceso a los usuarios que no posean su dirección de correo electrónico verificado.
* **simpleauth_enable_brute_force_protection**: *[bool]* Activa la defensa de ataques de inicio de sesión por fuerza bruta.
* **simpleauth_enable_acl**: *[bool]* Activa las Listas de Control de Acceso (ACL)

#### Vistas

* **simpleauth_skin**: *[string]* Skin utilizado en las vistas incluídas por SimpleAuth. Por defecto es `default`.
* **simpleauth_assets_dir**: *[string]* URL pública relativa a la aplicación donde se guardarán los recursos (css, js, etc) de las vistas de SimpleAuth.

#### Listas de Control de Acceso (ACL)

* **simpleauth_acl_map**: *[array]* Arreglo asociativo con los nombres e IDs de categorías y grupos de categorías de permisos usados por las Listas de Control de Acceso. Configurar esto reduce drásticamente la cantidad de consultas la base de datos y aumenta el rendimiento en general de la aplicación.

#### Emails

* **simpleauth_email_configuration**: *[array|null]* Arreglo con la configuración personalizada que será suministrada durante la inicialización de la librería de email para el envío de correos. Dejar en `null` para usar la misma de la aplicación.
* **simpleauth_email_address**: *[string]* Dirección de correo electrónico que aparecerá en el campo `from` de los emails enviados.
* **simpleauth_email_name**: *[string]* Nombre que aparecerá el campo `from` junto a la dirección correo electrónico de los emails enviados.
* **simpleauth_email_verification_message**: *[string|null]* Mensaje automático con las instrucciones para la verificación de correo electrónico enviado al usuario luego de registrarse exitosamente. Dejar en `null` para usar el mensaje por defecto de SimpleAuth traducido al idioma actual de la aplicación. Nota: para que los mensajes que contengan HTML puedan ser visualizados correctamente, se debe configurar primero la librería de emails.
* **simpleauth_password_reset_message**: *[string|null]* Mensaje automático con las instrucciones para el restablecimiento de contraseña. Dejar en `null` para usar el mensaje por defecto de SimpleAuth traducido al idioma actual de la aplicación. Nota: para que los mensajes que contengan HTML puedan ser visualizados correctamente, se debe configurar primero la librería de emails.

#### Funcionalidad "Recuérdame"

* **simpleauth_remember_me_field**: *[string]* Nombre del campo del formulario de inicio de sesión correspondiente a la funcionalidad "Recuérdame".
* **simpleauth_remember_me_cookie**: *[string]* Nombre de la cookie utilizada por funcionalidad "Recuérdame".

#### Base de datos

* **simpleauth_user_provider**: *[string]* Proveedor de usuario utilizado por SimepleAuth. No modificar si no sabes lo que estás haciendo.
* **simpleauth_users_table**: *[string]* Nombre de la tabla donde se almacenan los usuarios.
* **simpleauth_users_email_verification_table**: *[string]* Nombre de la tabla donde se almacenan los tokens de verificación de correo electrónico.
* **simpleauth_password_resets_table**: *[string]* Nombre de la tabla donde se almacenan los tokens de restablecimiento de contraseña.
* **simpleauth_login_attempts_table**: *[string]* Nombre de la tabla donde se almacenan los intentos de inicio de sesión fallidos, utilizados para la defensa contra ataques de inicio de sesión por fuerza bruta.
* **simpleauth_users_acl_table**: *[string]* Nombre de la tabla donde se almacenan los permisos de usuarios concendidos, utilizados por las Listas de Control de Acceso (ACL).
* **simpleauth_users_acl_categories_table**: *[string]* Nombre de la tabla donde se almacenan el árbol de permisos utilizados por las Listas de Control de Acceso (ACL).
* **simpleauth_id_col**: *[string]* Nombre de la columna de identificación de la tabla de usuarios.
* **simpleauth_username_col**: *[string]* Nombre de la columna correspondiente al nombre de usuario de la tabla de usuarios. Ésta columna es la que se usará durante el proceso de autenticación de usuario.
* **simpleauth_email_col**: *[string]* Nombre de la columna correspondiente al email de la tabla de usuarios. Ésta columna es la que se usará para los envíos de email de la librería.
* **simpleauth_email_first_name_col**: *[string]* Nombre de la columna correspondiente al primer nombre (o nombre) de la tabla de usuarios. Ésta columna es la que se usará para los envíos de email de la librería.
* **simpleauth_password_col**: *[string]* Nombre de la columna correspondiente la contraseña en la tabla de usuarios. Ésta columna es la que se se usará durante el proceso de autenticación de usuario.
* **simpleauth_role_col**: *[string]* Nombre de la columna correspondiente al rol en la tabla de usuarios. Ésta columna será utilizada para la comprobación de roles de usuario de la librería.
* **simpleauth_active_col**: *[string]* Nombre de la columna correspondiente al estatus del usuario. En la base de datos, debe definirse como una columna del tipo INT, donde el valor `0` corresponde a un usuario **desactivado** y `1` a un usuario **activado**. Se usa durante el inicio de sesión.
* **simpleauth_verified_col**: *[string]* Nombre de la columna correspondiente al estatus de la verificación del email del usuario. En la base de datos, debe definirse como una columna del tipo INT, donde el valor `0` corresponde a un usuario **desactivado** y `1` a un usuario **activado**. Se usa durante el inicio de sesión.
* **simpleauth_remember_me_col**: *[string]* Nombre de la columna donde se almacena el token utilizado por la funcionalidad "Recuérdame", en caso de estar activada.

### Librería de SimpleAuth (Simple_auth)

