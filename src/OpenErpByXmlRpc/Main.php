<?php

namespace OpenErpByXmlRpc;

/**
 * Class to manipulate OpenERP
 *
 * @package OpenErpByXmlRpc
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Main
{
    private static $xml_rpc  = null;

    /**
     * Constructor
     *
     * @param   string  $host       The OpenERP host (with or without scheme)
     * @param   int     $port       The OpenERP XML-RPC port
     * @param   string  $username   The username to connect in the OpenERP
     * @param   string  $password   The password to connect in the OpenERP
     * @param   string  $database   The database to use with OpenERP
     * @throws  Exception           If required information to connectwith OpenERP missed
     * @throws  Exception           If login failed
     */
    public function __construct($host = null, $port = null, $username = null, $password = null, $database = null)
    {
        if (self::$xml_rpc === null) {
            $host       = $host     ?: Config::get('openerp_host', null);
            $port       = $port     ?: Config::get('openerp_port', null);
            $username   = $username ?: Config::get('openerp_login', null);
            $password   = $password ?: Config::get('openerp_pass', null);
            $database   = $database ?: Config::get('openerp_database', null);

            if ($host === null || $port === null || $username === null || $password === null || $database === null) {
                throw new Exception('Check your OpenERP setting');
            }

            self::$xml_rpc = new Client();
            self::$xml_rpc->setUrl($host);
            self::$xml_rpc->setPort((int)$port);
            self::$xml_rpc->setUsername($username);
            self::$xml_rpc->setPassword($password);
            self::$xml_rpc->setDatabase($database);
        }

        if (self::$xml_rpc->login() === false) {
            throw new Exception('Fail to login');
        }
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
        if (func_num_args() < 2) {
            throw new Exception('call must have at least 2 parameters');
        }

        $args = func_get_args();

        return call_user_func_array(array(self::$xml_rpc, 'call'), $args);
    }


    /**
     * Get the list of available database in the OpenERP
     *
     * @return array    the list of available database in the OpenERP
     */
    public function getDbs()
    {
        return self::$xml_rpc->getListDb();
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
     * Create a new record in OpenERP
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
     * Write a existing record in OpenERP
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
}