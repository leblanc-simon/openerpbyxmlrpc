<?php

use OpenErpByXmlRpc\Criteria;
use OpenErpByXmlRpc\OpenErpByXmlRpc;
use PHPUnit\Framework\TestCase;

class OpenErpByXmlRpcTest extends TestCase
{
    static private array $config = [];

    /**
     * @var OpenErpByXmlRpc
     */
    protected $xml_rpc;

    public static function setUpBeforeClass(): void
    {
        $content = \file_get_contents(__DIR__.'/config.test.json');
        if (false === $content) {
            self::fail('Impossible to read '.__DIR__.'/config.test.json');
        }

        try {
            $config = \json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            if (true === is_array($config)) {
                self::$config = $config;
            } else {
                self::fail('Impossible to read '.__DIR__.'/config.test.json');
            }
        } catch (JsonException $e) {
            self::fail('Impossible to decode '.__DIR__.'/config.test.json');
        }
    }

    protected function setUp(): void
    {
        $this->xml_rpc = new OpenErpByXmlRpc(self::$config['url'], self::$config['port']);
    }

    protected function loginIntoOdoo(): void
    {
        $this->xml_rpc
            ->setDatabase(self::$config['database'])
            ->setUsername(self::$config['username'])
            ->setPassword(self::$config['password'])
        ;
    }

    public function testRead(): void
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->read('res.users', self::$config['uid'], ['login']);

        $this->assertIsArray($user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertArrayHasKey('login', $user[0], 'Check if the result contains login');
        $this->assertSame(self::$config['username'], $user[0]['login'], 'Check if the result has the good login');
    }


    public function testReadOne(): void
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->readOne('res.users', self::$config['uid'], array('login'));

        $this->assertIsArray($user, 'Check the type of return');
        // @phpstan-ignore-next-line
        $this->assertArrayHasKey('login', $user, 'Check if the result contains login');
        // @phpstan-ignore-next-line
        $this->assertSame(self::$config['username'], $user['login'], 'Check if the result has the good login');
    }


    public function testReadOneNothing(): void
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->readOne('res.users', 1000000, array('login'));

        $this->assertNull($user, 'Check the type of return');
    }


    public function testSearchWithArray(): void
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->search('res.users', [['login', '=', self::$config['username']]]);

        $this->assertIsArray($user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertSame(self::$config['uid'], $user[0], 'Check if the result has the good id');
    }


    public function testSearchWithCriteria(): void
    {
        $this->loginIntoOdoo();
        $user = $this->xml_rpc->search(
            'res.users',
            Criteria::create()->equal('login', self::$config['username'])
        );

        $this->assertIsArray($user, 'Check the type of return');
        $this->assertCount(1, $user, 'Check if the result contains only one result');
        $this->assertSame(self::$config['uid'], $user[0], 'Check if the result has the good id');
    }


    public function testDb(): void
    {
        $dbs = $this->xml_rpc->getDbs();

        $this->assertIsArray($dbs, 'Check the type of return');
        $this->assertCount(1, $dbs, 'Check if the result contains only one result');
        $this->assertSame(self::$config['database'], $dbs[0], 'Check if the result has the good name');
    }
}
