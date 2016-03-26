<?php
/**
 * Tests for loading the user.
 */
require_once __DIR__ . '/../../vendor/autoload.php';

use Silex\WebTestCase;

date_default_timezone_set('Australia/Melbourne');

class meTest extends WebTestCase {
    public function createApplication() {
        return require __DIR__.'/../index.php';
        $app['debug'] = true;
        $app['exception_handler']->disable();

        return $app;
    }

    public function testPerson() {
        $me = new Person;
        $this->assertTrue($me);
    }
}
