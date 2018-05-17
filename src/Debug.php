<?php


namespace Luthier;

use DebugBar\StandardDebugBar as DebugBar;
use Luthier\RouteBuilder as Route;

class Debug
{
    private static $debugBar;

    public static function getDebugBar()
    {
        if(self::$debugBar === null)
        {
            self::$debugBar = new DebugBar();
        }

        return self::$debugBar;
    }

    public static function getDebugBarRoutes()
    {
        Route::get('_debug_gbar/css', function(){

            ob_start();
            Debug::getDebugBar()->getJavascriptRenderer()->dumpCssAssets();

            //
            // Some CSS tweaks
            //

            echo "
                div.phpdebugbar-header, a.phpdebugbar-restore-btn
                {
                    background-color: white;
                    background-position-y: 6px;
                }
                a.phpdebugbar-restore-btn
                {
                    height: 21px;
                }
                div.phpdebugbar-header > div > *
                {
                    padding: 8px 8px;
                }
                div.phpdebugbar-resize-handle
                {
                    border-bottom: 2px solid black;
                }
                a.phpdebugbar-minimize-btn, a.phpdebugbar-close-btn, a.phpdebugbar-maximize-btn
                {
                    margin: 3px 0px 0px 5px;
                    padding-bottom: 0px !important;
                }
                div.phpdebugbar-minimized
                {
                    border-top: 2px solid black;
                }
                a.phpdebugbar-tab span.phpdebugbar-badge
                {
                    background: white;
                    color: #ff5722;
                    font-weight: bold;
                    font-size: 12px;
                    vertical-align: unset;
                    border-radius: 42%;
                    padding: 2px 7px;
                }
                a.phpdebugbar-tab.phpdebugbar-active
                {
                    background-image: none;
                    background: #FF5722;
                    color: white !important;
                }
                div.phpdebugbar-mini-design a.phpdebugbar-tab
                {
                    border-right: none;
                    min-height: 16px;
                }
            ";
            $css = ob_get_clean();
            $css = str_ireplace("url('../fonts/fontawesome-webfont", "url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/fonts/fontawesome-webfont", $css);

            ci()->output
                ->set_content_type('text/css')
                ->set_output($css);

        })->name('debug_bar.css_assets');

        Route::get('_debug_gbar/js', function(){

            ob_start();
            Debug::getDebugBar()->getJavascriptRenderer()->dumpJsAssets();
            $js = ob_get_clean();

            ci()->output
                ->set_content_type('text/javascript')
                ->set_output($js);

        })->name('debug_bar.js_assets');
    }
}