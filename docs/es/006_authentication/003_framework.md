[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] El Framework de Autenticación de Luthier CI es una estructura sobre la cual se construyen sistemas de autenticación de usuario en CodeIgniter)

# Framework de Autenticación de Luthier CI

### Contenido

1. [Introducción](#introduction)
2. [Creación de Proveedores de usuario](#creation-of-user-providers)
   1. [Instancia de usuario](#user-instance)
   2. [Carga de usuarios](#user-load)
   3. [Hash de contraseñas y su verificación](#password-hash-and-verification)
   4. [Validar que un usuario esté activo y verificado](#validate-that-an-user-is-active-and-verified)
3. [Trabajando con Proveedores de usuario](#working-with-user-providers)
   1. [Inicio de sesión](#user-login)
   2. [Inicio de sesión avanzado](#advanced-user-login)
4. [Sesiones](#sessions)
   1. [Almacenando un usuario en la sesión](#storing-an-user-in-the-session)
   2. [Obteniendo un usuario desde la sesión](#retrieving-an-user-from-the-session)
   3. [Datos de sesión personalizados](#custom-session-data)
   4. [Eliminando la sesión actual](#deleting-custom-session)
5. [Operaciones con usuarios](#user-operations)
   1. [Verificación de roles](#roles-verification)
   2. [Verificación de permisos](#permissions-verification)
6. [Autenticación basada en controladores](#controller-based-authentication)
   1. [Configuración general](#general-configuration)
   2. [El controlador de autenticación](#the-authentication-controller)
   3. [El formulario de inicio de sesión](#the-login-form)
   4. [Cierre de sesión](#logout)
   5. [Eventos de autenticación](#authentication-events)

### <a name="introduction"></a> Introducción

El **Framework de Autenticación de Luthier CI** es una estructura sobre la cual se construyen sistemas de autenticación de usuario en CodeIgniter. Ofrece respuestas a dos grandes dilemas: de dónde se obtienen los usuarios y cómo se dispone de ellos en tu aplicación.

Durante el proceso son utilizados los **Proveedores de usuario**. Un Proveedor de usuario es una clase que se encarga de obtener de algún lugar al usuario que se pretende autenticar, funcionando como un intermediario entre CodeIgniter y, por ejemplo, una base de datos, una API o incluso un arreglo de usuarios cargado en la memoria.

Este artículo está orientado a usuarios avanzados y con necesidades muy específicas de autenticación. Si estás buscando una solución pre-configurada y fácil de usar, consulta la documentación de [SimpleAuth](./simpleauth) que, de hecho, es una implementación de todo lo que vas a leer a continuación.

### <a name="creation-of-user-providers"></a> Creación de Proveedores de usuario

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

#### <a name="user-instance"></a> Instancia de usuario

Las **instancias de usuario** son una representación lógica de los usuarios autenticados: contienen (y devuelven) todos sus detalles, roles y permisos. El primer método que debemos implementar en nuestro Proveedor de usuario es `getUserClass()`, que retorna el nombre de la **instancia de usuario** que se utilizará de ahora en adelante.

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

#### <a name="user-load"></a>  Carga de usuarios

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

Ahora nuestro Proveedor de usuario es capaz de buscar en el arreglo y devolver un *objeto de usuario* en caso de haber una coincidencia, o lanzar una excepción `UserNotFoundException` si no encuentra ningún usuario.

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

#### <a name="password-hash-and-verification"></a> Hash de contraseñas y su verificación

Los siguientes métodos a implementar se encargan de generar y validar los hash de contraseñas en el Proveedor de usuario:

* `hashPassword()`: *[string]* recibe un contraseña en texto plano y devuelve su hash

* `verifyPassword()`: *[bool]* recibe una contraseña en texto plano y hash de contraseña, validando que coincidan

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

Seguramente habrás notado que el argumento `$password` del método `loadUserByUsername()` se debe definir como opcional. Esto es así debido a que al inicio de cada solicitud, Luthier CI intenta volver a cargar el último usuario autenticado con su Proveedor de usuario, y esto sólo es posible si se permite la obtención de usuarios a partir de un dato relativamente seguro de almacenar en la sesión, como su *id* o *username*.

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
    <i class="fa fa-times" aria-hidden="true"></i>
    <strong>No uses las funciones md5() ni sha1() para hash de contraseñas</strong>
    <br />
    Estos algoritmos son muy eficientes y cualquier persona (con una computadora moderna y suficiente tiempo libre) puede intentar "romper" el cifrado por fuerza bruta. Existe una sección que habla sobre los <a href="http://php.net/manual/es/faq.passwords.php">hash de contraseñas</a> en la documentación de PHP, y es sin duda una lectura obligatoria para quienes se preocupen por este importante aspecto de seguridad.
</div>


#### <a name="validate-that-an-user-is-active-and-verified"></a> Validar que un usuario esté activo y verificado

Sólo quedan por implementar los métodos `checkUserIsActive()` y `checkUserIsVerified()` que, como sugieren sus nombres, validan que un usuario esté *activo* y su información esté *verificada*.

El criterio para que un usuario esté *activo* y *verificado* es de tu elección. En nuestro caso, para que un usuario esté *activo* su valor `active` debe ser igual a `1`, y para que esté *verificado* su valor `verified` debe ser igual a `1` también.

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

### <a name="working-with-user-providers"></a> Trabajando con Proveedores de usuario

Lo primero que debes hacer antes de usar un Proveedor de usuario es cargarlo en tu aplicación. Para ello, utiliza el método estático `loadUserProvider()` de la clase `Auth`.

Por ejemplo, para cargar el Proveedor de usuario anterior, la sintaxis es la siguiente:

```php
$myUserProvider = Auth::loadUserProvider('MyUserProvider');
```

#### <a name="user-login"></a> Inicio de sesión

Para realizar un inicio de sesión usa el método `loadUserByUsername()` de tu Proveedor de usuario, donde el primer argumento es el nombre de usuario/email y el segundo es su contraseña:

```php
// Cargamos un Proveedor de usuario:
$myUserProvider = Auth::loadUserProvider('MyUserProvider');

// Retorna el objeto de usuario correspondiente a 'jonh@doe.com':
$john = $myUserProvider->loadUserByUsername('john@doe.com', 'foo123');

// Retorna el objeto de usuario correspondiente a 'alice@brown.com':
$alice = $myUserProvider->loadUserByUsername('alice@brown.com', 'bar456');
```

Los Proveedores de usuario están diseñados de tal forma que también es posible iniciar sesión únicamente con el nombre de usuario/email:

```php
$alice = $myUserProvider->loadUserByUsername('alice@brown.com');
```

Cualquier error durante el inicio de sesión producirá una excepción, que debe ser atrapada y manejada según sea el caso:

```php
// ERROR: La contraseña de john@doe.com es incorrecta!
// (Una excepción 'UserNotFoundException' será lanzada)
$jhon = $myUserProvider->loadUserByUsername('john@doe.com', 'wrong123');

// ERROR: El usuario anderson@example.com no existe!
// (Una excepción 'UserNotFoundException' será lanzada)
$anderson = $myUserProvider->loadUserByUsername('anderson@example.com', 'test123');
```

#### <a name="advanced-user-login"></a> Inicio de sesión avanzado

Que el Proveedor de usuario devuelva un usuario no significa que realmente esté autorizado para iniciar sesión. Los métodos `checkUserIsActive()` y `checkUserIsVerified()` añaden comprobaciones adicionales convenientes.

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

Para que no tengas que definir la función `advanced_login` una y otra vez en tus aplicaciones, ya existen dos métodos que hacen lo mismo: `Auth::attempt()` y `Auth::bypass()`, el primero se usa para inicios de sesión por nombre de usuario y contraseña y el segundo para inicios de sesión por nombre de usuario solamente.

Salvo por el manejo de excepciones, las siguientes expresiones son equivalentes al código anterior:

```php
Auth::bypass('alex@rodriguez.com', 'MyUserProvider');
Auth::bypass('alice@brown.com', 'MyUserProvider');

Auth::attempt('alex@rodriguez.com', 'foo123', 'MyUserProvider');
Auth::attempt('alice@brown.com', 'bar456', 'MyUserProvider');
```

### <a name="sessions"></a> Sesiones

¿De qué sirve poder iniciar sesión si el usuario autenticado no persiste en la navegación? La clase `Auth` incluye funciones para el almacenamiento y obtención de usuarios en la sesión.

#### <a name="storing-an-user-in-the-session"></a> Almacenando un usuario en la sesión

Para almacenar un usuario en la sesión, utiliza el método estático `store()`:

```php
$alice = $myUserProvider->loadUserByUsername('alice@brown.com');
Auth::store($alice);
```

Esto guardará al usuario autenticado durante el resto de la navegación, siempre y cuando no elimines la sesión o ésta expire.

####  <a name="retrieving-an-user-from-the-session"></a> Obteniendo un usuario desde la sesión

Para obtener el usuario almacenado en la sesión, utiliza el método estático `user()`:

```php
$alice = Auth::user();
```

Éste método devuelve un objeto de **instancia de usuario**, o `NULL` en caso de no haber ningún usuario almacenado. Ejemplo:

```php
$alice = Auth::user();

// La entidad de usuario
// (El valor devuelto depende del Proveedor de usuario, aunque lo más común es que sea un objeto)
$alice->getEntity();

// Un arreglo con los roles que el Proveedor de usuario le ha asignado al usuario
$alice->getRoles();

// Un arreglo con los permisos que el Proveedor de usuario le ha asignado al usuario
$alice->getPermissions();
```

Puedes comprobar si un usuario es anónimo (o invitado) usando el método estático `isGuest()`:

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

#### <a name="custom-session-dat"></a> Datos de sesión personalizados

Para obtener y almacenar en un sólo lugar tus propios datos de sesión (relacionados con la autenticación) usa el método estático `session()`, cuyo primer argumento es el nombre del valor a almacenar, y el segundo argumento es el valor asignado.

Ejemplo:

```php
// Almacenar un valor
Auth::session('my_value', 'foo');

// Obtener un valor
$myValue = Auth::session('my_value');
var_dump( $myValue ); // foo

// Obtener TODOS los valores almacenados
var_dump( Auth::session() ); // [ 'my_value' => 'foo' ]
```

#### <a name="deleting-custom-session"></a> Eliminando la sesión actual

Para eliminar TODOS los datos de la sesión de autenticacion actual (incluído el usuario autenticado que esté almacenado en ese momento) utiliza el método estático `destroy`:

```php
Auth::destroy();
```

### <a name="user-operations"></a> Operaciones con usuarios

Hay dos operaciones disponibles para realizar con los usuarios autenticados: la verificación de roles y la verificación de permisos.

#### <a name="roles-verification"></a> Verificación de roles

Para verificar que un usuario posea un rol determinado, usa el método estático `isRole()`, cuyo primer argumento es el nombre del rol a verificar:

```php
Auth::isRole('user');
```

Se puede suministrar un objeto de usuario diferente al almacenado en sesión como segundo argumento:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isRole('admin', $user);
```

#### <a name="permissions-verification"></a> Verificación de permisos

Para verificar que el usuario posea un permiso determinado, usa el método estático `isGranted()`, cuyo primer argumento es el nombre del permiso a verificar:

```php
Auth::isGranted('general.read');
```

Se puede suministrar un objeto de usuario diferente al almacenado en sesión como segundo argumento:

```php
$alice = Auth::loadUserProvider('MyUserProvider')->bypass('alice@brown.com');
Auth::isGranted('general.read', $user);
```

### <a name="controller-based-authentication"></a> Autenticación basada en controladores

Hasta ahora has visto los elementos del Framework de Autenticación de Luthier CI trabajando por separado. ¡La buena noticia es que puedes hacerlos trabajar juntos! y todo gracias a una metodología a la que llamamos **Autenticación basada en controladores**.

La Autenticación basada en controladores consiste en la implementación de dos interfaces, una en un controlador y otra en un middleware, ambos de tu elección, que automatizan el proceso de autenticación de usuario.

#### <a name="general-configuration"></a> Configuración general

Puedes crear (aunque no es obligatorio) un archivo llamado `auth.php` dentro de la carpeta `config` de tu aplicación para configurar las opciones de la Autenticación basada en controladores. El significado de cada opción lo explicamos en la [documentación de SimpleAuth](./simpleauth#general-configuration)

Esto es un ejemplo de un archivo de configuración:

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

Si no existe el archivo se usará la configuración predeterminada, expuesta arriba.

#### <a name="the-authentication-controller"></a> El controlador de autenticación

Un controlador de autenticación es cualquier controlador de CodeIgniter que implemente la interfaz `Luthier\Auth\ControllerInterface`, la cual define los siguientes métodos:

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

Empecemos por crear un controlador llamado `AuthController.php`, que implemente todos los métodos requeridos:

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

Los valores devueltos por los métodos `getUserProvider()` y  `getMiddleware()` corresponden al **Proveedor de usuario** y al middleware con los **eventos de autenticación** que serán usados durante el proceso que sigue. En el caso del Proveedor de usuario, será el mismo de los ejemplos anteriores, `MyUserProvider`:

```php
public function getUserProvider()
{
    return 'MyUserProvider';
}
```

Mientras que para el middleware con los **eventos de autenticación** se usará uno llamado `MyAuthMiddleware` (que aún no existe) y del cual hablaremos más adelante:

```php
public function `getMiddleware()
{
    return 'MyAuthMiddleware';
}
```

Los métodos `login()` y `logout()` definen, respectivamente, el inicio y cierre de sesión. Cuando un usuario inicie sesión la solicitud será interceptada y manejada automáticamente por Luthier CI, de modo que en nuestro controlador sólo nos ocupa mostrar una vista con el formulario de inicio de sesión:

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

* **signup()**: Método con toda la lógica del registro de usuarios. Aquí se debe mostrar un formulario de registro y procesarlo (guardar el usuario en una base de datos, etc)

* **emailVerification(** *string* `$token`**)**: Se encarga de verificar el email de un usuario recién registrado. Lo normal es que se le haya enviado un email que contiene un enlace con un *token de verificación* (`$token`) hacia aquí.

* **passwordReset()**: Muestra un formulario para el restablecimiento de contraseña.

* **passwordResetForm(** *string* `$token`**)**: Verifica una solicitud de restablecimiento de contraseña. Casi siempre se trata de un email que se le envía al usuario y que contiene un enlace hacia aquí con un *token de restablecimiento de contraseña* (`$token`)

#### <a name="the-login-form"></a> El formulario de inicio de sesión

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

Luego, nos toca añadir la siguiente ruta en nuestro archivo `web.php`:

```php
Route::match(['get', 'post'], 'login', 'AuthController@login')->name('login');
```

Al acceder a la url `/login` debe aparecer el formulario de inicio de sesión que hemos creado:

<p align="center">
    <img src="https://ingenia.me/uploads/2018/06/18/luthier-ci-login-screen.png" alt="Login screen" class="img-responsive" />
</p>


Puedes obtener un arreglo con los errores ocurridos durante del proceso de autenticación y utilizarlo en tus vistas para informar al usuario. Usa el método `Auth::messages()`, como verás a continuación:

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

        // Nos ayudaremos de un arreglo con las traducciones de los códigos de error
        // devueltos (son siempre los mismos)

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

¡Tu formulario de inicio de sesión está listo!

Siéntete libre de probar cualquier combinación de usuario/contraseña disponible en tu Proveedor de usuario. Cuando logres iniciar sesión, serás redirigido a la ruta que hayas definido en la opción `$config['auth_login_route_redirect']`, o en caso de no existir dicha ruta, a la url raíz de tu aplicación.

#### <a name="logout"></a> Cierre de sesión

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

#### <a name="authentication-events"></a> Eventos de autenticación

¿Recuerdas el método `getMiddleware()` de nuestro controlador? Devuelve el nombre de un middleware *especial*: el middleware con los **eventos de autenticación**.

Vamos a crear un middleware llamado `MyAuthMiddleware` que extienda a la clase abstracta `Luthier\Auth\Middleware` y que, implementando todos los métodos requeridos, quedará así:

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

Cada método corresponde a un evento de autenticación, explicado a continuación:

* **preLogin**: Evento activado cuando el usuario visita la ruta de inicio de sesión, independientemente si inicia sesión o no.
* **onLoginSuccess**: Evento activado inmediatamente después de un inicio de sesión exitoso, y antes de la redirección que le sigue.
* **onLoginFailed**: Evento activado después de un intento de sesión fallido, y antes de la redirección que le sigue.
* **onLoginInactiveUser**: Este evento se activa si es lanzada una excepción `InactiveUserException` dentro del Proveedor de usuario, correspondiente a un error por inicio de sesión de un usuario inactivo.
* **onLoginUnverifiedUser**: Este evento se activa si es lanzada una excepción `UnverifiedUserException` dentro del Proveedor de usuario, correspondiente a un error por inicio de sesión de un usuario no verificado.
* **onLogout**: Evento activado inmediatamente después de que el usuario cierra sesión.

¡Felicidades! acabas de completar tu primera implementación de la Autenticación basada en controladores.