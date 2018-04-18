<?php

/**
 * Cli utilities
 *
 * (Experimental)
 *
 * @author Anderson Salas <anderson@ingenia.me>
 * @license MIT
 */

namespace Luthier;

class Cli
{

    /**
     * Defines all maker cli commands
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function maker()
    {
        if(ENVIRONMENT !== 'development')
        {
            return;
        }

        Route::group('luthier', function(){

            Route::group('make', function(){

                Route::cli('controller/{(.+):name}',function($name){
                    self::makeContoller($name);
                });
                Route::cli('model/{(.+):name}',function($name){
                    self::makeModel($name);
                });

            });

        });
    }


    /**
     * Recursive mkdir helper
     *
     * @param  string   $dir path
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function recursiveMkdir($dir)
    {
        $target = APPPATH . 'controllers';
        foreach($dir as $_path)
        {
            $target .= '/' . $_path;
            if(!file_exists($target))
            {
                mkdir($target);
            }
        }
    }


    /**
     * Creates a controller
     *
     * @param  string  $name controller name
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function makeContoller($name)
    {
        $path = APPPATH . 'controllers/' . $name . '.php';
        $dir  = explode('/', $name);
        $name = array_pop($dir);

        if(!empty($dir))
        {
            self::recursiveMkdir($dir);
        }

        if(file_exists($path))
        {
            show_error('The file already exists!');
        }

        $file = <<<CONTROLLER
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class $name extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Controller index
     *
     * @return void
     *
     * @access public
     */
    public function index()
    {

    }
}
CONTROLLER;

        file_put_contents($path, $file);
    }

    private static function makeModel($name)
    {
        $path = APPPATH . 'models/' . $name . '.php';
        $dir  = explode('/', $name);
        $name = array_pop($dir);

        if(!empty($dir))
        {
            self::recursiveMkdir($dir);
        }

        if(file_exists($path))
        {
            show_error('The file already exists!');
        }

        $file = <<<MODEL
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class $name extends CI_Model
{
    // ...
}
MODEL;

        file_put_contents($path, $file);
    }
}