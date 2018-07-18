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

/**
 * Route segment parameter
 * 
 * @author Anderson Salas <anderson@ingenia.me>
 */
class RouteParam
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $regex;

    /**
     * @var string
     */
    private $placeholder;

    /**
     * @var bool
     */
    private $optional;

    /**
     * @var string
     */
    private $segment;

    /**
     * @var string
     */
    public $value;

    /**
     * Luthier CI placeholder -> CodeIgniter placeholder conversion
     *
     * @var string[]
     */
    private static $placeholderPatterns = [
        '{num:[a-zA-Z0-9-_]*(\?}|})'  => '(:num)', # (:num) route
        '{any:[a-zA-Z0-9-_]*(\?}|})'  => '(:any)', # (:any) route
        '{[a-zA-Z0-9-_]*(\?}|})'      => '(:any)', # Everything else
    ];

    /**
     * CodeIgniter placeholder -> regex conversaion
     *
     * @var string[]
     */
    private static $placeholderReplacements = [
        '/\(:any\)/'  => '[^/]+',
        '/\(:num\)/'  => '[0-9]+',
    ];

    /**
     * Gets Luthier CI -> CodeIgniter placeholder conversion array
     *
     * @return string[]
     */
    public static function getPlaceholderReplacements()
    {
        return self::$placeholderReplacements;
    }

    /**
     * @param string $segment Original route segment
     */
    public function __construct($segment)
    {
        $this->segment = $segment;
        $customRegex = false;

        $matches = [];

        if(preg_match('/{\((.*)\):[a-zA-Z0-9-_]*(\?}|})/', $segment, $matches))
        {
            $this->placeholder = '(' . $matches[1] . ')';
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
     * Gets parameter name
     * 
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets original segment
     *
     * @return string
     */
    public function getSegment()
    {
        return $this->segment;
    }

    /**
     * Gets segment regex
     *
     * @return string
     */
    public function getRegex()
    {
        return $this->regex;
    }

    /**
     * Gets segment placeholder
     *
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * Checks if a segment is optional or not
     *
     * @return bool
     */
    public function isOptional()
    {
        return $this->optional;
    }
}