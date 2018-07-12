[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] El enrutamiento de Luthier CI explicado a fondo. Aprende más sobre las rutas y una nueva sintaxis inspirada en Laravel que está a tu alcance)

# Rutas

### Contenido

1. [Introducción](#introduction)
2. [Tipos de rutas](#route-types)
3. [Sintaxis](#syntax)
   1. [Espacios de nombre](#namespaces)
   2. [Prefijos](#prefixes)
   3. [Rutas con nombre](#named-routes)
   4. [Funciones anónimas como rutas](#callbacks-as-routes)
   5. [Grupos](#groups)
   6. [Rutas de recurso](#resource-routes)
   7. [Controlador por defecto](#default-controller)
4. [Parámetros](#parameters)
   1. [Parámetros opcionales](#optional-parameters)
   2. [Expresiones regulares en parámetros](#parameter-regex)
   3. [Parámetros "adhesivos"](#sticky-parameters)

### <a name="introduction"></a> Introducción

Luthier CI cambia el comportamiento del enrutador de CodeIgniter:

* En CodeIgniter, por defecto, las rutas son accesibles bajo cualquier verbo HTTP. Con Luthier CI es obligatorio definir los verbos HTTP aceptados para cada ruta y cualquier petición que no coincida con dichos parámetros generará un error 404.
* En CodeIgniter es posible acceder a los controladores directamente desde la URL sin necesidad de definir rutas. En cambio, con Luthier CI, intentar acceder a una ruta que no esté definida (incluso si la URL coincide con el nombre del controlador y del método) generará un error 404.
* En CodeIgniter los parámetros de rutas son simples expresiones regulares que apuntan a controladores, en Luthier CI una ruta es una entidad independiente y única, que contiene parámetros bien definidos y con la capacidad de construir URLs a partir de ellas.
* En CodeIgniter únicamente se pueden crear rutas que apunten a controladores. Con Luthier CI es posible utilizar funciones anónimas como controladores e incluso construir una aplicación web completa sin usar ni un solo controlador.

### <a name="route-types"></a> Tipos de rutas

Puedes trabajar con tres tipos de rutas:

* **Rutas HTTP**: se acceden bajo peticiones HTTP y se definen en el archivo `application/routes/web.php`
* **Rutas AJAX**: se acceden únicamente bajo peticiones AJAX y se definen en el archivo `application/routes/api.php`
* **Rutas CLI**: se acceden únicamente bajo un entorno CLI (Command Line Interface) y se definen en el archivo `application/routes/cli.php`

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Las rutas AJAX van en api.php</strong>
    <br />
    A pesar de que puedes definir rutas AJAX en el archivo <code>web.php</code>, lo ideal es hacerlo en <code>api.php</code>
</div>

### <a name="syntax"></a> Sintaxis

Si has usado Laravel entonces sabrás usar Luthier CI, pues su sintaxis es idéntica. Este es el ejemplo más sencillo posible de una ruta:

```php
Route::get('foo', 'bar@baz');
```

Donde **foo** es la URL de la ruta y **bar@baz** es el nombre del controlador y método (separados por el @) al que apunta. Al usar el método `get()` estás indicando a Luthier CI que la ruta va a estar disponible bajo peticiones GET.

<div class="alert alert-info">
    <i class="fa fa-info-circle" aria-hidden="true"></i>
    <strong>La primera ruta es la que gana</strong>
    <br />
    Si defines dos o más rutas con la misma URL y el mismo verbo HTTP, la primera será devuelta SIEMPRE
</div>

Luthier CI te permite definir rutas HTTP con los verbos GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS y TRACE:

```php
Route::post('foo', 'bar@baz');
Route::put('foo', 'bar@baz');
Route::patch('foo', 'bar@baz');
Route::delete('foo', 'bar@baz');
Route::head('foo', 'bar@baz');
Route::options('foo', 'bar@baz');
Route::trace('foo', 'bar@baz');
```

También, puedes pasar un arreglo con las _propiedades_ de la ruta como tercer argumento (explicados más adelante)

```php
Route::get('test', 'controller@method', ['prefix' => '...', 'namespace' => '...', (...)] );
```

Para aceptar múltiples verbos HTTP en una ruta, usa el método `match()`:

```php
Route::match(['GET', 'POST'], 'path', 'controller@method', [ (...) ]);
```

#### <a name="namespaces"></a> Espacios de nombre

La propiedad `namespace` le indica a CodeIgniter el sub-directorio donde se encuentra el controlador. (Nota que esto no es un _espacio de nombre_ de PHP, sino un nombre de directorio)

```php
// El controlador está ubicado en application/controllers/admin/Testcontroller.php
Route::get('hello/world', 'testcontroller@index', ['namespace' => 'admin']);
```

#### <a name="prefixes"></a> Prefijos

Usa la propiedad `prefix` para agregar prefijos a las rutas:

```php
// La URL será 'admin/hello/world' en lugar de 'hello/world'
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

#### <a name="named-routes"></a> Rutas con nombre

Puedes (y, de hecho, es recomendable) asignar un nombre a tus rutas. Esto te permitirá llamarlas desde otros lugares:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

Para obtener una ruta por su nombre usa la función `route()`, cuyo el primer argumento es el nombre de la ruta y un segundo argumento opcional es un arreglo con los valores de los parámetros de dicha ruta. Por ejemplo, para obtener la ruta anterior, basta con escribir `route('about_us')`:

```php
// http://example.com/company/about_us
<a href="<?= route('about_us');?>">My link!</a>
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Nombres duplicados</strong>
    <br />
    No puedes llamar a dos o más rutas con el mismo nombre
</div>

#### <a name="groups"></a> Grupos

Puedes crear grupos de rutas usando el método `group()`, donde el primer argumento es el prefijo que tendrán en comun, y el segundo argumento es una funcion anónima con las sub-rutas:

```php
Route::group('prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

Además, es posible asignar propiedades en común para los grupos de rutas. Este es un ejemplo de la sintaxis extendida:

```php
Route::group('prefix', ['namespace' => 'foo', 'middleware' => ['Admin','IPFilter']], function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

#### <a name="resource-routes"></a> Rutas de recurso

Las rutas de recurso permiten definir operaciones de CRUD (**C**reate, **R**ead, **U**pdate, **D**elete) para un controlador en una sola línea. Ejemplo:

```php
Route::resource('photos','PhotosController');
```

Produce:

```php
[Name]                 [Path]               [Verb]          [Controller action]
photos.index           photos               GET             PhotosController@index
photos.create          photos/create        GET             PhotosController@create
photos.store           photos               POST            PhotosController@store
photos.show            photos/{id}          GET             PhotosController@show
photos.edit            photos/{id}/edit     GET             PhotosController@edit
photos.update          photos/{id}          PUT, PATCH      PhotosController@update
photos.destroy         photos/{id}          DELETE          PhotosController@destroy
```

Además, es posible crear rutas de recurso parciales, pasando un tercer argumento con un arreglo de las acciones a filtrar:

```php
Route::resource('photos','PhotosController', ['index','edit','update']);
```

Produce:

```php
[Name]                 [Path]               [Verb]          [Controller action]
photos.index           photos               GET             PhotosController@index
photos.edit            photos/{id}/edit     GET             PhotosController@edit
photos.update          photos/{id}          PUT, PATCH      PhotosController@update
```

#### <a name="default-controller"></a> Controlador por defecto

Luthier CI establece automáticamente cualquier ruta definida con la URL `/` y el verbo HTTP **GET** como el controlador por defecto, sin embargo puedes establecerlo explícitamente mediante el método `set()` y esta sintaxis especial:

```php
// Nota que el valor está enlazado a la ruta especial 'default_controller' de CodeIgniter y debe
// usarse la sintaxis nativa:
Route::set('default_controller', 'welcome/index');
```

#### <a name="callbacks-as-routes"></a> Funciones anónimas como rutas

Puedes usar funciones anónimas (también llamadas _closures_ o _funciones lambda_) en lugar de apuntar a un controlador, por ejemplo:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});
```
Para acceder a la instancia del framework dentro de las funciones anónimas, usa la función `ci()`.

### <a name="parameters"></a> Parámetros

Es posible definir parámetros en tus rutas, de modo que puedan ser dinámicas. Para añadir un parámetro a un segmento de la ruta, enciérralo entre `{llaves}`

```php
Route::post('blog/{slug}', 'blog@post');
```

<div class="alert alert-warning">
    <i class="fa fa-warning" aria-hidden="true"></i>
    <strong>Parámetros duplicados</strong>
    <br />
    No puedes llamar a dos o más parámetros con el mismo nombre
</div>

#### <a name="optional-parameters"></a> Parámetros opcionales

Para hacer un parámetro opcional, agrega un `?` antes de cerrar las llaves:

```php
Route::put('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');
```

Ten en cuenta que luego del primer parámetro opcional definido, TODOS los siguientes parámetros deberán ser opcionales.

<div class="alert alert-success">
    <i class="fa fa-check" aria-hidden="true"></i>
    <strong>Rutas generadas automáticamente</strong>
    <br />
    Luthier CI generará por ti el árbol completo de rutas para todos los parámetros opcionales, así que no tienes que preocuparte por escribir más rutas además de la principal.
</div>

#### <a name="parameter-regex"></a> Expresiones regulares en parámetros

Puedes limitar un parámetro a una expresión regular:

```php
// Estos son los equivalentes de (:num) y (:any), respectivamente
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');
```
Además, puedes usar una expresión regular personalizada con la sintaxis `{([expr]):[name]}`:

```php
// Esto es equivalente a /^(es|en)$/
Route::get('main/{((es|en)):_locale}/about', 'about@index');
```

#### <a name="sticky-parameters"></a> Parámetros "adhesivos"

Es posible que necesites definir un parámetro en un grupo de rutas y que a su vez esté disponible en todas sus sub-rutas, sin tener que definirlo en los argumentos de todos los metodos en todos los controladores, lo cual es tedioso. Pensando en eso, Luthier CI ofrece los llamados **parámetros adhesivos**. Un parámetro adhesivo comienza con un guión bajo (`_`) y tiene algunas singularidades:

* No se pasa en los argumentos del método del controlador al que apunta dicha ruta.
* En todas la sub-rutas que compartan el parámetro adhesivo, valor se tomará de la URL y será suministrado automáticamente en la función `route()`, por lo que puedes omitirlo, o bien, sobreescribirlo por cualquier otro valor.

Considera este ejemplo:

```php
Route::group('shop/{_locale}', function()
{
    Route::get('category/{id}', 'ShopCategory@categoryList')->name('shop.category');
    Route::get('product/{id}/details', 'ShopProduct@details')->name('shop.product.details');
});
```

Las rutas `shop.category` y `shop.product.details` comparten el parámetro adhesivo `_locale`. Mientras que es requerido que dicho parámetro esté en la URL, no es obligatorio que esté presente en el arreglo de parámetros cuando uses la función `route()` en este contexto. Esto es especialmente útil cuando necesites enlazar a otras variantes de la ruta actual:

```php
// Si la URL es 'shop/en/category/1', {_locale} será 'en' aquí:
echo route('shop.category', ['id' => 1]); # shop/en/category/1
echo route('shop.category', ['id' => 2]); # shop/en/category/2
echo route('shop.category', ['id' => 3]); # shop/en/category/3

// Puedes sobreescribir ese valor por cualquier otro:
echo route('shop.category', ['_locale' => 'es', 'id' => 1]); # shop/es/category/1
```

Una ventaja de los parámetros adhesivos es que no tienes que definirlos como argumentos de todos los métodos de los controladores apuntados. En el ejemplo anterior, dentro de los controladores `ShopCategory` y `ShopProduct`, sus métodos tendrán un único argumento: `$id`, debido a que es el único suministrado por el enrutador:

```php
<?php
# application/controllers/ShopCategory.php

defined('BASEPATH') OR exit('No direct script access allowed');

class ShopCategory extends CI_Controller
{
    // Definir el método como categoryList($_locale, $id) no va a funcionar: se está
    // esperando exactamente 1 argumento:
    public function categoryList($id)
    {

    }
}
```

```php
<?php
# application/controllers/ShopProduct.php

defined('BASEPATH') OR exit('No direct script access allowed');

class ShopProduct extends CI_Controller
{
    // Lo mismo aquí:
    public function details($id)
    {

    }
}
```

Para obtener el valor de un parámetro adhesivo usa al método `param()` de la propiedad `route` dentro del controlador:

```php
<?php
# application/controllers/ShopCategory.php

defined('BASEPATH') OR exit('No direct script access allowed');

class ShopCategory extends CI_Controller
{
    public function categoryList($id)
    {
        $locale = $this->route->param('_locale');
    }
}
```