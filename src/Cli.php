<?php

/**
 * Cli utilities
 *
 * Due security reasons, this commands aren't available in 'production' or 'testing'
 * environment.
 *
 * @author Anderson Salas <anderson@ingenia.me>
 * @license MIT
 */

namespace Luthier;
use Luthier\RouteBuilder as Route;

class Cli
{

    /**
     * Register all maker cli commands
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

                Route::cli('helper/{(.+):name}',function($name){
                    self::makeHelper($name);
                });

                Route::cli('library/{(.+):name}',function($name){
                    self::makeLibrary($name);
                });

                Route::cli('middleware/{(.+):name}',function($name){
                    self::makeMiddleware($name);
                });

                Route::cli('migration/{name}/{((sequential|timestamp)):type?}',function($name, $type = 'timestamp'){
                    self::makeMigration($name, $type);
                });

                Route::cli('auth', function(){
                    self::makeAuth();
                });

            });

        });
    }


    /**
     * Register all migrations cli commands
     *
     * @return void
     *
     * @access public
     * @static
     */
    public static function migrations()
    {
        if(ENVIRONMENT !== 'development')
        {
            return;
        }

        Route::group('luthier', function(){

            Route::group('migrate', function(){

                Route::cli('{version?}',function($version = null){
                    self::migrate($version);
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
    private static function rmkdir($folders, $base)
    {
        $target = APPPATH . $base;

        foreach($folders as $folder)
        {
            $target .= '/' . $folder;

            if(!file_exists($target))
            {
                mkdir($target);
            }
        }
    }

    private static function rcopy($source, $target)
    {
        foreach(scandir($source) as $res)
        {
            if($res == '.' || $res == '..')
            {
                continue;
            }

            $_source = $source . '/' . $res;
            $_target = $target . '/' . $res;

            if(is_dir($_source))
            {
                if(!file_exists($_target))
                {
                    mkdir($_target);
                }

                self::rcopy($_source, $_target);
            }
            else
            {
                if(!file_exists($_target))
                {
                    copy($_source, $_target);
                    echo "CREATED: $_target\n";
                }
                else
                {
                    echo "WARNING: $_target file already exists, skipping!\n";
                }
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
        $dir = [];

        if(count(explode('/', $name)) > 0)
        {
            $dir  = explode('/', $name);
            $name = array_pop($dir);
        }

        $name = ucfirst($name);
        $path = APPPATH . 'controllers/' . ( empty($dir) ? $name : implode('/', $dir) . '/' . $name ) . '.php';

        if(!empty($dir))
        {
            self::rmkdir($dir,'controllers');
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

        echo "\nCREATED:\n" . realpath($path) . "\n";
    }


    /**
     * Creates a model
     *
     * @param  string   $name model name
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function makeModel($name)
    {
        $dir = [];

        if(count(explode('/', $name)) > 0)
        {
            $dir  = explode('/', $name);
            $name = array_pop($dir);
        }

        $name = ucfirst($name) . '_model';
        $path = APPPATH . 'models/' . ( empty($dir) ? $name : implode('/', $dir) . '/' . $name ) . '.php';

        if(!empty($dir))
        {
            self::rmkdir($dir,'models');
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

        echo "\nCREATED:\n" . realpath($path) . "\n";
    }


    /**
     * Creates a helper
     *
     * @param  string  $name helper name
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function makeHelper($name)
    {
        $dir = [];

        if(count(explode('/', $name)) > 0)
        {
            $dir  = explode('/', $name);
            $name = array_pop($dir);
        }

        $name = ucfirst($name) . '_helper';
        $path = APPPATH . 'helpers/' . ( empty($dir) ? $name : implode('/', $dir) . '/' . $name ) . '.php';

        if(!empty($dir))
        {
            self::rmkdir($dir,'helpers');
        }

        if(file_exists($path))
        {
            show_error('The file already exists!');
        }

        $file = <<<HELPER
<?php

defined('BASEPATH') OR exit('No direct script access allowed');


HELPER;

        file_put_contents($path, $file);

        echo "\nCREATED:\n" . realpath($path) . "\n";
    }


    private static function makeMiddleware($name)
    {
        $dir = [];

        if(count(explode('/', $name)) > 0)
        {
            $dir  = explode('/', $name);
            $name = array_pop($dir);
        }

        $name = ucfirst($name) . '_middleware';
        $path = APPPATH . 'middleware/' . ( empty($dir) ? $name : implode('/', $dir) . '/' . $name ) . '.php';

        if(!empty($dir))
        {
            self::rmkdir($dir,'middleware');
        }

        if(file_exists($path))
        {
            show_error('The file already exists!');
        }

        $file = <<<MIDDLEWARE
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class $name
{

    /**
     * Middleware entry point
     *
     * @return void
     */
    public function run()
    {

    }
}
MIDDLEWARE;

        file_put_contents($path, $file);

        echo "\nCREATED:\n" . realpath($path) . "\n";
    }


    /**
     * Creates a library
     *
     * @param  string   $name library name
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function makeLibrary($name)
    {
        $dir = [];

        if(count(explode('/', $name)) > 0)
        {
            $dir  = explode('/', $name);
            $name = array_pop($dir);
        }

        $name = ucfirst($name) ;
        $path = APPPATH . 'libraries/' . ( empty($dir) ? $name : implode('/', $dir) . '/' . $name ) . '.php';

        if(!empty($dir))
        {
            self::rmkdir($dir,'libraries');
        }

        if(file_exists($path))
        {
            show_error('The file already exists!');
        }

        $file = <<<LIBRARY
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class $name
{
    // ...
}
LIBRARY;

        file_put_contents($path, $file);

        echo "\nCREATED:\n" . realpath($path) . "\n";
    }


    /**
     * Creates a migration
     *
     * @param  string  $name migration name
     * @param  string  $type migration type (sequential|date)
     *
     * @return void
     *
     * @access private
     * @static
     */
    private static function makeMigration($name, $type)
    {
        if(!file_exists(APPPATH . '/migrations'))
        {
            mkdir(APPPATH . '/migrations');
        }

        $name = trim(str_ireplace(' ', '_', $name));

        if($type == 'timestamp')
        {
            $filename = date('Y') . date('m') . date('d') . date('H') . date('i') . date('s') . '_' . $name;
        }
        else
        {
            $migrations = scandir(APPPATH . '/migrations');
            $last = 0;

            foreach($migrations as $migration)
            {
                if($migration == '.' || $migration == '..')
                {
                    continue;
                }

                $_number = substr($migration,0,4);

                if(preg_match('/^[0-9]{3}_$/',$_number))
                {
                    if($_number > $last)
                    {
                        $last = (int) $_number;
                    }
                }
            }
            $last++;
            $filename = str_pad($last, 3, '0', STR_PAD_LEFT) . '_' . $name;
        }

        $path = APPPATH . 'migrations/' . $filename . '.php';

        if(file_exists($path))
        {
            show_error('The file already exists!');
        }

        $file = <<<MIGRATION
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_{$name} extends CI_Migration
{
    public function up()
    {

    }

    public function down()
    {

    }
}
MIGRATION;

        file_put_contents($path, $file);

        echo "\nCREATED:\n" . realpath($path) . "\n";
    }

    
    private static function makeAuth()
    {
        self::rcopy(__DIR__ . '/Resources/Auth', APPPATH);
    }


    /**
     * Runs a migration
     *
     * @param  string  $version (Optional)
     *
     * @return void
     *
     * @access private
     */
    private static function migrate($version = null)
    {
        if($version == 'reverse')
        {
            self::migrate('0');
            return;
        }

        if($version == 'refresh')
        {
            self::migrate('0');
            self::migrate();
            return;
        }

        ci()->load->library('migration');

        $migrations = ci()->migration->find_migrations();

        $_migrationsTable = new \ReflectionProperty('CI_Migration', '_migration_table');
        $_migrationsTable->setAccessible(true);
        $_migrationsTable = $_migrationsTable->getValue(ci()->migration);

        $old = ci()->db->get($_migrationsTable)->result()[0]->version;

        $migrate = function() use($version)
        {
            if($version === null)
            {
                return ci()->migration->latest();
            }

            return ci()->migration->version($version);
        };

        $result = $migrate();

        if($result === FALSE)
        {
            show_error(ci()->migration->error_string());
        }

        $current = ci()->db->get($_migrationsTable)->result()[0]->version;

        echo "\n";

        if($old == $current)
        {
            echo "Nothing to migrate. \n";
        }
        else
        {
            $migrated   = [];
            $index      = 0;
            $migrations = $old < $current ? $migrations : array_reverse($migrations, true);
            $ascendent  = $old < $current;

            foreach($migrations as $name => $path)
            {
                if($ascendent)
                {
                    if( $current >=  $name)
                    {
                        echo 'MIGRATED: ' . basename($migrations[$name]) . "\n";
                    }
                }
                else
                {
                    if( $current <= $name)
                    {
                        echo 'REVERSED: ' . basename($migrations[$name]) . "\n";
                    }
                }
            }
        }
    }
}