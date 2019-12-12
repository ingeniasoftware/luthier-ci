# SimpleAuth

Con SimpleAuth puedes añadir un formulario de inicio de sesión personalizable y listo para usar en CodeIgniter.

SimpleAuth se compone de un controlador (`SimpleAuthController`) un middleware (`SimpleAuthMiddleware`) y una librería (`Simple_auth`) todo ello construído sobre el **Framework de Autenticación de Luthier CI**.

<!-- %index% -->

### Instalación de SimpleAuth

La instalación se realiza a través del comando `make` de las [Herramientas CLI Incorporadas de Luthier CI](../cli#herramientas-para-cli-incorporadas?relative_url=..%2Fcli%23herramientas-para-cli-incorporadas). Es necesario configurar una conexión a una base de datos (en `application/config/database.php`) y activar las migraciones (en `application/config/migration.php`) antes de proceder con la instalación.

#### Paso 1: Copiar los archivos necesarios

Abre una terminal y ejecuta en la carpeta raíz de tu aplicación el siguiente comando:

```
php index.php luthier make auth
```

#### Paso 2: Instalar la base de datos

Para instalar la base de datos ejecuta lo siguiente desde la línea de comandos:

```
php index.php luthier migrate
```

<div class="alert alert-info">
    Si ocurre un error asegúrate de que los paraémtros de conexión a la base de datos son correctos
</div>

#### Paso 3: Definir las rutas

En tu archivo de rutas `web.php` añade la siguiente línea:

```php
Route::auth();
```

El método `Route::auth()` es un atajo para crear todas las rutas  necesarias:

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

Al visitar la URL `/login` de tu aplicación deberás ver tu nueva pantalla de inicio de sesión.

<div class="alert alert-warning">
    <strong>La ruta de cierre de sesión</strong><br />
    Por defecto, la ruta <code>logout</code> sólo acepta peticiones POST, así que navegar a la URL <code>/logout</code> no va a funcionar para cerrar la sesión. Lo ideal sería usar un formulario HTML que apunte a esa ruta, no obstante, si quieres aceptar peticiones GET usa <code>Route::auth(FALSE)</code>
</div>

### Controlador de SimpleAuth

El controlador de SimpleAuth (`SimpleAuthController`) contiene la lógica de cara al usuario para las operaciones de autenticación, tales como el **inicio de sesión**, el **registro de usuario** y el **restablecimiento de contraseña**. Un controlador de SimpleAuth recién creado se ve parecido a esto:

```php
<?php
# application/controllers/SimpleAuthController.php

defined('BASEPATH') OR exit('No direct script access allowed');

/* (...) */

class SimpleAuthController extends Luthier\Auth\SimpleAuth\Controller
{

    /**
     * (...)
     */
    public function getSignupFields()
    {
        return [ ... ];
    }

    /**
     * (...)
     */
    public function getUserFields()
    {
        return [ ... ];
    }
}
```

La clase a la que extiende éste controlador (`Luthier\Auth\SimpleAuth\Controller`) ya provee todos los métodos necesarios por lo que, a menos que desees crear algo personalizado, no hace falta modificar mucho aquí.

#### Personalizar el formulario de registro de usuario

Puedes cambiar los campos del formulario de registro a tu gusto modificando el arreglo que es devuelto por el método `getSignupFields()`. Ésto es un ejemplo de la estructura que debe tener el arreglo:

```php
public function getSignupFields()
{
    return [
        'Field name 1' => [
            'Field type',
            'Field label',
            [ /* HTML5 attributes array */ ],
            [ /* CI Validation rules array */ ] ,
            [ /* CI Validation error essages array (Optional) */ ]
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

El método `getUserFields()` retorna un arreglo con las columnas de la tabla donde se almacenan los usuarios en la base de datos. Cada elemento del arreglo **debe coincidir** con el nombre del campo HTML en el formulario de registro de usuario:

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

### Middleware de SimpleAuth

El middleware de SimpleAuth (`SimpleAuthMiddleware`) se usa para proteger aquellas rutas de la aplicación en las que se requiera que el usuario esté autenticado. Éste middleware se encarga automáticamente de verificar el estado actual del usuario:

* Si el usuario **está autenticado** la solicitud sigue con normalidad.
* Si el usuario **no está autenticado** se intentará restaurar la sesión utilizando la característica *Recuérdame*.
* Si no es posible restaurar ninguna sesión previa se redireccionará al usuario a la pantalla de inicio de sesión.

Puedes usar el middleware de SimpleAuth en tus rutas y grupos de rutas como cualquier otro middleware, e incluso combinarlo con tus propios middleware para añadir capas de seguridad adicionales:

```php
<?php
# application/routes/web.php

Route::auth();
Route::get('/', 'FrontendController@homepage')->name('homepage');
Route::group('dashboard', ['middleware' => ['SimpleAuthMiddleware']], function(){
    Route::get('/', 'UserArea@dashboard');
});
```

### Librería de SimpleAuth

La librería de SimpleAuth contiene métodos para realizar operaciones que involucran usuarios. Para usar la librería de SimpleAuth primero debes cargarla en el framework:

```php
$this->load->library('Simple_auth');
```

##### Obtener el usuario actual

Para obtener el usuario autenticado actual, usa el método `user()`. Éste método retorna una **instancia de usuario** si el usuario se encuentra autenticado, o `NULL` en caso contrario:

```php
$user = $this->simple_auth->user();
```

Puedes acceder a los datos del usuario como propiedades del objeto devuelto:

```php
$user = $this->simple_auth->user();

$firstName = $user->first_name;
$lastName = $user->last_name;
```

<div class="alert alert-info">
    El método <code>user()</code> y las <strong>instancias de usuario</strong> que son devueltas se tratan en detalle en la documentación del <strong>Framework de Autenticación de Luthier CI</strong>.
</div>

##### Verificar si un usuario es invitado (anónimo)

El método `isGuest()` retorna `TRUE` si el usuario es anónimo (no ha iniciado sesión) o `FALSE` en caso contrario:

```php
$this->simple_auth->isGuest();
```

##### Verificar el rol de un usuario

Para verificar si un usuario posee un rol en específico, usa el método `isRole($role)`, el cual retorna `TRUE` si el usuario posee el rol `$role`, o `FALSE` en caso contrario:

```php
$this->simple_auth->isRole('ADMIN');
```

##### Verificar los permisos de un usuario

Para verificar si un usuario posee un permiso en específico, usa el método `isGranted($permission)`, el cual retorna `TRUE` si el usuario posee el permiso `$permission`, o `FALSE` en caso contrario:

```php
$this->simple_auth->isGranted('general.read');
```

Para verificar si un usuario pertenece a un rol que comience por una frase/categoría en específico, utiliza el caracter (**\***):

```php
$this->simple_auth->isGranted('general.*');
```

##### Verificar si un usuario está completamente autenticado

El método `isFullyAuthenticated()` devuelve `TRUE` si el usuario se encuentra completamente autenticado, o `FALSE` en caso contrario:

```php
$this->simple_auth->isFullyAuthenticated();
```

Un usuario completamente autenticado es aquel que ha iniciado sesión a través del formulario de inicio de sesión y no por medio de la característica _Recuérdame_.

##### Solicitar contraseña

El método `promptPassword($route)` redirige automáticamente a `$route` en caso de que el usuario no esté **completamente autenticado** (ver función `isFullyAuthenticated()`). 

Esto es útil para validar la sesión de los usuarios autenticados mediante la característica *Recuérdame*.

##### Buscar un usuario

El método `searchUser($search)` devuelve un objeto con el primer usuario que coincida con `$search`, o `NULL` en caso de no encontrar ninguno. El criterio de búsqueda varía dependiendo del tipo de variable `$search` suministrado:

* Si `$search` es un **entero**, se buscará y devolverá el usuario con la clave primaria (ID) que coincida.
* Si `$search` es un **string**, Buscará y devolverá el primer usuario que coincida con el valor de la columna establecida para el nombre de usuario (parámetro `simpleauth_username_col`)
* Si `$search` es un **array**, se tratará como el `where($search)` del QueryBuilder nativo de CodeIgniter.

Ejemplo:

```php
$this->simple_auth->searchUser(1);
$this->simple_auth->searchUser('admin@admin.com');
$this->simple_auth->searchUser(['gender' => 'm', 'active' => 1]);
```

##### Modificar un usuario

El método `updateUser($search, $values)` modifica el usuario encontrado bajo el criterio `$search` (ver método `searchUser()`) con los nuevos valores del arreglo `$values`. 

Ejemplo:

```php
$this->simple_auth->updateUser(1, ['first_name' => 'John']);
$this->simple_auth->updateUser('admin@admin.com', ['gender' => 'f']);
```

##### Crear un usuario

El método `createUser($user)` crea un nuevo usuario en la base de datos con los valores del arreglo `$data`. Cada índice del arreglo `$data` corresponde a una columna de la tabla de usuarios.

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

<div class="alert alert-info">
    Esta método crea automáticamente el hash de la contraseña para el nombre de la columna que coincida el valor establecido en la opción <code>simpleauth_password_col</code>
</div>

### Listas de Control de Acceso (ACL)

Las **Listas de Control de Acceso** proporcionan un mayor control de los permisos asignados a los usuarios. Cuando se usa ésta característica, un usuario tiene uno o varios permisos asignados que, en conjunto, le conceden (o niegan) el acceso a determinados recursos de la aplicación.

<div class="alert alert-info">
    En SimpleAuth no existen <em>grupos de usuarios</em> ni nada parecido. Los permisos de usuario se almacenan como un árbol de permisos dentro de una base de datos.
</div>

<div class="alert alert-info">
    No existe un método para crear o eliminar permisos, por lo que debes hacerlo manualmente. Las tablas usadas por las ACL, sin embargo, son creadas con las migraciones que se incluyen con SimpleAuth
</div>

Considera los siguientes permisos almacenados en la tabla `user_permissions_categories`:

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

Y la siguiente aignación de permisos en la tabla `user_permissions`:

```
ID      USERNAME    PERMISSION_ID
---------------------------------
1       anderson    2
2       anderson    5
3       julio       3
4       julio       6
```

Cuando el usuario `anderson` inicie sesión, tendrá los siguientes permisos:

```
general.read
general.delete.local
```

Y cuando el usuario `julio` inicie sesión, tendrá los siguientes permisos:

```
general.write
general.delete.global
```

##### Verificar que un permiso exista

El método `permissionsExists($permission)` devuelve `TRUE` si el permiso `$permission` existe en la tabla de la Lista de Control de Acceso (ACL), o `FALSE` en caso contrario.

Ejemplo:

```php
$this->simple_auth->permissionExists('general.read');
```

##### Conceder un permiso a un usuario

El método `grantPermission($permission, $username)` asigna el permiso `$permission` al usuario `$username` y devuelve `TRUE` si la operación fué exitosa o `FALSE` en caso contrario. Si se omite el argumento `$user` la operación se realiza en el usuario autenticado actual.

Ejemplo:

```php
$this->simple_auth->grantPermission('general.read');
```

##### Revocar un permiso a un usuario

El método `revokePermission($permission, $username)` revoca el permiso `$permission` del usuario `$username`, retornando `TRUE` si la operación fué exitosa o `FALSE` en caso contrario. Si se omite el argumento `$username` la operación se realiza en el usuario autenticado actual.

Ejemplo:

```php
$this->simple_auth->revokePermission('general.read');
```

### Vistas y traducciones

Puedes cambiar el diseño (skin) de los formularios renderizados por SimpleAuth escogiendo entre las vistas predeterminadas o tus propias vistas. Los vistas predeterminadas en SimpleAuth tienen la ventaja de estar traducidas a varios idiomas. Los idiomas soportados por SimpleAuth son los siguientes:

* Inglés
* Español
* Italiano

##### Establecer el skin de SimpleAuth

Para cambiar el skin modifica la opción `simpleauth_skin` del archivo de configuración de SimpleAuth:

```php
# application/config/auth.php

$config['simpleauth_skin'] = 'default';
```
El idioma utilizado por los skins es tomado del valor de la opción `language` (`$config['language']`) del archivo de configuración principal del framework (`application/config/config.php`). 

<div class="alert alert-info">
    En caso de no encontrarse el idioma actual entre los idiomas soportados por SimpleAuth, se utilizará el Inglés
</div>

##### Utilizando tus propias vistas

En total, 6 vistas son utilizadas por SimpleAuth:

* **login.php**: Vista de inicio de sesión
* **signup.php**: Vista de registro de usuario
* **password_prompt.php**: Vista de confirmación de contraseña actual (característica *Recuérdame*)
* **password_reset.php**: Vista de del formulario de solicitud de restablecimiento de contraseña
* **password_reset_form.php**: Vista de del formulario de restablecimiento de contraseña
* **message.php**: Vista de un mensaje genérico

Para utilizar tus propias vistas, crea un archivo con el mismo nombre de la vista a reemplazar dentro de la carpeta `applications/views/simpleauth`.

Por ejemplo:

```php
application/views/simpleauth/login.php
application/views/simpleauth/message.php
application/views/simpleauth/password_prompt.php
application/views/simpleauth/password_reset.php
application/views/simpleauth/password_reset_form.php
application/views/simpleauth/signup.php
```

### Configuración de SimpleAuth

La configuración de SimpleAuth se encuentra en el archivo `application/config/auth.php`. Éste archivo es creado automáticamente durante la instalación de SimpleAuth.

##### Activación/desactivación de características

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_enable_signup** | *bool* | Activa o desactiva el formulario de registro de usuario |
| **simpleauth_enable_password_reset** | *bool* | Activa o desactiva el formulario de restablecimiento de contraseña |
| **simpleauth_enable_remember_me** | *bool* | Activa o desactiva la característica *Recuérdame* |
| **simpleauth_enable_email_verification** | *bool* | Activa o desactiva la verificación de correo electrónico. Para usar esta característica necesitas cargar y configurar la [Librería de Email](https://codeigniter.com/user_guide/libraries/email.html) |
| **simpleauth_enforce_email_verification** | *bool* | Forza a los usuarios a verificr su dirección de correo electrónio. Para usar esta característica necesitas cargar y configurar la [Librería de Email](https://codeigniter.com/user_guide/libraries/email.html) |
| **simpleauth_enable_brute_force_protection** | *bool* | Activa o desactiva la defensa contra ataques de inicio de sesión por fuerza bruta |
| **simpleauth_enable_acl** | *bool* | Activa o desactiva las Listas de Control de Acceso (ACL) |

##### Configuración general

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_user_provider** | *string* | *Proveedor de usuario* utilizado por SimepleAuth |
| **auth_login_route** | *string* | Ruta de inicio de sesión. Si utilizas el método `Route::auth()` para definir las rutas de SimpleAuth éste valor será ignorado |
| **auth_logout_route** | *string* | Ruta de cierre de sesión. Si utilizas el método `Route::auth()` para definir las rutas de SimpleAuth éste valor será ignorado |
| **auth_login_route_redirect** | *string* | Ruta a redireccionar después de iniciar sesión |
| **auth_logout_route_redirect** | *string* |  Ruta a redireccionar después de cerrar sesión |
| **auth_route_auto_redirect** | *array* |  Rutas que activarán una redirección automática a `auth_login_route_redirect` si el usuario está autenticado |
| **auth_form_username_field** | *string* | Nombre del campo del formulario de inicio de sesión correspondiente al nombre de usuario/email |
| **auth_form_password_field** | *string* | Nombre del campo del formulario de inicio de sesión correspondiente a la contraseña  |
| **auth_session_var** | *string* | Nombre de la variable de sesión utilizada por el Framework de Autenticación de Luthier CI |

##### Configuración de vistas

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_skin** | *string* | Skin utilizado en las vistas incluídas por SimpleAuth. |
| **simpleauth_assets_dir** | *string* | URL pública para los recursos de SimpleAuth (css, js, etc.) |

##### Configuración de Listas de Control de Acceso (ACL)

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_acl_map** | *array* | Arreglo asociativo (`'name' => 'Category ID'`) de las categorías y grupos de permisos usados por las Listas de Control de Acceso. Configurar esto mejora considerablemente el rendimiento de la base de datos |

##### Configuración de emails

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_email_configuration** | *array\|null* | Arrelo con la configuración de la **Librería de Email** usada por SimpleAuth. Dejar en `NULL` para usar la misma de la aplicación |
| **simpleauth_email_address** | *string* | Dirección de correo electrónico que aparecerá en el campo `from` de los emails enviados por SimpleAuth |
| **simpleauth_email_name** | *string* | Nombre del remitente de los correos enviados por SimpleAuth |
| **simpleauth_email_verification_message** | *string\|null* | Plantilla del email con las instrucciones de verificación de correo electrónico que es enviado por SimpleAuth luego del regitro de usuario. Dejar en `null` para usar el mensaje por defecto. |
| **simpleauth_password_reset_message** | *string\|null* | Plantilla del email con las instrucciones para el restablecimiento de contraseña. Dejar en `null` para usar el mensaje por defecto. |

##### Configuración de la característica "Recuérdame"

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_remember_me_field** | *string* | Nombre del campo del formulario de inicio de sesión usado por la característica _Recuérdame_ |
| **simpleauth_remember_me_cookie** | *string* | Nombre de la cookie usada por la característica _Recuérdame_ |

##### Configuración de base de datos

| Parámetro | Tipo  | Descripción |
| :--- | :---: | :--- |
| **simpleauth_users_table** | *string* | Nombre de la tabla donde se almacenan los usuarios |
| **simpleauth_users_email_verification_table** | *string* | Nombre de la tabla donde se almacenan los tokens de verificación de correo electrónico |
| **simpleauth_password_resets_table** | *string* | Nombre de la tabla donde se almacenan los tokens de restablecimiento de contraseña |
| **simpleauth_login_attempts_table** | *string* | Nombre de la tabla donde se almacenan los intentos de inicio de sesión fallidos |
| **simpleauth_users_acl_table** | *string* | Nombre de la tabla para ACL |
| **simpleauth_users_acl_categories_table** | *string* | Nombre de la tabla donde se almacenan las categorías de los permisos usados por ACL |
| **simpleauth_id_col** | *string* | Nombre de la columna _ID_ de la tabla de usuarios |
| **simpleauth_username_col** | *string* | Nombre de la columna _username_ de la tabla de usuarios |
| **simpleauth_email_col** | *string* | Nombre de la columna _email_ de la tabla de usuarios |
| **simpleauth_password_col** | *string* | Nombre de la column _password_ de la tabla de usuarios |
| **simpleauth_role_col** | *string* | Nombre de la columna _role_ de la tabla de usuarios. Es usada para la comprobación de roles |
| **simpleauth_active_col** | *string* | Nombre de la columna _active_ de la tabla de usuarios. El valor de ésta columna es un booleano, donde `1` corresponde a un usuario activo, y `0` a un usuario inactivo. |
| **simpleauth_verified_col** | *string* | Nombre de la columna _verified_ de la tabla de usuarios. El valor de ésta columna es un booleano, donde `1` corresponde a un usuario verificado, y `0` a un usuario no verificado. |
| **simpleauth_remember_me_col** | *string* | Nombre de la columna donde son almacenados los tokens usados por la característica _Recuérdame_ |
