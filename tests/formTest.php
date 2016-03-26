<?php
/**
 * Test Form.
 */
require_once __DIR__ . '/../../vendor/autoload.php';
use Silex\WebTestCase;

class authTest extends WebTestCase {
    public function createApplication() {
        return require __DIR__.'/../formMin.php';
        $app['debug'] = true;
        $app['exception_handler']->disable();

        // return $app;
    }

    public function testLoadForm() {
        $client = $this->createClient();
        $crawler = $client->request('GET', '/pledge');

        $this->assertTrue($client->getResponse()->isOk());
    }
}
