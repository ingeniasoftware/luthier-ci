# Rutas

La creación de rutas es una tarea fundamental durante el desarrollo de cualquier aplicación web. Luthier CI mejora  el enrutamiento de CodeIgniter para que construir aplicaciones grandes no sea excesivamente complicado.

<!-- %index% -->

### Diferencias entre el enrutamiento de CodeIgniter y Luthier CI

La forma en que las rutas son manejadas por CodeIgniter es modificada por Luthier CI durante su ejecución:

* En CodeIgniter, por defecto, las rutas son accesibles a través de cualquier verbo HTTP. Con Luthier CI es obligatorio definir en cada ruta los verbos HTTP aceptados.
* En CodeIgniter es posible acceder a los controladores sin necesidad de definir rutas, mientras que con Luthier CI únicamente las rutas definidas son detectadas.
* Con Luthier CI cada ruta es una entidad independiente y única, con parámetros bien definidos y con la capacidad de construir URLs a partir de ellas.
* Con Luthier CI es posible utilizar funciones anónimas como controladores e incluso construir una aplicación web completa sin usar ni un solo controlador.

### Tipos de rutas

Tres tipos de rutas están disponibles en Luthier CI:

* **Rutas HTTP**: se acceden bajo peticiones HTTP y se definen en el archivo `application/routes/web.php`
* **Rutas AJAX**: se acceden únicamente bajo peticiones AJAX y se definen en el archivo `application/routes/api.php`
* **Rutas CLI**: se acceden únicamente bajo un entorno CLI (Command Line Interface) y se definen en el archivo `application/routes/cli.php`

<div class="alert alert-success">
    A pesar de que puedes definir rutas AJAX en el archivo <code>web.php</code>, lo ideal es hacerlo en <code>api.php</code>
</div>

### Sintaxis

Si has usado Laravel entonces sabrás cómo escribir rutas en Luthier CI, pues su sintaxis muy parecida. Este es un ejemplo de una ruta de Luthier CI:

```php
Route::get('foo', 'bar@baz');
```

Donde:
* **foo** es la URL de la ruta, y
*  **bar@baz** es el nombre del controlador y método al que hace referencia, separados por **@**. 

El método `Route::get()` establece que la ruta acepta únicamente peticiones `GET`.

<div class="alert alert-warning">
    Si defines dos o más rutas con la misma URL y el mismo verbo HTTP siempre será usada la primera.
</div>

Se pueden definir rutas para los verbos GET, POST, PUT, PATCH, DELETE, HEAD, OPTIONS y TRACE usando los siguientes métodos de la clase `Route`: 

```php
Route::post('foo', 'bar@baz');
Route::put('foo', 'bar@baz');
Route::patch('foo', 'bar@baz');
Route::delete('foo', 'bar@baz');
Route::head('foo', 'bar@baz');
Route::options('foo', 'bar@baz');
Route::trace('foo', 'bar@baz');
```

Puedes pasar un arreglo con las _propiedades_ de la ruta como tercer argumento:

```php
Route::get('test', 'controller@method', ['prefix' => '...', 'namespace' => '...', (...)] );
```

También es posible aceptar múltiples verbos HTTP en una ruta, usando el método `Route::match()`:

```php
Route::match(['GET', 'POST'], 'path', 'controller@method', [ (...) ]);
```

#### Espacios de nombre

La propiedad **namespace** le indica a CodeIgniter el sub-directorio donde se encuentra el controlador.

```php
// El controlador apuntará a application/controllers/foo/Bar.php
Route::get('hello/world', 'bar@index', ['namespace' => 'admin']);
```

<div class="alert alert-info">
    Nota que esto no es un <em>espacio de nombre</em> de PHP, sino un nombre de directorio.
</div>

#### Prefijos

Usa la propiedad **prefix** para agregar prefijos a las rutas:

```php
// La URL será 'admin/hello/world'
Route::get('hello/world', 'testcontroller@index', ['prefix' => 'admin']);
```

#### Rutas con nombre

Es recomendable asignar un nombre a tus rutas, esto te permitirá construir URLs en tus vistas y en otros controladores:

```php
Route::get('company/about_us', 'testcontroller@index')->name('about_us');
```

Para obtener una ruta por su nombre usa la función `route($name)`, donde `$name` es el nombre de la ruta:

```php
route('about_us');
```

<div class="alert alert-warning">
    Declarar dos o más rutas con el mismo nombre producirá una excepción
</div>

#### Grupos

Usa el método `Route::group($prefix, $routes)` para definir un grupo de rutas, donde `$prefix` es el prefijo en común y `$routes` una función anónima con que contiene las sub-rutas:

