# Examples

### Example #1: Multi-language website

This example shows the management of a multi-language website through the URL. Use a middleware to load the current language file.

```php
<?php
# application/routes/web.php

Route::get('/', function(){

    // "Default" path. This is a good place to request a cookie, session variable or something
    // that allows us to restore the user's last language, or display a language selection screen 
    // if no information is provided.

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
        Obtaining the value of the "_locale" sticky parameter 
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