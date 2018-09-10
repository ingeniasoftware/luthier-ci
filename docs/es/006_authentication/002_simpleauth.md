[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] ¡Con SimpleAuth puedes añadir un inicio de sesión y registro de usuarios a tu aplicación en menos de 5 minutos!)

# SimpleAuth

### Contenido

1. [Introducción](#introduction)
2. [Instalación](#installation)
   1. [Paso 1: Copiar los archivos necesarios](#step-1-copy-required-files)
   2. [Paso 2: Instalar la base de datos](#step-2-install-the-database)
   3. [Paso 3: Definir las rutas](#step-3-define-the-routes)
3. [Controlador de SimpleAuth](#simpleauth-controller)
   1. [Personalizar el formulario de registro de usuario](#signup-form-personalization)
4. [Middleware de SimpleAuth](#simpleauth-middleware)
5. [Librería de SimpleAuth](#simpleauth-library)
   1. [Funciones básicas](#simpleauth-library-basic-functions)
      1. [Obtener el usuario actual](#obtaining-the-current-user)
      2. [Verificar si un usuario es invitado (anónimo)](#verifying-if-user-is-guest)
      3. [Verificar el rol de un usuario](#verifying-the-user-role)
      4. [Verificar los permisos de un usuario](#verifying-the-user-permissions)
   2. [Funciones de Listas de Control de Acceso (ACL)](#simpleauth-library-acl-functions)
   3. [Otras funciones](#simpleauth-library-other-functions)
6. [Vistas y traducciones](#views-and-translations)
   1. [Estableciendo el skin de SimpleAuth](#establishing-simpleauth-skin)
   2. [Estableciendo el idioma de SimpleAuth](#establishing-simpleauth-language)
   3. [Utilizando tus propias vistas](#using-your-own-views)
7. [Configuración de SimpleAuth](#simpleauth-configuration)
   1. [Configuración general](#general-configuration)
   2. [Activación/desactivación de características](#enabling-disabling-features)
   3. [Configuración de vistas](#views-configuration)
   4. [Configuración de Listas de Control de Acceso (ACL)](#access-control-list-configuration)
   5. [Configuración de emails](#email-configuration)
   6. [Configuración de funcionalidad "Recuérdame"](#remember-me-functionality-configuration)
   7. [Configuración de base de datos](#database-configuration)

### <a name="introduction"></a> Introducción

¡Con **SimpleAuth** puedes añadir un inicio de sesión y registro de usuarios a tu aplicación en menos de 5 minutos! SimpleAuth se compone de un controlador (`SimpleAuthController`) un middleware (`SimpleAuthMiddleware`) una librería (`Simple_auth`) y otros elementos construídos a partir del **Framework de Autenticación de Luthier CI**.

### <a name="installation"></a> Instalación

Como la instalación se realiza a través del comando `make` de las [Herramientas CLI Incorporadas de Luthier CI](../cli#built-in-cli-tools), asegúrate de definir dichos comandos en tu archivo de rutas `cli.php`:

```php
<?php
# application/routes/cli.php

Luthier\Cli::maker();      // Comando 'luthier make'
Luthier\Cli::migrations(); // Comando 'luthier migrate'
```

Además, es necesario que configures correctamente la conexión a la base de datos (en `application/config/database.php`) y las migraciones (en `application/config/migration.php`) antes de comenzar.

#### <a name="step-1-copy-required-files"></a> Paso 1: Copiar los archivos necesarios

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

#### <a name="step-2-install-the-database"></a> Paso 2: Instalar la base de datos

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

#### <a name="step-3-define-the-routes"></a> Paso 3: Definir las rutas

En tu archivo `web.php`, añade la siguiente línea:

```php
Route::auth();
```

Que es un atajo para definir todas estas rutas:

```php
Route::match(['get', 'post'], 'login', 'SimpleAuthController@login')->name('login');
Route::post('logout', 'SimpleAuthController@logout')->name('logout');
Route::get('email_verification/{token}', 'SimpleAuthController@emailVerification')->name('email_verification');
Route::match(['get', 'post'], 'signup', 'SimpleAuthController@signup')->name('signup');
Route::match(['get', 'post'], 'confirm_password', 'SimpleAuthController@confirmPassword')->name('confirm_password');
Route::group('password-reset', function(){
    Route::match(['get','post'], '/', 'SimpleAuthController@passwordReset')->name('password_reset');
    Route::match(['get','post'], '{token}', 'SimpleAuthController@passwordResetForm')->name('password_reset_form');
});
```

Si has seguido todos los pasos correctamente, al visitar la url `/login` deberás ver tu nueva pantalla de inicio de sesión:

<img src="https://ingenia.me/uploads/2018/06/11/simpleauth_login_screenshot.png" alt="SimpleAuth login screen" class="img-responsive center" />

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Información acerca de la ruta de cierre de sesión</strong>
    <br />
    Por defecto, la ruta <code>logout</code> sólo acepta peticiones POST, así que un link a la url <code>/logout</code> no va a funcionar para cerrar la sesión, a menos se use un formulario HTML que apunte a esa ruta. Para permitir peticiones GET, usa <code>Route::auth(FALSE)</code>
</div>

### <a name="simpleauth-controller"></a> Controlador de SimpleAuth

El controlador de SimpleAuth (`SimpleAuthController`) contiene las acciones de autenticación tales como el inicio de sesión, el registro de usuario, el restablecimiento de contraseña, entre otros. Se ve similar a esto:

```php
<?php
# application/controllers/SimpleAuthController.php

defined('BASEPATH') OR exit('No direct script access allowed');

/* (...) */

class SimpleAuthController extends Luthier\Auth\SimpleAuth\Controller
{

    /**
     * Sign up form fields
     *
     * (...)
     */
    public function getSignupFields()
    {
        return [ /* (...) */ ];
    }

    /**
     * Fillable database user fields
     *
     * (...)

     * @access public
     */
    public function getUserFields()
    {
        return [ /* (...) */ ];
    }
}
```

A menos que desees personalizar SimpleAuth, no hace falta agregar nada más a éste controlador, pues la clase a la que extiende (`Luthier\Auth\SimpleAuth\Controller`) ya define la lógica de autenticación y, en tu archivo de rutas, `Route::auth()` ya define todas las rutas que deben apuntar hacia aquí.

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Sobreescribir métodos elimina cualquier funcionalidad base</strong>
    <br />
    Puede parecer obvio, pero si sobreescribes cualquier método del controlador de SimpleAuth perderás el sistema de skins (temas) vistas traducidas, el constructor de formulario de registro de usuario, y otras funciones útiles que vienen pre-configuradas, descritas más abajo
</div>

#### <a name="signup-form-personalization"></a> Personalizar el formulario de registro de usuario

Puedes cambiar los campos del formulario de registro a tu gusto. Para ello, el método `getSignupFields()` de tu controlador de SimpleAuth debe retornar un arreglo que defina su estructura, con la siguiente sintaxis:

```php
public function getSignupFields()
{
    return [
        'Field name 1' => [
            'Field type',
            'Field label',
            [ /* HTML5 attributes array */ ],
            [ /* CI Validation rules array */] ,
            [ /* CI Validation error essages array (Optional)*/]
        ],
        'Field name 2' => [
            'Field type',
            'Field label',
            [ /* ... */ ],
            [ /* ... */ ] ,
        ],

        // ( ... )

        'Field name N' => [
            'Field type',
            'Field label',
            [ /* ... */ ],
            [ /* ... */ ] ,
        ]
    ];
}
```

Por otra parte, el método `getUserFields()` de tu controlador de SimpleAuth debe retornar un arreglo con los campos de dicho formulario que se almacenarán en el nuevo usuario, donde cada elemento del arreglo coincide tanto con el campo de dicho formulario de registro como con el nombre de la columna de la tabla de usuarios en tu base de datos:

```php
public function getUserFields()
{
    return [
        'first_name',
        'last_name',
        'username',
        'gender',
        'email',
        'password',
        'role',
    ];
}
```

Los usuarios de Laravel notarán que este es exactamente el mismo comportamiento de la propiedad `$fillable` de los modelos de EloquentORM, pero aplicado al formulario de registro de usuarios de SimpleAuth.

### <a name="simpleauth-middleware"></a> Middleware de SimpleAuth

El middleware de SimpleAuth (`SimpleAuthMiddleware`) es la primera línea de defensa para las rutas que requieran la autenticación previa del usuario. Este middleware se encarga automáticamente de verificar el estado actual del usuario:

* Si el usuario está **autenticado**, la solicitud sigue con normalidad
* Si el usuario NO está **autenticado**, se intentará restaurar la sesión utilizando la funcionalidad "Recuérdame" (en caso de estár activada)
* Si no es posible restaurar ninguna sesión previa, se redireccionará al usuario a la pantalla de inicio de sesión

Puedes usar el middleware de SimpleAuth en tantas rutas y grupos de rutas como quieras, e incluso combinarlo con tus propios middleware para añadir capas de seguridad adicionales.

Ejemplo:

```php
<?php
# application/routes/web.php

// Rutas predeterminadas de SimpleAuth:

Route::auth();

// Rutas públicas:

Route::get('/', 'FrontendController@homepage')->name('homepage');
Route::get('/about', 'FrontendController@about')->name('about');
Route::match(['get','post'], '/contact', 'FrontendController@contact')->name('contact');

// Rutas protegidas: acceder aquí sin estar autenticado va a redirigirte a la pantalla de
//                   inicio de sesión

Route::group('dashboard', ['middleware' => ['SimpleAuthMiddleware']], function(){
    Route::get('/', 'UserArea@dashboard');
});
```

### <a name="simpleauth-library"></a> Librería de SimpleAuth

La librería de SimpleAuth es un *wrapper* de la clase `Auth` del Framework de Autenticación de Luthier CI, en el formato de una librería nativa de CodeIgniter, por lo que todos sus métodos están disponibles para ti con una sintaxis que ya conoces.

Para comenzar a usar la Librería de SimpleAuth, debes cargarla en el framework:

```php
$this->load->library('Simple_auth');
```

#### <a name="simpleauth-library-basic-functions"></a> Funciones básicas

*NOTA: No todos los métodos de la clase `Luthier\Auth` son relevantes cuando estás usando SimpleAuth, por lo que únicamente enumeramos los que pueden resultarte útiles*


##### <a name="obtaining-the-current-user"></a> Obtener el usuario actual

Para obtener el usuario que se encuentra autenticado en tu aplicación, usa el método `user()`, el cual retorna un *objeto de usuario*, o `NULL` en caso de no existir ningún usuario autenticado:

```php
// El objeto de usuario actual:
$userObject = $this->simple_auth->user();

// Con el objeto de usuario se tiene acceso a:
// ...la entidad de usuario de la base de datos:
$user = $userObject->getEntity();

// ...sus roles:
$roles = $userObject->getRoles();

// ...y sus permisos:
$permissions = $userObject->getPermissions();
```

Si estás usando el Proveedor de usuario predeterminado de SimpleAuth, puedes acceder directamente a los datos del usuario actual sin tener que usar el método `getEntity()`. Las siguientes expresiones son equivalentes:

```php
$this->simple_auth->user()->getEntity()->first_name;

$this->simple_auth->user()->first_name;
```

##### <a name="verifying-if-user-is-guest"></a> Verificar si un usuario es invitado (anónimo)

Para verificar rápidamente si un usuario es invitado, usa el método `isGuest()`, el cual retorna `TRUE` si el usuario NO ha iniciado sesión aún, y `FALSE` en caso contrario:

```php
$this->simple_auth->isGuest();
```

##### <a name="verifying-the-user-role"></a> Verificar el rol de un usuario

Para verificar si un usuario posee un rol en específico, usa el método `isRole($role)`, el cual retorna `TRUE` si el usuario posee el rol `$role`, o `FALSE` si no lo posee o no hay ningún usuario autenticado:

```php
$this->simple_auth->isRole('ADMIN');
```

##### <a name="verifying-the-user-permissions"></a> Verificar los permisos de un usuario

Para verificar si un usuario posee un permiso en específico, usa el método `isGranted($permission)`, el cual retorna `TRUE` si el usuario posee el permiso `permission`, o `FALSE` si no lo posee o no hay ningún usuario autenticado.

Ejemplo:

```php
$this->simple_auth->isGranted('general.read');
```

Una sintaxis alternativa está disponible, para verificar si un usuario pertenece a un rol que comience por una frase/categoría en específico:

```php
// Lo siguiente dará TRUE para los permisos que comiencen por 'general.'
$this->simple_auth->isGranted('general.*');
```

#### <a name="simpleauth-library-acl-functions"></a> Funciones de Listas de Control de Acceso (ACL)

Las Listas de Control de Acceso (ACL) son una funcionalidad opcional de autenticación utilizada para establecer permisos específicos a cada usuario autenticado. Un usuario puede, por lo tanto, tener un rol y varios permisos asignados que le garanticen (o nieguen) el acceso a determinados recursos de la aplicación.

En SimpleAuth no existen *grupos de usuarios* ni nada parecido, los permisos de usuario se almacenan en un arbol de permisos de profundidad variable (el límite de sub-permisos depende de ti).

Considera los siguientes permisos:

```
ID      NAME        PARENT_ID
-----------------------------
1       general     [null]
2       read        1
3       write       1
4       delete      1
5       local       4
6       global      4
```

Y ésta asignación de permisos:

```
ID      USERNAME    PERMISSION_ID
---------------------------------
1       anderson    2
2       anderson    5
3       julio       3
4       julio       6
```

Cuando, por ejemplo, el usuario `anderson` inicie sesión, tendrá los siguientes permisos:

```
general.read
general.delete.local
```

Y cuando el usuario `julio` inicie sesión, tendrá los siguientes permisos:

```
general.write
general.delete.global
```

El árbol de permisos se almacena en la tabla `user_permissions_categories`, mientras que las asignaciones de permisos se almacenan en la tabla `user_permissions`, ambas creadas mediante las migraciones que se incluyen con SimpleAuth. No existe un método automatizado para crear o eliminar permisos, por lo que debes hacerlo manualmente.

---

Estas son las funciones de ACL disponibles en la librería de SimpleAuth:

##### <a name="simpleauth-library-permissionsexists-method"></a> permissionsExists(*string* **$permission**) *: [bool]*

Verifica que el permiso `$permission` exista en la tabla de la Lista de Control de Acceso (ACL).

Ejemplo:

```php
$this->simple_auth->permissionExists('general.read');
```

##### <a name="simpleauth-library-grantpermission-method"></a> grantPermission(*string* **$permission**, *string* **$username** = *NULL*) *: [bool]*

Asigna el permiso `$permission` al usuario `$username`, retornando `TRUE` si la operación fué exitosa o `FALSE` en caso contrario.

```php
// Asignando el permiso 'general.read' al usuario actual
$this->simple_auth->grantPermission('general.read');
```

##### <a name="simpleauth-library-revokepermission-method"></a> revokePermission(*string* **$permission**, *string* **$username** = *NULL*) *: [bool]*

Revoca el permiso `$permission` al usuario `$username`, retornando `TRUE` si la operación fué exitosa o `FALSE` en caso contrario.

```php
// Revocando el permiso 'general.read' al usuario actual
$this->simple_auth->revokePermission('general.read');
```

#### <a name="simpleauth-library-other-functions"></a> Otras funciones

Las siguientes funciones sirven de ayuda para tareas especiales relacionadas con la autenticación de usuario:

##### <a name="simpleauth-library-isfullyauthenticated-method"></a> isFullyAutenticated() *: [bool]*

Devuelve `TRUE` si el usuario se encuentra totalmente autenticado, `FALSE` en caso contrario. Un usuario totalmente autenticado es aquel que ha iniciado sesión directamente y NO a través de la funcionalidad "Recuérdame".

##### <a name="simpleauth-library-promptpassword-method"></a> promptPassword(*string* **$route** = `'confirm_password'`) *: [bool]*

Redirige automáticamente a la ruta `$route` en caso de que el usuario no se encuentre totalmente autenticado. Ésta función es útil para solicitar nuevamente al usuario autenticado a través de la funcionaldiad "Recuérdame" que confirme su contraseña.

##### <a name="simpleauth-library-searchuser-method"></a> searchUser(*mixed* **$search**) *: [object|null]*

Devuelve un objeto con el usuario encontrado bajo el criterio `$search`, o `NULL` en caso de no encontrar ninguno. Dependiendo del tipo de variable `$search`, este método realiza tres tipos de búsquedas:

* **int**: Buscará y devolverá al usuario con la clave primaria que coincida (configuración `simpleauth_id_col`)
* **string**: Buscará y devolverá al primer usuario que coincida con el valor de la columna establecida para el nombre de usuario durante el inicio de sesión (configuración `simpleauth_username_col`)
* **array**: Es equivalente al método `where($search)` del QueryBuilder de CodeIgniter.

Ejemplo:

```php
// Buscará el usuario con la ID 1
$this->simple_auth->searchUser(1);

// Buscará el usuario con la columna de nombre de usuario/email igual a 'admin@admin.com'
$this->simple_auth->searchUser('admin@admin.com');

// Buscará el usuario cuyo valor de columna 'gender' sea 'm' y 'active' igual a 1
$this->simple_auth->searchUser(['gender' => 'm', 'active' => 1]);
```

##### <a name="simpleauth-library-updateuser-method"></a> updateUser(*int|string* **$search**) *: [void]*

Actualiza el usuario encontrado bajo el criterio `$search`. Dependiendo del tipo de variable `$search`, este método realiza dos tipos de actualización distintas:

* **int**: Buscará y actualizará el primer usuario con el valor de la clave primaria que coincida (configuración `simpleauth_id_col`)
* **string**: Buscará y actualizará el primer usuario con el valor de columna establecida para el nombre de usuario durante el inicio de sesión que coincida (configuración `simpleauth_username_col`)

Ejemplo:
```php
// Reemplazará los datos del usuario con la ID 1
$this->simple_auth->updateUser(1, ['first_name' => 'John']);

// Reemplazará los datos del usuario con la columna de nombre de usuario/email igual a 'admin@admin.com'
$this->simple_auth->searchUser('admin@admin.com', ['gender' => 'f']);
```

##### <a name="simpleauth-library-createuser-method"></a> createUser(*array* **$data**) *: [void]*

Crea un nuevo usuario en la base de datos con los valores del arreglo `$data`. Cada índice del arreglo `$data` corresponde a una columna de la tabla de usuarios, definida en la configuración `simpleauth_users_table`

Ejemplo:

```php
$this->simple_auth->createUser(
    [
        'first_name' => 'Admin',
        'last_name'  => 'Admin',
        'username'   => 'admin',
        'email'      => 'admin@admin.com',
        'password'   => 'admin',
        'gender'     => 'm',
        'role'       => 'admin',
        'verified'   => 1
    ]
);
```

Esta función crea automáticamente el hash de la contraseña si el nombre de la columna coincide con el nombre establecido en la configuración `simpleauth_password_col`


### <a name="views-and-translations"></a> Vistas y traducciones

SimpleAuth te da la posibilidad de escoger entre diseños (skins) predeterminados o usar tus propias vistas. Los diseños incluídos en SimpleAuth tienen la ventaja de estar traducidos a varios idiomas. Por el momento, los idiomas soportados son los siguientes:

* Inglés
* Español

#### <a name="establishing-simpleauth-skin"></a> Estableciendo el skin de SimpleAuth

Para cambiar el skin utilizado en las vistas, modifica la opción `simpleauth_skin` del archivo de configuración de SimpleAuth:

```php
# application/config/auth.php

$config['simpleauth_skin'] = 'default';
```

#### <a name="establishing-simpleauth-language"></a> Estableciendo el idioma de SimpleAuth

El idioma utilizado por los skins depende del valor de la opción `language` (`$config['language']`) del archivo de configuración principal del framework (`application/config/config.php`). En caso de no encontrarse el idioma actual entre los soportados por SimpleAuth se utilizará el Inglés (`english`).

#### <a name="using-your-own-views"></a> Utilizando tus propias vistas

Puedes usar tus propias vistas sin necesidad de sobreescribir métodos del controlador de SimpleAuth. En total, 6 vistas son utilizadas por SimpleAuth:

* **login.php**: Vista de inicio de sesión
* **signup.php**: Vista de registro de usuario
* **password_prompt.php**: Vista de confirmación de contraseña actual (funcionalidad "Recuérdame")
* **password_reset.php**: Vista de del formulario de solicitud de restablecimiento de contraseña
* **password_reset_form.php**: Vista de del formulario de restablecimiento de contraseña
* **message.php**: Vista de un mensaje genérico

Por lo tanto, para utilizar tus propias vistas, basta con crear un archivo con el nombre de la vista a reemplazar, dentro de una carpeta `simpleauth` (si no existe, debes crearla primero) en tu carpeta `views`. Por ejemplo:

```php
application/views/simpleauth/login.php
application/views/simpleauth/message.php
application/views/simpleauth/password_prompt.php
application/views/simpleauth/password_reset.php
application/views/simpleauth/password_reset_form.php
application/views/simpleauth/signup.php
```

### <a name="simpleauth-configuration"></a> Configuración de SimpleAuth

La configuración de SimpleAuth se encuentra en el archivo `application/config/auth.php`. A continuación, una breve explicación de cada elemento:

#### <a name="general-configuration"></a> Configuración general

* **auth_login_route**: *[string]* Ruta de inicio de sesión. Si utilizas el método `Route::auth()` para definir las rutas de SimpleAuth, éste valor será ignorado.
* **auth_logout_route**: *[string]* Ruta de cierre de sesión. Si utilizas el método `Route::auth()` para definir las rutas de SimpleAuth, éste valor será ignorado.
* **auth_login_route_redirect**: *[string]* Ruta de redirección en caso de inicio de sesión exitoso.
* **auth_logout_route_redirect**: *[string]* Ruta de redirección inmediatamente después del cierre de sesión.
* **auth_route_auto_redirect**: *[array]* Rutas que activarán una redirección automática a la ruta `auth_login_route_redirect` en caso de que el usuario ya esté autenticado.
* **auth_form_username_field**: *[string]* Nombre del campo del formulario de inicio de sesión correspondiente al nombre de usuario/email a autenticar.
* **auth_form_username_field**: *[string]* Nombre del campo del formulario de inicio de sesión correspondiente a la contraseña de usuario a autenticar.
* **auth_session_var**: *[string]* Nombre de la variable de sesión utilizada por el módulo de Autenticación de Luthier CI.

#### <a name="enabling-disabling-features"></a> Activación/desactivación de características

* **simpleauth_enable_signup**: *[bool]* Activa el formulario de registro de usuario.
* **simpleauth_enable_password_reset**: *[bool]* Activa el formulario de restablecimiento de contraseña.
* **simpleauth_enable_remember_me**: *[bool]* Activa la función "Recuérdame" basada en cookie.
* **simpleauth_enable_email_verification**: *[bool]* Activa la verificación de correo electrónico durante el proceso de registro de usuario. Para que funcione es necesario que el envío de correos electrónicos del framework esté correctamente configurado.
* **simpleauth_enforce_email_verification**: *[bool]* Cuando esta opción es `TRUE`, SimpleAuth denegará el inicio de sesión a los usuarios que no posean su cuenta de correo electrónico verificada.
* **simpleauth_enable_brute_force_protection**: *[bool]* Activa la defensa de ataques de inicio de sesión por fuerza bruta.
* **simpleauth_enable_acl**: *[bool]* Activa las Listas de Control de Acceso (ACL)

#### <a name="views-configuration"></a> Configuración de vistas

* **simpleauth_skin**: *[string]* Skin utilizado en las vistas incluídas por SimpleAuth. Por defecto es `default`.
* **simpleauth_assets_dir**: *[string]* URL pública relativa a la aplicación donde se guardarán los recursos (css, js, etc) de las vistas de SimpleAuth.

#### <a name="access-control-list-configuration"></a> Configuración de Listas de Control de Acceso (ACL)

* **simpleauth_acl_map**: *[array]* Arreglo asociativo con los nombres e IDs de categorías y grupos de categorías de permisos usados por las Listas de Control de Acceso. Configurar esto reduce drásticamente la cantidad de consultas la base de datos, en especial cuando se posee un árbol de permisos profundo.

#### <a name="email-configuration"></a> Configuración de emails

* **simpleauth_email_configuration**: *[array|null]* Arreglo con la configuración personalizada que será suministrada durante la inicialización de la librería de email para el envío de correos de SimpleAuth. Dejar en `null` para usar la misma de la aplicación.
* **simpleauth_email_address**: *[string]* Dirección de correo electrónico que aparecerá en el campo `from` de los emails enviados por SimpleAuth.
* **simpleauth_email_name**: *[string]* Nombre que aparecerá junto al campo `from` en los correos electrónicos enviados por SimpleAuth.
* **simpleauth_email_verification_message**: *[string|null]* Mensaje automático con las instrucciones para la verificación de correo electrónico enviado al usuario luego de registrarse exitosamente en la aplicación. Dejar en `null` para usar el mensaje por defecto de SimpleAuth, que está traducido al idioma actual de la aplicación. Nota: para que los mensajes que contengan HTML puedan ser visualizados correctamente, se debe configurar primero la librería de emails.
* **simpleauth_password_reset_message**: *[string|null]* Mensaje automático con las instrucciones para el restablecimiento de contraseña. Dejar en `null` para usar el mensaje por defecto de SimpleAuth traducido al idioma actual de la aplicación. Nota: para que los mensajes que contengan HTML puedan ser visualizados correctamente, se debe configurar primero la librería de emails.

#### <a name="remember-me-functionality-configuration"></a> Configuración de funcionalidad "Recuérdame"

* **simpleauth_remember_me_field**: *[string]* Nombre del campo del formulario de inicio de sesión correspondiente a la funcionalidad "Recuérdame".
* **simpleauth_remember_me_cookie**: *[string]* Nombre de la cookie utilizada por funcionalidad "Recuérdame".

#### <a name="database-configuration"></a> Configuración de base de datos

* **simpleauth_user_provider**: *[string]* Proveedor de usuario utilizado por SimepleAuth.
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