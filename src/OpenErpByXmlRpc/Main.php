<?php

namespace OpenErpByXmlRpc;

use Psr\Log\LoggerInterface;

/**
 * Class to manipulate Odoo
 *
 * @package OpenErpByXmlRpc
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Main
{
    /**
     * @var Client
     */
    private $xml_rpc  = null;

    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @var string
     */
    private $database;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Constructor
     *
     * @param   string  $host       The Odoo host (with or without scheme)
     * @param   int     $port       The Odoo XML-RPC port
     * @param   string  $username   The username to connect in the Odoo
     * @param   string  $password   The password to connect in the Odoo
     * @param   string  $database   The database to use with Odoo
     * @throws  Exception           If required information to connect with Odoo missed
     * @throws  Exception           If login failed
     */
    public function __construct($host, $port = 8069, $database = null, $username = null, $password = null)
    {
        $this->host = $host;
        $this->port = (int)$port;

        if (null !== $database) {
            $this->setDatabase($database);
        }

        if (null !== $username) {
            $this->setUsername($username);
        }

        if (null !== $password) {
            $this->setPassword($password);
        }
    }

    /**
     * Login into Odoo
     *
     * @return $this
     * @throws Exception if login failed
     */
    public function login()
    {
        $this->init(false);

        if (null === $this->database || null === $this->username || null === $this->password) {
            throw new Exception('Check your Odoo setting');
        }

        $this->xml_rpc
            ->setDatabase($this->database)
            ->setUsername($this->username)
            ->setPassword($this->password)
        ;

        if (false === $this->xml_rpc->login()) {
            $this->xml_rpc = null;
            throw new Exception('Fail to login');
        }

        return $this;
    }

    /**
     * Return the current UID
     *
     * @return int|null
     */
    public function getUid()
    {
        if (null === $this->xml_rpc) {
            return null;
        }

        return $this->xml_rpc->getUid();
    }

    /**
     * Return the XML-RPC Client (WARNING : use with caution !)
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->xml_rpc;
    }

    /**
     * Call a method in XML-RPC
     *
     * @params  ...         The parameter to pass in the XML-RPC call
     * @return  mixed       The return of XML-RPC call
     * @throws  Exception   If required parameter is not set (at least 2 parameter : object, method [,parameters, parameters])
     */
    public function call()
    {
        $this->init();

        if (func_num_args() < 2) {
            throw new Exception('call must have at least 2 parameters');
        }

        $args = func_get_args();

        return call_user_func_array(array($this->xml_rpc, 'call'), $args);
    }

    /**
     * Get the list of available database in the Odoo
     *
     * @return array    the list of available database in the Odoo
     */
    public function getDbs()
    {
        $this->init(false);
        
        return $this->xml_rpc->getListDb();
    }

    /**
     * Read the data of a model
     *
     * @param   string      $model      The model name (object to call)
     * @param   int|array   $ids        The id or list of ids to read
     * @param   array       $fields     The field to include in the result (nothing for all)
     * @return  array                   The result of the call
     */
    public function read($model, $ids, array $fields = array())
    {
        if (is_numeric($ids) === true) {
            $ids = array($ids);
        }

        return $this->call($model, 'read', $ids, $fields);
    }

    /**
     * Read the data of a model for one id
     *
     * @param   string      $model      The model name (object to call)
     * @param   int         $id         The id to read
     * @param   array       $fields     The field to include in the result (nothing for all)
     * @return  array|null              The result of the call, null if nothing
     * @throws  Exception               If id is not a numeric
     */
    public function readOne($model, $id, array $fields =array())
    {
        if (is_numeric($id) === false) {
            throw new Exception('id must be a numeric');
        }

        $result = $this->read($model, $id, $fields);
        if (is_array($result) === true && isset($result[0]) === true && is_array($result[0]) === true) {
            return $result[0];
        }

        return null;
    }

    /**
     * Search ids of a model
     *
     * @param   string          $model      The model name (object to call)
     * @param   array|Criteria  $criteria   The criteria for search
     * @return  array                       The result of the call
     * @throws  Exception                   If the criteria is neither array nor Criteria object
     */
    public function search($model, $criteria)
    {
        if ($criteria instanceof Criteria) {
            $criteria = $criteria->get();
        }

        if (is_array($criteria) === false) {
            throw new Exception('criteria must be an array or an instance of Criteria');
        }

        return $this->call($model, 'search', $criteria);
    }

    /**
     * Create a new record in Odoo
     *
     * @param   string      $model      The model name (object to call)
     * @param   array       $values     The values to insert
     * @return  array                   The result of the call
     */
    public function create($model, array $values)
    {
        return $this->call($model, 'create', $values);
    }

    /**
     * Write a existing record in Odoo
     *
     * @param   string      $model      The model name (object to call)
     * @param   int|array   $ids        The id or list of ids to write
     * @param   array       $values     The values to insert
     * @return  array                   The result of the call
     */
    public function write($model, $ids, array $values)
    {
        if (is_numeric($ids) === true) {
            $ids = array($ids);
        }

        return $this->call($model, 'write', $ids, $values);
    }

    /**
     * Set the Odoo database to use
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
     * Set the Odoo username to use
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
     * Set the Odoo password to use
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
     * Set the logger to use
     *
     * @param   LoggerInterface $logger
     * @return  $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        if (null !== $this->xml_rpc) {
            $this->xml_rpc->setLogger($this->logger);
        }
        return $this;
    }

    /**
     * Init the XML-RPC client
     */
    private function init($login = true)
    {
        if (null !== $this->xml_rpc) {
            if (true === $login && null === $this->xml_rpc->getUid()) {
                $this->login();
            }
            return;
        }

        $this->xml_rpc = new Client($this->host, $this->port);

        if (null !== $this->logger) {
            $this->xml_rpc->setLogger($this->logger);
        }

        if (true === $login) {
            $this->login();
        }
    }
}
