# Framework de Autenticación de Luthier CI

El **Framework de Autenticación de Luthier CI** es una arquitectura sobre la cual se construyen sistemas de autenticación de usuarios dentro de CodeIgniter.

Ofrece respuestas a dos grandes dilemas: de dónde se obtienen los usuarios y cómo se dispone de ellos en tu aplicación.

Durante el proceso son utilizados los **Proveedores de usuario**. Un Proveedor de usuario es una clase que se encarga de obtener de algún lugar al usuario que se pretende autenticar, funcionando como un intermediario entre CodeIgniter y, por ejemplo, una base de datos, una API o incluso un arreglo de usuarios cargado en la memoria.

Este artículo está orientado a usuarios avanzados y con necesidades muy específicas. Si lo que buscas es una solución lista para usar,  consulta la documentación de [SimpleAuth](./simpleauth?relative_url=..%2Fsimpleauth). SimpleAuth es, de hecho, es una implementación de todo lo que vas a leer a continuación.

<!-- %index% -->

### Creación de Proveedores de usuario

Todos los Proveedores de usuario se guardan en la carpeta `application/security/providers`. Además, sus clases deben implementar la interfaz `Luthier\Auth\UserProviderInterface`, que define los siguientes métodos:

```php
public function getUserClass();

public function loadUserByUsername($username, $password = null);

public function hashPassword($password);

public function verifyPassword($password, $hash);

public function checkUserIsActive(UserInterface $user);

public function checkUserIsVerified(UserInterface $user);
```

Comencemos por crear un archivo llamado `MyUserProvider.php`, que será nuestro primer Proveedor de usuario:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserProviderInterface;

class MyUserProvider implements UserProviderInterface
{

}
```

#### Instancia de usuario

Las **instancias de usuario** son una representación lógica de los usuarios autenticados: contienen (y devuelven) todos sus detalles, roles y permisos. 

El primer método que debemos implementar en nuestro Proveedor de usuario es `getUserClass()`, que retorna el nombre de la **instancia de usuario** que se utilizará de ahora en adelante.

Nuestra instancia de usuario se llamará `MyUser`, entonces:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserProviderInterface;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }
}
```


El siguiente paso es crear la clase `MyUser`. Los archivos de instancia de usuario se guardan en la carpeta `application/security/providers`. Para que Luthier CI pueda usarlas, tanto el nombre de la clase como el nombre del archivo deben coincidir con el nombre devuelto en en método `getUserClass()`.

Las instancias de usuario deben implementar la interfaz `Luthier\Auth\UserInterface`, que define los siguientes métodos:

```php
public function __construct($instance, $roles, $permissions);

public function getEntity();

public function getUsername();

public function getRoles();

public function getPermissions();
```

Implementando todos esos métodos, nuestra clase `MyUser` queda así:

```php
<?php
# application/security/providers/MyUser.php

use Luthier\Auth\UserInterface;

class MyUser implements UserInterface
{
    private $user;

    private $roles;

    private $permissions;

    public function __construct($entity, $roles, $permissions)
    {
        $this->user        = $entity;
        $this->roles       = $roles;
        $this->permissions = $permissions;
    }

    public function getEntity()
    {
        return $this->user;
    }

    public function getUsername()
    {
        return $this->user->email;
    }

    public function getRoles()
    {
        return $this->roles;
    }

    public function getPermissions()
    {
        return $this->permissions;
    }
}
```

Y nuestra estructura de archivos así:

```
application
    |- security
    |   |- providers
    |       | - MyUserProvider.php
    |       | - MyUser.php
```

#### Carga de usuarios

Los usuarios deben obtenerse de alguna parte, y el siguiente método a implementar, `loadUserByUsername()`, cumple esa función.

Sólo hay dos resultados posibles de la carga de usuarios:

* **Carga exitosa**: Se encontró un usuario y contraseña que coincidan. El Proveedor de usuario devuelve un objeto que representa al usuario.
* **Carga fallida**: No se encontró ningún usuario. Es lanzada cualquiera de las siguientes excepciones: `UserNotFoundException`, `InactiveUserException`, `UnverifiedUserException` o `PermissionNotFoundException`.

El ejemplo más sencillo es declarar un arreglo dentro del mismo Proveedor de usuario, que contiene los usuarios disponibles:

