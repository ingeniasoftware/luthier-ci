[//]: # ([author] Anderson Salas)
[//]: # ([meta_description] ¡Mira en acción a Luthier-CI! aquí recopilamos algunos ejemplos reales de uso para que encuentres inspiración)

# Ejemplos

### Ejemplo #1: Sitio web multi-idioma

En este ejemplo se muestra el manejo de un sitio web multi-idioma a través de la URL.
Utiliza un middleware para cargar el archivo de idioma actual.

```php
<?php
# application/routes/web.php

Route::get('/', function(){

    // Ruta "por defecto". Éste es un buen lugar para solicitar una cookie, variable de sesión
    // o algo que nos permita restaurar el último idioma del usuario, o bien mostrar una
    // pantalla de selección de idioma si no se provee ninguna información.

    redirect(route('homepage', ['_locale' => 'en']));
});

Route::group('{((es|en|it|br|ge)):_locale}', ['middleware' => ['Lang_middleware']], function(){

    Route::get('home', function(){
        var_dump( ci()->lang->line('test') );
    })->name('homepage');

    Route::get('about', function(){
        var_dump( ci()->lang->line('test') );
    })->name('about');

});
```

```php
<?php
# application/middleware/Lang_middleware.php

class Lang_middleware
{
    public function run()
    {
        // Obteniendo el valor del parámetro adhesivo "_locale"
        $locale = ci()->route->param('_locale');

        $langs = [
            'es' => 'spanish',
            'en' => 'english',
            'it' => 'italian',
            'br' => 'portuguese-brazilian',
            'ge' => 'german',
        ];

        ci()->lang->load('test', $langs[$locale]);
    }
}
```