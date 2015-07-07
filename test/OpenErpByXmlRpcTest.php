<?php

class OpenErpByXmlRpcTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var \OpenErpByXmlRpc\Main
     */
    protected $xml_rpc;

    protected function setUp()
    {
        $this->xml_rpc = new \OpenErpByXmlRpc\Main('localhost', 8069);
    }

    protected function loginIntoOdoo()
    {
        $this->xml_rpc
            ->setDatabase('openerp')
            ->setUsername('admin')
            ->setPassword('admin')
        ;
    }

    public function testRead()
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->read('res.users', 1, array('login'));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertArrayHasKey('login', $user[0], 'Check if the result contains login');
        $this->assertSame('admin', $user[0]['login'], 'Check if the result has the good login');
    }


    public function testReadOne()
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->readOne('res.users', 1, array('login'));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertArrayHasKey('login', $user, 'Check if the result contains login');
        $this->assertSame('admin', $user['login'], 'Check if the result has the good login');
    }


    public function testReadOneNothing()
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->readOne('res.users', 1000000, array('login'));

        $this->assertNull($user, 'Check the type of return');
    }


    public function testSearchWithArray()
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->search('res.users', array(array('login', '=', 'admin')));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertSame(1, $user[0], 'Check if the result has the good id');
    }


    public function testSearchWithCriteria()
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->search('res.users', \OpenErpByXmlRpc\Criteria::create()->equal('login', 'admin'));

        $this->assertInternalType('array', $user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertSame(1, $user[0], 'Check if the result has the good id');
    }


    public function testDb()
    {
        $dbs = $this->xml_rpc->getDbs();

        $this->assertInternalType('array', $dbs, 'Check the type of return');
        $this->assertCount(1, $dbs, 'Check if the result contains only one result');
        $this->assertSame('openerp', $dbs[0], 'Check if the result has the good name');
    }
}