```php
$users = [
    [
        'name'      => 'John Doe',
        'email'     => 'john@doe.com',
        'password'  => 'foo123',
        'active'    => 1,
        'verified'  => 1,
    ],
    [
        'name'      => 'Alice Brown',
        'email'     => 'alice@brown.com',
        'password'  => 'bar456',
        'active'    => 1,
        'verified'  => 1,
    ]
];
```

Dicho esto, actualizaremos el código con lo siguiente:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => 'foo123',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => 'bar456',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if($user->password != $password)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }
}
```

Ahora nuestro Proveedor de usuario es capaz de buscar en el arreglo y devolver un *objeto de usuario* en caso de haber una coincidencia, o lanzar una excepción `UserNotFoundException` si no encuentra ninguno.

Sin embargo, por regla general, las contraseñas no suelen (ni deben) almacenarse directamente. En su lugar, se almacena un  *hash* generado con un algoritmo de encriptación de un solo sentido.

Considera este nuevo arreglo de usuarios:

```php
$users = [
    [
        'name'      => 'John Doe',
        'email'     => 'john@doe.com',
        'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
        'active'    => 1,
        'verified'  => 1,
    ],
    [
        'name'      => 'Alice Brown',
        'email'     => 'alice@brown.com',
        'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
        'active'    => 1,
        'verified'  => 1,
    ]
];
```

Las contraseñas de cada uno siguen siendo exactamente las mismas, la diferencia es que ahora lo almacenado es su *hash* y no la contraseña en texto plano, por lo que una comparación `$user->password == $password` no es suficiente.

#### Hash de contraseñas y su verificación

Los siguientes métodos a implementar se encargan de generar y validar los hash de contraseñas en el Proveedor de usuario:

* `hashPassword($password)`: recibe un contraseña en texto plano `$password` y retorna su hash.
* `verifyPassword($password, $hash)`: recibe una contraseña en texto plano `$password` y un hash de contraseña `$hash`. Retorna `TRUE` si la contraseña corresponde al hash, o `FALSE` en caso contrario.

La lógica y la implementación queda a criterio del desarrollador. En nuestro caso, usaremos el algoritmo `blowfish`, quedando el código así:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if(!$this->verifyPassword($password, $user->password))
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
```

Seguramente habrás notado que el argumento `$password` del método `loadUserByUsername()` se debe definir como opcional. Esto es así debido a que al momento de procesar las solicitudes HTTP entrantes Luthier CI intenta volver a cargar el último usuario autenticado con su Proveedor de usuario, y esto sólo es posible si se permite la obtención de usuarios a partir de un dato relativamente seguro de almacenar en la sesión, como su *id* o *username*.

Por lo tanto, debemos modificar un poco nuestro código para garantizar que el Proveedor de usuario aún siga siendo capaz de obtener usuarios incluso si ninguna contraseña es suministrada:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if($password !== NULL)
        {
            if(!$this->verifyPassword($password, $user->password))
            {
                throw new UserNotFoundException('Invalid user credentials!');
            }
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }
}
```

<div class="alert alert-danger">
    <strong>No uses md5() ni sha1() para el hash de contraseñas</strong><br />
    Estos algoritmos son muy eficientes, por lo que romper el cifrado por fuerza bruta es relativamente sencillo. Consulta la <a href="http://php.net/manual/es/faq.passwords.php">documentación de PHP</a> para obtener más detalles.
</div>


#### Validar que un usuario esté activo y verificado

Sólo quedan por implementar los métodos `checkUserIsActive()` y `checkUserIsVerified()` que, como sugieren sus nombres, validan que un usuario esté *activo* y su información esté *verificada*.

El criterio para que un usuario esté *activo* y *verificado* es de tu elección. En nuestro caso, para que un usuario esté *activo* su atributo `active` debe ser igual a `1`, y para que esté *verificado* su atributo `verified` debe ser igual a `1` también.

Implementando ambos métodos, nuestro Proveedor de usuario ahora se ve así:

```php
<?php
# application/security/providers/MyUserProvider.php

use Luthier\Auth\UserInterface;
use Luthier\Auth\UserProviderInterface;
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

class MyUserProvider implements UserProviderInterface
{
    public function getUserClass()
    {
        return 'MyUser';
    }

