<?php

namespace OpenErpByXmlRpc;

use Zend\XmlRpc\Client as ZendClient;

/**
 * Class to manage logger
 *
 * @package OpenErpByXmlRpc
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Log
{
    static private $filename;

    /**
     * Log a request
     *
     * @param ZendClient $client
     * @param $type
     */
    static public function request(ZendClient $client, $type)
    {
        if (Config::get('log', false) === false) {
            return;
        }

        $params = $client->getLastRequest()->getParams();
        $login_informations = null;

        if ('common' === $type) {
            $object = '';
            $method = $client->getLastRequest()->getMethod();
            if (Config::get('log_show_pass', false) !== true) {
                $params[2] = '*****';
            }
        } elseif ('object' === $type) {
            $object = $params[3];
            $method = $params[4];
            $login_informations = array($params[0], $params[1], $params[2]);
            unset($params[0], $params[1], $params[2], $params[3], $params[4]);
        } elseif ('db' === $type) {
            $object = 'db';
            $method = 'list';
            $params = array();
        } else {
            return;
        }

        static::write(static::buildRequestString($object, $method, $login_informations, $params));
    }


    /**
     * Log a success response
     *
     * @param ZendClient $client
     */
    static public function response(ZendClient $client)
    {
        if (Config::get('log', false) === false) {
            return;
        }

        $content = 'Response :'."\n";
        $content .= static::logType($client->getLastResponse()->getReturnValue());

        static::write($content);
    }


    /**
     * Log a fault response
     *
     * @param ZendClient\Exception\FaultException $e
     */
    static public function fault(ZendClient\Exception\FaultException $e)
    {
        if (Config::get('log', false) === false) {
            return;
        }

        $content = 'Response with Fault :'."\n";
        $content .= static::logType($e->getMessage());

        static::write($content);
    }


    /**
     * Write the data into a file
     *
     * @param   string  $data   The data to write in the file
     */
    static private function write($data)
    {
        $content = "\n\n--------------------------------------\n";
        $content .= "\n--------- ".date('Y-m-d H:i:s')." --------\n";
        $content .= "\n--------------------------------------\n";
        $content .= $data;

        file_put_contents(static::getFilename(), $content, FILE_APPEND);
    }


    /**
     * Get the filename to use for logging
     *
     * @return string   the filename
     */
    static private function getFilename()
    {
        if (static::$filename === null) {
            static::$filename = Config::get('log_filename', Config::get('log_dir').DIRECTORY_SEPARATOR.'xmlrpc-'.date('Ymd').'.log');
            if (substr(static::$filename, 0, 1) !== '/') {
                static::$filename = Config::get('log_dir').DIRECTORY_SEPARATOR.static::$filename;
            }
        }

        return static::$filename;
    }


    /**
     * Build the request in string
     *
     * @param   string      $object             The object call in XML-RPC
     * @param   string      $method             The method call in XML-RPC
     * @param   null|array  $login_information  The login information
     * @param   array       $params             The parameters get in the method to call
     * @return  string
     */
    static private function buildRequestString($object, $method, $login_information, $params)
    {
        $content = 'Call: '.$object.':'.$method;
        if (null !== $login_information) {
            if (Config::get('log_show_pass', false) !== true) {
                $login_information[2] = '*****';
            }
            $content .= sprintf(' with database: %s , uid: %s , pass: %s',
                $login_information[0],
                $login_information[1],
                $login_information[2]
            );
        }
        $content .= "\n";
        $count = 0;
        foreach ($params as $param) {
            $content .= 'Arg '.(++$count).' : '.static::logType($param)."\n";
        }

        return $content;
    }


    /**
     * Convert a mixed data to string
     *
     * @param   mixed   $value  the data to convert
     * @return  string          the data in string
     */
    private static function logType($value)
    {
        if (is_array($value) === true || is_object($value) === true) {
            return static::logObject($value);
        } elseif (is_bool($value) === true) {
            return static::logBoolean($value);
        } elseif (is_string($value) === true) {
            return $value;
        } else {
            return static::logObject($value);
        }
    }


    /**
     * Convert an object to string
     *
     * @param   object  $value  the data to convert
     * @return  string          the data in string
     */
    static private function logObject($value)
    {
        ob_start();
        var_dump($value);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }


    /**
     * Convert a boolean to string
     *
     * @param   bool    $value  the data to convert
     * @return  string          the data in string
     */
    static private function logBoolean($value)
    {
        if ($value === true) {
            return 'bool(true)';
        } elseif ($value === false) {
            return 'bool(false)';
        } else {
            return '';
        }
    }
}