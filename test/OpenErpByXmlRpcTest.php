<?php

class OpenErpByXmlRpcTest extends PHPUnit_Framework_TestCase
{
    static public function setUpBeforeClass()
    {
        \OpenErpByXmlRpc\Config::add(array(
            'openerp_host' => 'localhost',
            'openerp_port' => 8069,
            'openerp_login' => 'admin',
            'openerp_pass' => 'admin',
            'openerp_database' => 'openerp',
            'log' => true,
            'log_dir' => dirname(__DIR__).'/logs',
            'log_show_pass' => false,
        ));
    }

    public function testRead()
    {
        $xmlrpc = new \OpenErpByXmlRpc\Main();
        $user = $xmlrpc->read('res.users', 1, array('login'));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertArrayHasKey('login', $user[0], 'Check if the result contains login');
        $this->assertSame('admin', $user[0]['login'], 'Check if the result has the good login');
    }


    public function testSearchWithArray()
    {
        $xmlrpc = new \OpenErpByXmlRpc\Main();
        $user = $xmlrpc->search('res.users', array(array('login', '=', 'admin')));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertSame(1, $user[0], 'Check if the result has the good id');
    }


    public function testSearchWithCriteria()
    {
        $xmlrpc = new \OpenErpByXmlRpc\Main();
        $user = $xmlrpc->search('res.users', \OpenErpByXmlRpc\Criteria::create()->equal('login', 'admin'));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertSame(1, $user[0], 'Check if the result has the good id');
    }


    public function testDb()
    {
        $xmlrpc = new \OpenErpByXmlRpc\Main();
        $dbs = $xmlrpc->getDbs();

        $this->assertInternalType('array', $dbs, 'Check the type of return');
        $this->assertCount(1, $dbs, 'Check if the result contains only one result');
        $this->assertSame('openerp', $dbs[0], 'Check if the result has the good name');
    }
}