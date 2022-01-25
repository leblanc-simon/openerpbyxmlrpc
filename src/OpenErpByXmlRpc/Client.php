<?php

namespace OpenErpByXmlRpc;

use Laminas\Http;
use Laminas\XmlRpc\Client as LaminasClient;
use Psr\Log\LoggerInterface;

/**
 * Class to call OpenERP in XML-RPC.
 *
 * @license MIT
 * @author  Simon Leblanc <contact@leblanc-simon.eu>
 */
class Client
{
    private ?string $base_url = null;
    private int $port = 8069;

    private ?string $username = null;
    private ?string $password = null;
    private ?string $database = null;

    private array $options = [];

    private ?LoggerInterface $logger = null;

    private static array $clients = [];

    private ?array $auth = null;

    private array $errors = [];

    private array $paths = [
        'db' => '/xmlrpc/db',
        'common' => '/xmlrpc/common',
        'object' => '/xmlrpc/object',
        'report' => '/xmlrpc/report',
    ];

    /**
     * Constructor.
     *
     * @param string|null $url  The url / host of the OpenERP
     * @param int         $port The port of the OpenERP
     */
    public function __construct(?string $url = null, int $port = 8069)
    {
        if (null !== $url) {
            $this->setUrl($url);
        }

        $this->setPort($port);
    }

    /**
     * Log in the OpenERP.
     *
     * @return bool True if the user is logged, false else
     *
     * @throws Exception If username, password or database isn't set
     */
    public function login(): bool
    {
        if (null === $this->username || null === $this->password || null === $this->database) {
            throw new Exception('You must set login, password and database before to log in');
        }

        try {
            $result = $this->internalCall('common', 'login', [
                $this->database,
                $this->username,
                $this->password,
            ]);

            if (0 === $result) {
                throw new Exception('Invalid login', 1);
            }

            $this->auth = [
                $this->database,
                $result,
                $this->password,
            ];
        } catch (LaminasClient\Exception\FaultException $e) {
            $this->auth = null;
            $this->errors[] = $e;

            return false;
        } catch (\Throwable $e) {
            $this->auth = null;
            $this->errors[] = $e;

            return false;
        }

        return true;
    }

    /**
     * Set options for the HTTP Client.
     *
     * @param array $options an array with the option use to initialize HTTP client
     *
     * @return $this
     */
    public function setClientOptions(array $options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get the available database.
     *
     * @return mixed The result of call
     */
    public function getListDb()
    {
        return $this->internalCall('db', 'list', []);
    }

    /**
     * Call an OpenERP method.
     *
     * @params  mixed   ... The list of parameter to pass in the XML-RPC call
     *
     * @return mixed The result of call
     */
    public function call()
    {
        if (null === $this->auth) {
            throw new Exception('Impossible to call method if not logged');
        }

        $params = array_merge($this->auth, func_get_args());

        return $this->internalCall('object', 'execute', $params);
    }

    /**
     * Prepare an OpenERP report.
     *
     * @params  mixed   ... The list of parameter to pass in the XML-RPC call
     *
     * @return mixed The result of call
     */
    public function report()
    {
        if (null === $this->auth) {
            throw new Exception('Impossible to call method if not logged');
        }

        $params = array_merge($this->auth, func_get_args());

        return $this->internalCall('report', 'report', $params);
    }

    /**
     * Get an OpenERP report.
     *
     * @params  mixed   ... The list of parameter to pass in the XML-RPC call
     *
     * @return mixed The result of call
     */
    public function getReport()
    {
        if (null === $this->auth) {
            throw new Exception('Impossible to call method if not logged');
        }

        $params = array_merge($this->auth, func_get_args());

        return $this->internalCall('report', 'report_get', $params);
    }

    /**
     * Get the current UID.
     *
     * @return int|null The current UID
     */
    public function getUid()
    {
        if (true === is_array($this->auth) && true === isset($this->auth[1])) {
            return $this->auth[1];
        }

        return null;
    }

    /**
     * Get the last error.
     *
     * @return \Exception|null null if no error, \Exception if an error exist
     */
    public function getError()
    {
        $nb_errors = count($this->errors);

        if (0 === $nb_errors) {
            return null;
        }

        return $this->errors[$nb_errors - 1];
    }

    /**
     * Call the XML-RPC request.
     *
     * @param string $type   The type of call (db, common, object, report)
     * @param string $method The method to call
     * @param array  $params The parameter to pass in the method
     *
     * @return mixed The result of call
     *
     * @throws LaminasClient\Exception\FaultException If the call failed
     */
    private function internalCall($type, $method, $params = [])
    {
        $formatter = new LoggerFormatter($this->getClient($type));

        try {
            $return = $this->getClient($type)->call($method, $params);

            if (null !== $this->logger) {
                $this->logger->debug($formatter->getRequest($type));
                $this->logger->debug($formatter->getResponse());
            }
        } catch (LaminasClient\Exception\FaultException $e) {
            if (null !== $this->logger) {
                $this->logger->debug($formatter->getRequest($type));
                $this->logger->error($formatter->fault($e));
            }
            throw $e;
        }

        return $return;
    }

    /**
     * Get the Zend XML-RPC Client.
     *
     * @param string $type The type of call (db, common, object, report)
     *
     * @return LaminasClient The Zend XML-RPC Client object
     *
     * @throws Exception
     */
    private function getClient(string $type): LaminasClient
    {
        if (false === isset(self::$clients[$type])) {
            self::$clients[$type] = new LaminasClient($this->buildUrl($type), new Http\Client(null, $this->options));
        }

        return self::$clients[$type];
    }

    /**
     * Build the URL to call.
     *
     * @param string $type The type of call (db, common, object, report)
     *
     * @return string The URL to call
     *
     * @throws Exception If the type of call doesn't exist
     */
    private function buildUrl(string $type): string
    {
        $url = '';

        if (null === $this->base_url || 0 === preg_match('/^https?::/', $this->base_url)) {
            $url = 'http://';
        }

        $url .= $this->base_url;

        if (0 === strpos($url, 'https') && 443 === $this->port) {
            $port = '';
        } elseif (0 === strpos($url, 'https') && 80 === $this->port) {
            $port = '';
        } else {
            $port = ':'.$this->port;
        }

        if (false === isset($this->paths[$type])) {
            throw new Exception($type.' doesn\'t exist');
        }

        $url .= $port.$this->paths[$type];

        return $url;
    }

    /**
     * Setter of url / host.
     *
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->base_url = $url;

        return $this;
    }

    /**
     * Setter of port.
     *
     * @return $this
     */
    public function setPort(int $port): self
    {
        $this->port = $port;

        return $this;
    }

    /**
     * Setter of username.
     *
     * @return $this
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Setter of password.
     *
     * @return $this
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Setter of database.
     *
     * @return $this
     */
    public function setDatabase(string $database): self
    {
        $this->database = $database;

        return $this;
    }

    /**
     * Setter of logger.
     *
     * @return $this
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }
}
