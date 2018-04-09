<?php

/**
 * Welcome to Luthier-CI!
 *
 * This is your main route file. Put all your HTTP-Based routes here using the static
 * Route class methods
 *
 * Examples:
 *
 *    Route::get('foo', 'bar@baz');
 *      -> $route['foo']['GET'] = 'bar/baz';
 *
 *    Route::post('bar', 'baz@fobie', [ 'namespace' => 'cats' ]);
 *      -> $route['bar']['POST'] = 'cats/baz/foobie';
 *
 *    Route::get('blog/{slug}', 'blog@post');
 *      -> $route['blog/(:any)'] = 'blog/post/$1'
 */


Route::get('/', function(){

    // In fact, THIS is your 'default_controller' route, because is defined as a GET route
    // under the root '/' path, so Luthier set as it for you

    ci()->load->view('welcome_message');
});


Route::set('404_override', function(){
    show_404();
});

Route::set('translate_uri_dashes',FALSE);