    public function loadUserByUsername($username, $password = null)
    {
        $users = [
            [
                'name'      => 'John Doe',
                'email'     => 'john@doe.com',
                'password'  => '$2y$10$c1iqXvXuFKZ4hI4l.LhCvuacba1fR3OX.uPfPD29j4DkyayC6p4uu',
                'active'    => 1,
                'verified'  => 1,
            ],
            [
                'name'      => 'Alice Brown',
                'email'     => 'alice@brown.com',
                'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
                'active'    => 1,
                'verified'  => 1,
            ]
        ];

        $userEmails   = array_column($users, 'email');
        $foundedIndex = array_search($username, $userEmails);

        if($foundedIndex === false)
        {
            throw new UserNotFoundException('Invalid user credentials!');
        }

        $user = (object) $users[$foundedIndex];

        if($password !== NULL)
        {
            if(!$this->verifyPassword($password, $user->password))
            {
                throw new UserNotFoundException('Invalid user credentials!');
            }
        }

        $userClass = $this->getUserClass();

        return new $userClass(
            /*  User data   */ $user,
            /*     Roles    */ ['user'],
            /*  Permissions */ []
        );
    }

    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    final public function checkUserIsActive(UserInterface $user)
    {
        /*
         * El método getEntity() se usa para devolver un arreglo/objeto/entidad con los
         * datos del usuario. En nuestro caso, es un objeto, por lo que podemos usar
         * la siguiente sintaxis encadenada:
         */
        if($user->getEntity()->active == 0)
        {
            throw new InactiveUserException();
        }
    }


    final public function checkUserIsVerified(UserInterface $user)
    {
        /*
         * Lo mismo aquí:
         */
        if($user->getEntity()->verified == 0)
        {
            throw new UnverifiedUserException();
        }
    }
}
```

¡Listo! ya has creado tu primer Proveedor de usuario y su Instancia de usuario adjunta. Estás listo para autenticar usuarios.

### Trabajando con Proveedores de usuario

Lo primero que debes hacer antes de usar un Proveedor de usuario es cargarlo en tu aplicación. Para ello, utiliza el método `Auth::loadUserProvider()`:

Por ejemplo, para cargar el Proveedor de usuario anterior, la sintaxis es la siguiente:

```php
$myUserProvider = Auth::loadUserProvider('MyUserProvider');
```

#### Inicio de sesión

Para realizar un inicio de sesión usa el método `loadUserByUsername()` de tu Proveedor de usuario, donde el primer argumento es el nombre de usuario/email y el segundo es su contraseña:

```php
$myUserProvider = Auth::loadUserProvider('MyUserProvider');

$john = $myUserProvider->loadUserByUsername('john@doe.com', 'foo123');

$alice = $myUserProvider->loadUserByUsername('alice@brown.com', 'bar456');
```

Cualquier error durante el inicio de sesión producirá una excepción, que debe ser atrapada y manejada:

```php
// Una excepción 'UserNotFoundException' será lanzada
$jhon = $myUserProvider->loadUserByUsername('john@doe.com', 'wrong123');

// Una excepción 'UserNotFoundException' será lanzada
$anderson = $myUserProvider->loadUserByUsername('anderson@example.com', 'test123');
```

#### Inicio de sesión avanzado

Los métodos `checkUserIsActive()` y `checkUserIsVerified()` añaden comprobaciones adicionales al inicio de sesión.

Considera el siguiente arreglo de usuarios:

```php
$users = [
    [
        'name'      => 'Alex Rodriguez',
        'email'     => 'alex@rodriguez.com',
        'password'  => '$2y$10$2nXHy1LyNL217hfyINGKy.Ef5uhxa1FdmlMDw.nbGOkSEJtT6IJWy',
        'active'    => 0,
        'verified'  => 1,
    ],
    [
        'name'      => 'Alice Brown',
        'email'     => 'alice@brown.com',
        'password'  => '$2y$10$xNHf.J7fbNdph2dy26JAdeQEA70aL/SG9ojrkpR3ocf1qph0Bafay',
        'active'    => 1,
        'verified'  => 0,
    ],
    [
        'name'      => 'Jessica Hudson',
        'email'     => 'jessica@example.com',
        'password'  => '$2y$10$IpNrG1VG53DrborE4Tl6LevtVgVfoO9.Ef9TBVgH9I10DLRnML9gi',
        'active'    => 1,
        'verified'  => 1,
    ],
];
```

Y el siguiente código de inicio de sesión:

```php
use Luthier\Auth\Exception\UserNotFoundException;
use Luthier\Auth\Exception\InactiveUserException;
use Luthier\Auth\Exception\UnverifiedUserException;

