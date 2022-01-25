<?php

namespace OpenErpByXmlRpc;

use Psr\Log\LoggerInterface;

/**
 * Class to manipulate Odoo.
 *
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class OpenErpByXmlRpc
{
    private ?Client $xml_rpc = null;
    private string $host;
    private int $port;
    private ?string $database = null;
    private ?string $username = null;
    private ?string $password = null;
    private ?LoggerInterface $logger = null;
    private array $options = [];

    /**
     * Constructor.
     *
     * @param string      $host     The Odoo host (with or without scheme)
     * @param int         $port     The Odoo XML-RPC port
     * @param string|null $username The username to connect in the Odoo
     * @param string|null $password The password to connect in the Odoo
     * @param string|null $database The database to use with Odoo
     */
    public function __construct(
        string $host,
        int $port = 8069,
        ?string $database = null,
        ?string $username = null,
        ?string $password = null
    ) {
        $this->host = $host;
        $this->port = $port;

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
     * Set options for the HTTP Client.
     *
     * @param array $options an array with the option use to initialize HTTP client
     *
     * @return $this
     */
    public function setClientOptions(array $options): self
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Login into Odoo.
     *
     * @return $this
     *
     * @throws Exception if login failed
     */
    public function login(): self
    {
        $this->init(false);

        if (null === $this->database || null === $this->username || null === $this->password) {
            throw new Exception('Check your Odoo setting');
        }

        $this->getClient()
            ->setClientOptions($this->options)
            ->setDatabase($this->database)
            ->setUsername($this->username)
            ->setPassword($this->password)
        ;

        if (false === $this->getClient()->login()) {
            $this->xml_rpc = null;
            throw new Exception('Fail to login');
        }

        return $this;
    }

    /**
     * Return the current UID.
     */
    public function getUid(): ?int
    {
        if (null === $this->xml_rpc) {
            return null;
        }

        return $this->xml_rpc->getUid();
    }

    /**
     * Return the XML-RPC Client (WARNING : use with caution !).
     */
    public function getClient(): Client
    {
        if (null === $this->xml_rpc) {
            throw new Exception('XML-RPC client not initialize');
        }

        return $this->xml_rpc;
    }

    /**
     * Call a method in XML-RPC.
     *
     * @params  ...         The parameter to pass in the XML-RPC call
     *
     * @return mixed The return of XML-RPC call
     *
     * @throws Exception If required parameter is not set (at least 2 parameter : object, method [,parameters, parameters])
     */
    public function call()
    {
        $this->init();

        if (func_num_args() < 2) {
            throw new Exception('call must have at least 2 parameters');
        }

        $args = func_get_args();

        return call_user_func_array([$this->getClient(), 'call'], $args);
    }

    /**
     * Get the list of available database in the Odoo.
     *
     * @return array the list of available database in the Odoo
     */
    public function getDbs()
    {
        $this->init(false);

        $result = $this->getClient()->getListDb();
        if (false === is_array($result)) {
            throw new Exception('result must be an array');
        }

        return $result;
    }

    /**
     * Read the data of a model.
     *
     * @param string    $model  The model name (object to call)
     * @param int|array $ids    The id or list of ids to read
     * @param array     $fields The field to include in the result (nothing for all)
     *
     * @return array The result of the call
     */
    public function read(string $model, $ids, array $fields = [])
    {
        if (true === is_numeric($ids)) {
            $ids = [$ids];
        }

        $result = $this->call($model, 'read', $ids, $fields);
        if (false === is_array($result)) {
            throw new Exception('result must be an array');
        }

        return $result;
    }

    /**
     * Read the data of a model for one id.
     *
     * @param string $model  The model name (object to call)
     * @param int    $id     The id to read
     * @param array  $fields The field to include in the result (nothing for all)
     *
     * @return array|null The result of the call, null if nothing
     */
    public function readOne(string $model, int $id, array $fields = []): ?array
    {
        $result = $this->read($model, $id, $fields);
        if (true === is_array($result) && true === isset($result[0]) && true === is_array($result[0])) {
            return $result[0];
        }

        return null;
    }

    /**
     * Search ids of a model.
     *
     * @param string         $model    The model name (object to call)
     * @param array|Criteria $criteria The criteria for search
     *
     * @return array The result of the call
     *
     * @throws Exception If the criteria is neither array nor Criteria object
     */
    public function search(string $model, $criteria): array
    {
        if ($criteria instanceof Criteria) {
            $criteria = $criteria->get();
        }

        // @phpstan-ignore-next-line
        if (false === is_array($criteria)) {
            throw new Exception('criteria must be an array or an instance of Criteria');
        }

        $result = $this->call($model, 'search', $criteria);
        if (false === is_array($result)) {
            throw new Exception('result must be an array');
        }

        return $result;
    }

    /**
     * Create a new record in Odoo.
     *
     * @param string $model  The model name (object to call)
     * @param array  $values The values to insert
     *
     * @return array The result of the call
     */
    public function create(string $model, array $values): array
    {
        $result = $this->call($model, 'create', $values);
        if (false === is_array($result)) {
            throw new Exception('result must be an array');
        }

        return $result;
    }

    /**
     * Write a existing record in Odoo.
     *
     * @param string    $model  The model name (object to call)
     * @param int|array $ids    The id or list of ids to write
     * @param array     $values The values to insert
     *
     * @return array The result of the call
     */
    public function write(string $model, $ids, array $values): array
    {
        if (true === is_numeric($ids)) {
            $ids = [$ids];
        }

        $result = $this->call($model, 'write', $ids, $values);
        if (false === is_array($result)) {
            throw new Exception('result must be an array');
        }

        return $result;
    }

    /**
     * Set the Odoo database to use.
     *
     * @return $this
     */
    public function setDatabase(string $database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Set the Odoo username to use.
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Set the Odoo password to use.
     *
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Set the logger to use.
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        if (null !== $this->xml_rpc) {
            $this->xml_rpc->setLogger($this->logger);
        }

        return $this;
    }

    /**
     * Init the XML-RPC client.
     */
    private function init(bool $login = true): void
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
