OpenErpByXmlRpc
===============

Library to communicate into PHP and OpenERP

Usage
-----

```php
// Configure OpenERP host
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

// Init the communication
$xmlrpc = new \OpenErpByXmlRpc\Main();

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