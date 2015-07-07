OpenErpByXmlRpc
===============

Library to communicate into PHP and OpenERP

Usage
-----

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use OpenErpByXmlRpc\Main;

// Configure Logger (if you want log request and response)
$logger = new Logger('xmlrpc');
$handler = new StreamHandler(__DIR__.'/logs/xmlrpc-'.date('Ymd').'.log', Logger::DEBUG);
$handler->setFormatter(new \Monolog\Formatter\LineFormatter(null, null, true));
$logger->pushHandler($handler);

// Init the communication
$xmlrpc = new Main('localhost', 8069, 'database', 'username', 'password');
$xmlrpc->setLogger($logger); // Not required

// Search datas
$user = $xmlrpc->search('res.users', array(array('login', '=', 'admin')));
// or
$user = $xmlrpc->search(
    'res.users',
    \OpenErpByXmlRpc\Criteria::create()->equal('login', 'admin')
);

// Get and retrieve data
$user = $xmlrpc->read('res.users', 1, array('login'));
$users = $xmlrpc->read('res.users', array(1, 2), array('login'));

// Call another method
$res = $xmlrpc->call('res.users', 'another_method', 'param1', 'param2', ...);
```

Others methods exists, check in the source code !

Authors
-------

* Simon Leblanc : contact@leblanc-simon.eu

License
-------

[MIT](http://opensource.org/licenses/MIT)