function advanced_login($username)
{
    $myUserProvider = Auth::loadUserProvider('MyUserProvider');

    try
    {
        $user = $myUserProvider->loadUserByUsername($username);
                $myUserProvider->checkUserIsActive($user);
                $myUserProvider->checkUserIsVerified($user);
    }
    catch(UserNotFoundException $e)
    {
        return 'ERROR: User not found!';
    }
    catch(InactiveUserException $e)
    {
        return 'ERROR: Inactive user!';
    }
    catch(UnverifiedUserException $e)
    {
        return 'ERROR: Unverified user!';
    }

    return 'OK: Login success!';
}

var_dump( advanced_login('alex@rodriguez.com') );  // ERROR: Inactive user!
var_dump( advanced_login('alice@brown.com') );     // ERROR: Unverified user!
var_dump( advanced_login('jack@grimes.com') );     // ERROR: User not found!
var_dump( advanced_login('jessica@example.com') ); // OK: Login success!
```

A pesar de que `alex@rodriguez.com` y `alice@brown.com` existen dentro del arreglo de usuarios, según el Proveedor de usuario el primero está inactivo y el segundo no está verificado, y como el usuario `jack@grimes.com` no existe, el único usuario que puede iniciar sesión es `jessica@example.com`.

Para que no tengas que definir la función `advanced_login` una y otra vez en tus aplicaciones, ya existen dos métodos que hacen lo mismo: `Auth::attempt()` y `Auth::bypass()`, el primero se usa para inicios de sesión por nombre de usuario y contraseña y el segundo para inicios de sesión por nombre de usuario solamente:

```php
Auth::bypass('alex@rodriguez.com', 'MyUserProvider');
Auth::bypass('alice@brown.com', 'MyUserProvider');

Auth::attempt('alex@rodriguez.com', 'foo123', 'MyUserProvider');
Auth::attempt('alice@brown.com', 'bar456', 'MyUserProvider');
```

### Sesiones

La clase `Auth` incluye funciones para el almacenamiento y obtención de usuarios en la sesión.

#### Almacenando un usuario en la sesión

Para almacenar un usuario en la sesión, utiliza el método `Auth::store($user)`, donde `$user` es una **Instancia de Usuario**:

```php
$alice = $myUserProvider->loadUserByUsername('alice@brown.com');
Auth::store($alice);
```

#### Obteniendo un usuario desde la sesión

Para obtener el usuario almacenado en la sesión, utiliza el método  `Auth::user()`, que retorna una **Instancia de Usuario**, o `NULL` si no hay ningún usuario almacenado en la sesión:

```php
$alice = Auth::user();
```

Puedes comprobar si un usuario es anónimo (invitado) usando el método `Auth::isGuest()`:

```php
if( Auth::isGuest() )
{
    echo "Hi Guest!";
}
else
{
    echo "Welcome " . Auth::user()->getEntity()->name . "!";
}
```

#### Datos de sesión personalizados

Para obtener y almacenar tus propios datos de sesión, usa el método estático `Auth::session($name, $value)`, donde `$name` es el nombre de la variable de sesión, y `$value` el valor a asignar:

Ejemplo:

```php
// Almacenar un valor
Auth::session('my_value', 'foo');

// Obtener un valor
$myValue = Auth::session('my_value');

// Obtener TODOS los valores almacenados
var_dump( Auth::session() );
```

#### Eliminando la sesión actual

Para eliminar TODOS los datos de la sesión actual, utiliza el método estático `Auth::destroy()`:

```php
Auth::destroy();
```

### Operaciones con usuarios

Hay dos operaciones disponibles para realizar con los usuarios autenticados: la **verificación de roles** y la **verificación de permisos**.

#### Verificación de roles

Para verificar que un usuario posea un rol, usa el método  `Auth::isRole($role)`, donde `$role` es el nombre del rol:

```php
Auth::isRole('user');
```

Se puede suministrar un objeto de usuario personalizado como segundo argumento:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isRole('admin', $user);
```

#### Verificación de permisos

Para verificar que un usuario posea un permiso, usa el método estático `Auth::isGranted($permission)`, donde `$permission` es el nombre del permiso:

```php
Auth::isGranted('general.read');
```

