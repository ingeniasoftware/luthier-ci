[//]: # ([author] Anderson Salas, translated by Julio Cedeño)
[//]: # ([meta_description] Look Luthier-CI in action! Here we compile some real examples of use so you can find inspiration)

# Examples

### Example # 1: Multi-language website

This is an example shows a multi-language website managed by the URL.
A middleware is used to load the current language file.

```php
<?php
# application/routes/web.php

Route::get('/', function(){

    // Route "by default". This is a good place to request a cookie, session variable
    // or something that allows us to restore the last language of the user, or show a
    // language selection screen if no information is provided.

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
        // Obtaining the value of the "_locale" sticky parameter
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