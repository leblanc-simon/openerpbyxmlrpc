<?php

namespace OpenErpByXmlRpc;

use Zend\XmlRpc\Client as ZendClient;
use Psr\Log\LoggerInterface;

/**
 * Class to call OpenERP in XML-RPC
 *
 * @package OpenErpByXmlRpc
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Client
{
    private $base_url = null;
    private $port = 8069;

    private $username = null;
    private $password = null;
    private $database = null;

    /**
     * @var LoggerInterface
     */
    private $logger = null;

    static private $clients = array();

    private $auth = null;

    private $errors = array();

    private $paths = array(
        'db'     => '/xmlrpc/db',
        'common' => '/xmlrpc/common',
        'object' => '/xmlrpc/object',
        'report' => '/xmlrpc/report',
    );

    /**
     * Constructor
     *
     * @param   string  $url    The url / host of the OpenERP
     * @param   int     $port   The port of the OpenERP
     */
    public function __construct($url = null, $port = 8069)
    {
        if (null !== $url) {
            $this->setUrl($url);
        }

        $this->setPort($port);
    }

    /**
     * Log in the OpenERP
     *
     * @return  bool        True if the user is logged, false else
     * @throws  Exception   If username, password or database isn't set
     */
    public function login()
    {
        if (null === $this->username || null === $this->password || null === $this->database) {
            throw new Exception('You must set login, password and database before to log in');
        }

        try {
            $result = $this->internalCall('common', 'login', array(
                $this->database,
                $this->username,
                $this->password,
            ));

            if ($result === 0) {
                throw new Exception('Invalid login', 1);
            }

            $this->auth = array(
                $this->database,
                $result,
                $this->password,
            );
        } catch (ZendClient\Exception\FaultException $e) {
            $this->auth = null;
            $this->errors[] = $e;
            return false;
        } catch (Exception $e) {
            $this->auth = null;
            $this->errors[] = $e;
            return false;
        }

        return true;
    }

    /**
     * Get the available database
     *
     * @return  mixed       The result of call
     */
    public function getListDb()
    {
        return $this->internalCall('db', 'list', array());
    }

    /**
     * Call an OpenERP method
     *
     * @params  mixed   ... The list of parameter to pass in the XML-RPC call
     * @return  mixed       The result of call
     */
    public function call()
    {
        $params = array_merge($this->auth, func_get_args());

        return $this->internalCall('object', 'execute', $params);
    }

    /**
     * Prepare an OpenERP report
     *
     * @params  mixed   ... The list of parameter to pass in the XML-RPC call
     * @return  mixed       The result of call
     */
    public function report()
    {
        $params = array_merge($this->auth, func_get_args());

        return $this->internalCall('report', 'report', $params);
    }

    /**
     * Get an OpenERP report
     *
     * @params  mixed   ... The list of parameter to pass in the XML-RPC call
     * @return  mixed       The result of call
     */
    public function getReport()
    {
        $params = array_merge($this->auth, func_get_args());

        return $this->internalCall('report', 'report_get', $params);
    }

    /**
     * Get the current UID
     *
     * @return null|int     The current UID
     */
    public function getUid()
    {
        if (is_array($this->auth) === true && isset($this->auth[1]) === true) {
            return $this->auth[1];
        }

        return null;
    }

    /**
     * Get the last error
     *
     * @return null|\Exception  null if no error, \Exception if an error exist
     */
    public function getError()
    {
        $nb_errors = count($this->errors);

        if ($nb_errors === 0) {
            return null;
        }

        return $this->errors[$nb_errors - 1];
    }

    /**
     * Call the XML-RPC request
     *
     * @param   string  $type   The type of call (db, common, object, report)
     * @param   string  $method The method to call
     * @param   array   $params The parameter to pass in the method
     * @return  mixed           The result of call
     * @throws  ZendClient\Exception\FaultException     If the call failed
     */
    private function internalCall($type, $method, $params = array())
    {
        $formatter = new LoggerFormatter($this->getZendClient($type));

        try {
            $return = $this->getZendClient($type)->call($method, $params);

            if (null !== $this->logger) {
                $this->logger->debug($formatter->getRequest($type));
                $this->logger->debug($formatter->getResponse());
            }
        } catch (ZendClient\Exception\FaultException $e) {
            if (null !== $this->logger) {
                $this->logger->debug($formatter->getRequest($type));
                $this->logger->error($formatter->fault($e));
            }
            throw $e;
        }

        return $return;
    }

    /**
     * Get the Zend XML-RPC Client
     *
     * @param   string              $type   The type of call (db, common, object, report)
     * @return  \Zend\XmlRpc\Client         The Zend XML-RPC Client object
     */
    private function getZendClient($type)
    {
        if (isset(static::$clients[$type]) === false) {
            static::$clients[$type] = new ZendClient($this->buildUrl($type));
        }

        return static::$clients[$type];
    }

    /**
     * Build the URL to call
     *
     * @param   string  $type   The type of call (db, common, object, report)
     * @return  string          The URL to call
     * @throws  Exception       If the type of call doesn't exist
     */
    private function buildUrl($type)
    {
        $url = '';

        if (preg_match('/^https?::/', $this->base_url) === 0) {
            $url = 'http://';
        }

        $url .= $this->base_url;

        if (substr($url, 0, 5) === 'https' && $this->port === 443) {
            $port = '';
        } elseif (substr($url, 0, 5) === 'http:' && $this->port === 80) {
            $port = '';
        } else {
            $port = ':'.$this->port;
        }

        if (isset($this->paths[$type]) === false) {
            throw new Exception($type.' doesn\'t exist');
        }

        $url .= $port.$this->paths[$type];

        return $url;
    }

    /**
     * Setter of url / host
     *
     * @param   string  $url
     * @return  $this
     */
    public function setUrl($url)
    {
        $this->base_url = $url;
        return $this;
    }

    /**
     * Setter of port
     *
     * @param   int     $port
     * @return  $this
     * @throws  Exception   if port isn't an integer
     */
    public function setPort($port)
    {
        if (is_int($port) === false) {
            throw new Exception('Invalid port');
        }

        $this->port = $port;
        return $this;
    }

    /**
     * Setter of username
     *
     * @param   string  $username
     * @return  $this
     */
    public function setUsername($username)
    {
        $this->username = $username;
        return $this;
    }

    /**
     * Setter of password
     *
     * @param   string  $password
     * @return  $this
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * Setter of database
     *
     * @param   string  $database
     * @return  $this
     */
    public function setDatabase($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * Setter of logger
     *
     * @param   LoggerInterface $logger
     * @return  $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }
}
