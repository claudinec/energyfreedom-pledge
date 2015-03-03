<?php
/**
 * Display pledge custom field information from Energy Freedom site.
 */
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

// ... definitions

$app->get('/', function () {
    return 'Hello, world!';
});

$app->run();
