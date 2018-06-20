<?php

/**
 * Route Parameter class
 *
 * @autor Anderson Salas <anderson@ingenia.me>
 * @licence MIT
 */

namespace Luthier;

final class RouteParam
{

    /**
     * Route parameter name
     *
     * @var $name
     *
     * @access private
     */
    private $name;


    /**
     * Actual segment regex to be matched
     *
     * @var $regex
     *
     * @access private
     */
    private $regex;


    /**
     * Route parameter placeholder in CI native format
     *
     * @var $placeholder
     *
     * @access private
     */
    private $placeholder;


    /**
     * Is the parameter optional?
     *
     * @var $optional
     *
     * @access private
     */
    private $optional;


    /**
     * Original Luthier route segment
     *
     * @var $segment
     *
     * @access private
     */
    private $segment;



    /**
     * Route param default value
     *
     * @var $value
     *
     * @access public
     */
    public $value;


    /**
     * Luthier route placeholder to CI route placeholder
     *
     * @var $patterns
     *
     * @access private
     */
    private static $placeholderPatterns = [
        '{num:[a-zA-Z0-9-_]*(\?}|})'      => '(:num)', # (:num) route
        '{any:[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # (:any) route
        '{[a-zA-Z0-9-_]*(\?}|})'          => '(:any)', # Everything else
    ];


    /**
     * CodeIgniter route placeholder to regex
     *
     * @var static $placeholderReplacements
     *
     * @access private
     */
    private static $placeholderReplacements = [
        '/\(:any\)/'  => '[^/]+',
        '/\(:num\)/'  => '[0-9]+',
    ];


    /**
     * Get the CodeIgniter route placeholder to Regex
     *
     * @return array
     *
     * @access public
     * @static
     */
    public static function getPlaceholderReplacements()
    {
        return self::$placeholderReplacements;
    }


    /**
     * Class constructor
     *
     * @param  string  $segment
     *
     * @return $this
     *
     * @access public
     */
    public function __construct($segment, $default = null)
    {
        $this->segment = $segment;
        $customRegex = false;

        $matches = [];

        if(preg_match('/{\((.*)\):[a-zA-Z0-9-_]*(\?}|})/', $segment, $matches))
        {
            $this->placeholder = $matches[1];
            $this->regex = $matches[1];
            $name = preg_replace('/\((.*)\):/', '', $segment, 1);
        }
        else
        {
            foreach(self::$placeholderPatterns as $regex => $replacement)
            {
                $parsedSegment = preg_replace('/'.$regex.'/' , $replacement, $segment);

                if($segment != $parsedSegment )
                {
                    $this->placeholder = $replacement;
                    $this->regex = preg_replace(array_keys(self::$placeholderReplacements), array_values(self::$placeholderReplacements), $replacement,1);
                    $name = preg_replace(['/num:/', '/any:/'], '', $segment, 1);
                    break;
                }
            }
        }

        $this->optional = substr($segment,-2,1) == '?';
        $this->name = substr($name,1, !$this->optional ? -1 : -2);
    }


    /**
     * Get parameter name
     *
     * @return string
     *
     * @access public
     */
    public function getName()
    {
        return $this->name;
    }


    /**
     * Get original segment
     *
     * @return string
     *
     * @access public
     */
    public function getSegment()
    {
        return $this->segment;
    }


    /**
     * Get segment regex
     *
     * @return string
     *
     * @access public
     */
    public function getRegex()
    {
        return $this->regex;
    }


    /**
     * Get segment placeholder
     *
     * @return string
     *
     * @access public
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }


    /**
     * Is the segment optional?
     *
     * @return bool
     *
     * @access public
     */
    public function isOptional()
    {
        return $this->optional;
    }
}