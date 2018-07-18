<?php

/*
 * Luthier CI
 *
 * (c) 2018 Ingenia Software C.A
 *
 * This file is part of Luthier CI, a plugin for CodeIgniter 3. See the LICENSE
 * file for copyright information and license details
 */

namespace Luthier;

use DebugBar\StandardDebugBar as DebugBar;

/**
 * Wrapper of PHP Debug integration for Luthier CI
 *  
 * @author Anderson Salas <anderson@ingenia.me>
 */
class Debug
{
    /**
     * @var \DebugBar\StandardDebugBar 
     */
    private static $debugBar;

    /**
     * Creates an instance of PHP DebugBar
     *
     * @return void
     */
    public static function init()
    {
        if(ENVIRONMENT == 'production')
        {
            return;
        }

        self::$debugBar = new DebugBar();
        self::setDebugBarRoutes();
    }

    /**
     * Gets the current PHP Debug Bar instance
     *
     * @return \DebugBar\StandardDebugBar 
     */
    public static function getDebugBar()
    {
        return self::$debugBar;
    }

    /**
     * Sets PHP Debug Bar assets routes
     *
     * @return void
     */
    private static function setDebugBarRoutes()
    {
        RouteBuilder::any('_debug_bar/css', function(){

            ob_start();
            Debug::getDebugBar()->getJavascriptRenderer()->dumpCssAssets();

            // CSS tweaks
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
                ->set_output($css)
                ->_display();

            exit;

        })->name('debug_bar.css_assets');

        RouteBuilder::any('_debug_bar/js', function(){

            ob_start();
            Debug::getDebugBar()->getJavascriptRenderer()->dumpJsAssets();
            $js = ob_get_clean();

            ci()->output
                ->set_content_type('text/javascript')
                ->set_output($js)
                ->_display();

            exit;

        })->name('debug_bar.js_assets');
    }

    /**
     * Injects the PHP Debug Bar required assets to the HTML output returned by the 
     * CI_Output::get_output() method
     *
     * @param  mixed $output
     *
     * @return void
     */
    public static function prepareOutput(&$output)
    {
        if(ENVIRONMENT != 'production' && !ci()->input->is_ajax_request() && !is_cli() && self::$debugBar !== null)
        {
            $head  = "<script>
                function asset_retry(node, type)
                {
                    if(type == 'css')
                    {
                        node.href = node.href + '?retry=' + (Math.random() * 100);
                    }
                    else
                    {
                        node.src = node.href + '?retry=' + (Math.random() * 100);
                    }
                    phpdebugbar.restoreState();
                }
            </script>" ;
            $head .= '<link rel="stylesheet" href="'. route('debug_bar.css_assets') .'" onerror="asset_retry(this, \'css\')" />';

            $body  = '<script src="'. route('debug_bar.js_assets') .'" onerror="asset_retry(this, \'js\')"></script>';
            $body .=  Debug::getDebugBar()->getJavascriptRenderer()->render();

            $output = str_ireplace('</head>', $head . '</head>', $output);
            $output = str_ireplace('</body>', $body . '</body>', $output);
        }
    }

    /**
     * Logs a message in a PHP Debug Bar data collector
     * 
     * @param string $message   Content
     * @param string $type      Type 
     * @param string $collector Collector name ('messages' by default)
     */
    public static function log($message, $type = 'info', $collector = 'messages')
    {
        if(ENVIRONMENT == 'production' || self::$debugBar === null)
        {
            return;
        }

        if($message instanceof \Exception)
        {
            self::getDebugBar()->getCollector('exceptions')->addException($message);
            return;
        }

        self::getDebugBar()->getCollector($collector)->addMessage($message, $type, is_string($message));
    }

    /**
     * Logs a message that will be available in the next request 
     * as a session flash variable
     * 
     * @param string $message   Content
     * @param string $type      Type 
     * @param string $collector Collector name ('messages' by default)
     */
    public static function logFlash($message, $type = 'info', $collector = 'messages')
    {
        if(ENVIRONMENT == 'production' || self::$debugBar === null)
        {
            return;
        }

        $messages   = ci()->session->flashdata('_debug_bar_flash');
        $messages[] = [ $message, $type, $collector ];
        
        ci()->session->set_flashdata('_debug_bar_flash', $messages);
    }


    /**
     * Add a Data Collector to the current PHP DebugBar instance
     *
     * @param  mixed        $dataCollector
     *
     * @return mixed
     *
     * @access public
     * @static
     */
    /**
     * Adds a data collector to the PHP Debug Bar instance
     * 
     * @param \DebugBar\DataCollector\DataCollectorInterface $dataCollector
     * 
     * @return void
     */
    public static function addCollector($dataCollector)
    {
        if(ENVIRONMENT == 'production' || self::$debugBar === null)
        {
            return;
        }

        self::getDebugBar()->addCollector($dataCollector);
    }

}