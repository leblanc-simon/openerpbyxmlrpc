<?php

namespace OpenErpByXmlRpc;

use Zend\XmlRpc\Client as ZendClient;

class LoggerFormatter
{
    /**
     * @var ZendClient
     */
    private $client;

    public function __construct(ZendClient $client)
    {
        $this->client = $client;
    }

    /**
     * Format a request
     *
     * @param string $type
     * @return string
     */
    public function getRequest($type)
    {
        $params = $this->client->getLastRequest()->getParams();
        $login_informations = null;

        if ('common' === $type) {
            $object = '';
            $method = $this->client->getLastRequest()->getMethod();
            $params[2] = '*****';
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
            return 'Nothing...';
        }

        return static::buildRequestString($object, $method, $login_informations, $params);
    }

    /**
     * Format a success response
     *
     * @return string
     */
    public function getResponse()
    {
        $content = 'Response :'."\n";
        $content .= static::logType($this->client->getLastResponse()->getReturnValue());
        return $content;
    }

    /**
     * Format a fault response
     *
     * @param ZendClient\Exception\FaultException $e
     * @return string
     */
    public function fault(ZendClient\Exception\FaultException $e)
    {
        $content = 'Response with Fault :'."\n";
        $content .= static::logType($e->getMessage());

        return $content;
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
            $login_information[2] = '*****';
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
        } elseif (is_int($value) === true) {
            return 'int('.$value.')';
        } elseif (is_float($value) === true) {
            return 'float('.$value.')';
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
        return var_export($value, true);
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
