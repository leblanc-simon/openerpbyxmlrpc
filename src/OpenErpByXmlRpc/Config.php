<?php

namespace OpenErpByXmlRpc;

/**
 * Class to manipulate configuration
 *
 * @package OpenErpByXmlRpc
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Config
{
    static private $datas = array();

    /**
     * Get a value in the configuration
     *
     * @param   string  $name       The name of the configuration to get
     * @param   mixed   $default    The default value if the configuration doesn't exist
     * @return  mixed               The value of the configuration
     */
    static public function get($name, $default = null)
    {
        return (isset(static::$datas[$name]) === true) ? static::$datas[$name] : $default;
    }

    /**
     * Add values in the configuration
     *
     * @param   array   $datas  The values to add in the configuration
     */
    static public function add(array $datas = array())
    {
        static::$datas = array_merge(static::$datas, $datas);
    }
}