Se puede suministrar un objeto de usuario diferente al almacenado en sesión como segundo argumento:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isGranted('general.read', $user);
```

### Autenticación basada en controladores

Hasta ahora has visto los elementos del Framework de Autenticación de Luthier CI trabajando por separado. ¡La buena noticia es que puedes hacerlos trabajar juntos! gracias a una metodología llamada  **Autenticación basada en controladores**.

La Autenticación basada en controladores consiste en la implementación de dos interfaces, una en un controlador y otra en un middleware, ambos de tu elección, que automatizan el proceso de autenticación de usuario.

#### Configuración general

Puedes crear (aunque no es obligatorio) un archivo llamado `auth.php` dentro de la carpeta `config` de tu aplicación para configurar las opciones de la Autenticación basada en controladores:

```
<?php
# application/config/auth.php

$config['auth_login_route']  = 'login';

$config['auth_logout_route'] = 'logout';

$config['auth_login_route_redirect'] = 'dashboard';

$config['auth_logout_route_redirect'] = 'homepage';

$config['auth_route_auto_redirect'] = [];

$config['auth_form_username_field'] = 'email';

$config['auth_form_password_field'] = 'password';

$config['auth_session_var'] = 'auth';
```

<div class="alert alert-info">
    Si no existe el archivo se usará la configuración predeterminada, expuesta arriba.
</div>

#### El controlador de autenticación

Un **controlador de autenticación** es cualquier controlador de CodeIgniter que implemente la interfaz `Luthier\Auth\ControllerInterface`, la cual define los siguientes métodos:

```php
public function getUserProvider();

public function getMiddleware();

public function login();

public function logout();

public function signup();

public function emailVerification($token);

public function passwordReset();

public function passwordResetForm($token);
```

Empecemos por crear un controlador `AuthController.php` que implemente todos los métodos requeridos por la interfaz:

```php
<?php
# application/controllers/AuthController.php

defined('BASEPATH') OR exit('No direct script access allowed');

use Luthier\Auth\ControllerInterface;

class AuthController extends CI_Controller implements ControllerInterface
{
    public function getUserProvider()
    {
        return 'MyUserProvider';
    }

    public function getMiddleware()
    {
        return 'MyAuthMiddleware';
    }

    public function login()
    {
        $this->load->view('auth/login.php');
    }

    public function logout()
    {
        return;
    }

    public function signup()
    {
        $this->load->view('auth/signup.php');
    }

    public function emailVerification($token)
    {
        $this->load->view('auth/email_verification.php');
    }

    public function passwordReset()
    {
        $this->load->view('auth/password_reset.php');
    }

    public function passwordResetForm($token)
    {
        $this->load->view('auth/password_reset_form.php');
    }
}
```

Los valores devueltos por los métodos `getUserProvider()` y  `getMiddleware()` corresponden al **Proveedor de usuario** y al middleware con los **eventos de autenticación** que serán usados durante el proceso que sigue. En el caso del Proveedor de usuario, usaremos el mismo de los ejemplos anteriores, `MyUserProvider`:

```php
public function getUserProvider()
{
    return 'MyUserProvider';
}
```

El middleware con los **eventos de autenticación** será uno llamado `MyAuthMiddleware` (aún no existe) y del cual hablaremos más adelante:

```php
public function `getMiddleware()
{
    return 'MyAuthMiddleware';
}
```

Los métodos `login()` y `logout()` definen la lógica de inicio y cierre de sesión. Cuando un usuario envía el formulario de inicio de sesión, la solicitud es interceptada y manejada automáticamente por Luthier CI, de modo que sólo necesitamos renderizar una vista aquí:

```php
public function login()
{
    $this->load->view('auth/login.php');
}
```

El cierre de sesión también será manejado por Luthier CI, así que nuestro método `logout()` no hace absolutamente nada a nivel de controlador:

```php
public function logout()
{
    return;
}
```

La implementación de los métodos restantes depende de ti, pero te damos una idea de cuales deberían ser sus funciones:

| Método | Función que cumple |
| :--- | :--- |
| **signup()** | Lógica del registro de usuario. Aquí se debe mostrar un formulario de registro y procesarlo (guardar el usuario en una base de datos, etc) |
| **emailVerification($token)** | Verifica la dirección de email de un usuario recién registrado con un *tóken de verificación* `$token`, normalmente enviado como un link por correo electrónico |
| **passwordReset()** | Muestra un formulario para el restablecimiento de contraseña |
| **passwordResetForm($token)** | Realiza un restablecimiento de contraseña, después de validar el *tóken de restablecimiento de contraseña* `$token`, que normalmente es enviado al usuario como un link por correo electrónico |