```php
Route::group('my_prefix', function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

Es posible asignar **propiedades** para todas las rutas del grupo usando la sintaxis `Route::group($prefix, $properties, $routes)`:

```php
Route::group('prefix', ['namespace' => 'foo', 'middleware' => ['Admin','IPFilter']], function(){
    Route::get('bar','test@bar');
    Route::get('baz','test@baz');
});
```

#### Rutas de recurso

Las rutas de recurso son un atajo para crear el enrutamiento de operaciones CRUD (**C**reate, **R**ead, **U**pdate, **D**elete) para un controlador. 

Para crer una ruta de recurso usa el método `Route::resource($name, $controller)`, donde `$name` es el nombre/prefijo de las rutas y `$controller` el nombre del controlador:

```php
Route::resource('photos','PhotosController');
```

Resultado:

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

Es posible crear rutas de recurso parciales usando la sintaxis `Route::resource($name, $controller, $include)`, donde `$include` es un arreglo (incluyente) de las rutas a crear:

```php
Route::resource('photos','PhotosController', ['index','edit','update']);
```

Resultado:

```php
[Name]                 [Path]               [Verb]          [Controller action]
photos.index           photos               GET             PhotosController@index
photos.edit            photos/{id}/edit     GET             PhotosController@edit
photos.update          photos/{id}          PUT, PATCH      PhotosController@update
```

#### Controlador por defecto

Luthier CI establece automáticamente cualquier ruta definida con la URL `/` y el verbo HTTP **GET** como el controlador por defecto.

Puedes definir de forma explícita el controlador por defecto usando el método `Route::set('default_controller', $name)`, donde `$name` es el controlador por defecto:


```php
Route::set('default_controller', 'welcome/index');
```

#### Funciones anónimas como rutas

No es necesario suministrar un nombre de un controlador y un método para definir una ruta en Luthier CI. También puedes usar funciones anónimas (o _closures_) como controladores:

```php
Route::get('foo', function(){
    ci()->load->view('some_view');
});
```

<div class="alert alert-info">
    Para acceder a la instancia (singleton) del CodeIgniter dentro de las funciones anónimas, usa el helper <code>ci()</code>.
</div>

### Parámetros de rutas

Los parámetros son secciones dinámicas de la URL de una ruta, haciendo posible que múltiples URLs resuelvan a la misma ruta. Para definir parámetros, enciérralos entre `{llaves}`, por ejemplo:

```php
Route::post('blog/{slug}', 'blog@post');
```

<div class="alert alert-warning">
    No puedes definir dos o más parámetros con el mismo nombre
</div>

#### Parámetros opcionales

Para establecer un parámetro como opcional, agrega un `?` antes de cerrar las llaves:

```php
Route::put('categories/{primary?}/{secondary?}/{filter?}', 'clients@list');
```

Ten en cuenta que, tal como sucede con los argumentos de funciones en PHP, luego del primer parámetro definido como opcional TODOS los demás deberán ser opcionales también.

<div class="alert alert-success">
    Luthier CI generará por ti el árbol completo de rutas para todos los parámetros opcionales, así que no tienes que preocuparte por escribir más rutas además de la principal.
</div>

#### Expresiones regulares en parámetros

Puedes limitar el contenido de un parámetro de ruta para restringirlo a un set de caracteres en específico:

```php
Route::get('cars/{num:id}/{any:registration}', 'CarCatalog@index');
```

Los placeholders `num:` y `any:` son equivalentes a `(:num)` y `(:any)`, respectivamente.

También es posible usar una expresión regular para definir parámetros de ruta:

```php
Route::get('main/{((es|en)):_locale}/about', 'about@index');
```

Lo anterior es equivalente a `/^(es|en)$/`.

#### Parámetros adhesivos

Cuando trabajas con grupos de rutas que definen parámetros éstos deben ser declarados como argumentos en los métodos de los controladores, *recursivamente*. Dependiendo de la complejidad de tu aplicación, los parámetros heredados se irán acumulando, lo que hará que los métodos de tus controldores tengan una cantidad muy grande de argumentos. 

Los **parámetros adhesivos** sirven precisamente para ayudarte a lidiar con éste problema. 

Un parámetro adhesivo es cualquier parámetro de ruta que comience con un guión bajo (`_`). Tienen las siguientes propiedades:

* No es necesario definirlo en los argumentos de los métodos de los controladores en las sub-rutas.
* El valor del parámetro se tomará de la URL y será suministrado automáticamente en la función `route()`, por lo que puede ser omitido, o sobreescribirlo por cualquier otro valor.

Considera el siguiente grupo de rutas:

```php
Route::group('shop/{_locale}', function()
{
    Route::get('category/{id}', 'ShopCategory@categoryList')->name('shop.category');
    Route::get('product/{id}/details', 'ShopProduct@details')->name('shop.product.details');
});
```

Las rutas `shop.category` y `shop.product.details` comparten el parámetro adhesivo `_locale` y, mientras que sigue siendo requerido que dicho parámetro esté en la URL, puedes omitirlo cuando construyas rutas dentro de éste grupo:

```php
// Si la URL es 'shop/en/category/1', {_locale} será 'en' aquí:

echo route('shop.category', ['id' => 1]);
# shop/en/category/1

echo route('shop.category', ['id' => 2]); 
# shop/en/category/2

echo route('shop.category', ['id' => 3]); 
# shop/en/category/3
```

Esto es útil cuando necesites enlazar a otras variantes de la ruta actual:

```php
echo route('shop.category', ['_locale' => 'es', 'id' => 1]); 
# shop/es/category/1
```

Dentro de los controladores `ShopCategory` y `ShopProduct`, sus métodos tendrán un único argumento: `$id`:

```php
# application/controllers/ShopCategory.php
class ShopCategory extends CI_Controller
{
    public function categoryList($id)
    {
        // (...)
    }
}

# application/controllers/ShopProduct.php
class ShopProduct extends CI_Controller
{
    public function details($id)
    {
        // (...)
    }
}
```

Para obtener el valor de un parámetro adhesivo dentro de un controlador usa al método `param($name)` de la propiedad `route`, donde `$name` es el nombre del parámetro:

```php
public function categoryList($id)
{
    $locale = $this->route->param('_locale');
}
```