<?php

namespace OpenErpByXmlRpc;

use Laminas\XmlRpc\Client as LaminasClient;

class LoggerFormatter
{
    private LaminasClient $client;

    public function __construct(LaminasClient $client)
    {
        $this->client = $client;
    }

    /**
     * Format a request.
     */
    public function getRequest(string $type): string
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
            $login_informations = [$params[0], $params[1], $params[2]];
            unset($params[0], $params[1], $params[2], $params[3], $params[4]);
        } elseif ('db' === $type) {
            $object = 'db';
            $method = 'list';
            $params = [];
        } else {
            return 'Nothing...';
        }

        return static::buildRequestString($object, $method, $login_informations, $params);
    }

    /**
     * Format a success response.
     */
    public function getResponse(): string
    {
        $content = 'Response :'."\n";
        $content .= static::logType($this->client->getLastResponse()->getReturnValue());

        return $content;
    }

    /**
     * Format a fault response.
     */
    public function fault(LaminasClient\Exception\FaultException $e): string
    {
        $content = 'Response with Fault :'."\n";
        $content .= static::logType($e->getMessage());

        return $content;
    }

    /**
     * Build the request in string.
     *
     * @param string     $object            The object call in XML-RPC
     * @param string     $method            The method call in XML-RPC
     * @param array|null $login_information The login information
     * @param array      $params            The parameters get in the method to call
     */
    private static function buildRequestString(string $object, string $method, ?array $login_information, array $params): string
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
     * Convert a mixed data to string.
     *
     * @param mixed $value the data to convert
     *
     * @return string the data in string
     */
    private static function logType($value): string
    {
        if (true === is_array($value) || true === is_object($value)) {
            return static::logObject($value);
        }

        if (true === is_bool($value)) {
            return static::logBoolean($value);
        }

        if (true === is_int($value)) {
            return 'int('.$value.')';
        }

        if (true === is_float($value)) {
            return 'float('.$value.')';
        }

        if (true === is_string($value)) {
            return $value;
        }

        // @phpstan-ignore-next-line
        return static::logObject($value);
    }

    /**
     * Convert an object to string.
     *
     * @param array|object $value the data to convert
     *
     * @return string the data in string
     */
    private static function logObject($value): string
    {
        return var_export($value, true);
    }

    /**
     * Convert a boolean to string.
     *
     * @param bool $value the data to convert
     *
     * @return string the data in string
     */
    private static function logBoolean(bool $value): string
    {
        if (true === $value) {
            return 'bool(true)';
        } elseif (false === $value) {
            return 'bool(false)';
        } else {
            return '';
        }
    }
}