#### El formulario de inicio de sesión

Nuestro método `login()` hace referencia a una vista llamada `auth/login.php`. Vamos a crearla:

```html
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in</title>
</head>
<body>
    <h1>Log in</h1>
    <form method="post">
        <input type="text" name="username" required />
        <input type="password" name="oasswird" required />
        <button type="submit">Log in</button>
    </form>
</body>
</html>
```

Luego, añadimos la siguiente ruta en nuestro archivo `web.php`:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
```

Al acceder a la url `/login` debe aparecer el formulario de inicio de sesión que hemos creado.

Puede obtener un arreglo con los errores ocurridos durante el proceso de autenticación con el método `Auth::messages()` y usarlo en sus vistas para informar al usuario:

```php
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Log in</title>
</head>
<body>
    <h1>Log in</h1>
    <?php
        $errorMessages = [
            'ERR_LOGIN_INVALID_CREDENTIALS' => 'Correo electrónico o contraseña incorrecta',
            'ERR_LOGIN_INACTIVE_USER'       => 'Usuario inactivo',
            'ERR_LOGIN_UNVERIFIED_USER'     => 'Usuario no verificado',
        ];
    ?>

    <?php foreach(Auth::messages() as $type => $message){ ?>
        <div class="alert alert-<?= $type ;?>">
            <?= $errorMessages[$message] ;?>
        </div>
    <?php } ?>

    <form method="post">
        <input type="email" name="email" required />
        <input type="password" name="password" required />
        <button type="submit">Log in</button>
    </form>
</body>
</html>
```

¡Tu formulario de inicio de sesión está listo! Siéntete libre de probar cualquier combinación de usuario/contraseña disponible en tu Proveedor de usuario. 

<div class="alert alert-info">
    Cuando iniciar sesión serás redirigido a la ruta que hayas definido en la opción `$config['auth_login_route_redirect']` o, en caso de no existir dicha ruta, a la url raíz de tu aplicación.
</div>

#### Cierre de sesión

Ahora vamos a configurar el cierre de sesión. Lo único que hace falta es definir la ruta
que se usará, y por defecto será aquella que hayas llamado `logout`:

```php
Route::get('logout', 'AuthController@logout')->name('logout');
```

Nuestro archivo de rutas quedará, finalmente, parecido a esto:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
Route::get('logout', 'AuthController@logout')->name('logout');
```

#### Eventos de autenticación

¿Recuerdas el método `getMiddleware()` de nuestro controlador? Devuelve el nombre de un middleware *especial*: el middleware con los **eventos de autenticación**.

Vamos a crear un middleware llamado `MyAuthMiddleware` que extienda a la clase abstracta `Luthier\Auth\Middleware`. Una vez implementados todos los métodos requeridos, quedará así:

```php
<?php
# application/middleware/MyAuthMiddleware.php

defined('BASEPATH') OR exit('No direct script access allowed');

use Luthier\Route;
use Luthier\Auth\UserInterface;

class MyAuthMiddleware extends Luthier\Auth\Middleware
{
    public function preLogin(Route $route)
    {
        return;
    }

    public function onLoginSuccess(UserInterface $user)
    {
        return;
    }

    public function onLoginFailed($username)
    {
        return;
    }

    public function onLoginInactiveUser(UserInterface $user)
    {
        return;
    }

    public function onLoginUnverifiedUser(UserInterface $user)
    {
        return;
    }

    public function onLogout()
    {
        return;
    }
}
```

Cada método corresponde a un evento de autenticación:

| Evento | Descripción |
| :--- | :--- |
| **preLogin** | Evento activado cuando el usuario visita la ruta de inicio de sesión, independientemente si inicia sesión o no. |
| **onLoginSuccess** | Evento activado inmediatamente después de un inicio de sesión exitoso, y antes de la redirección que le sigue. |
| **onLoginFailed** | Evento activado después de un intento de sesión fallido, y antes de la redirección que le sigue. |
| **onLoginInactiveUser** | Evento activado si es lanzada una excepción `InactiveUserException` dentro del Proveedor de usuario |
| **onLoginUnverifiedUser** | Evento activado si es lanzada una excepción `UnverifiedUserException` dentro del Proveedor de usuario |
| **onLogout** | Evento activado después de que el usuario cierra sesión |