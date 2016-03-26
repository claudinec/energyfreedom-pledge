<?php
/**
 * Test NationBuilder authentication.
 */
require_once __DIR__ . '/../../vendor/autoload.php';
use Silex\WebTestCase;

class authTest extends WebTestCase {
    public function createApplication() {
        return require __DIR__.'/../auth.php';
        $app['debug'] = true;
        $app['exception_handler']->disable();

        // return $app;
    }

    public function testInitialPage() {
        $client = $this->createClient();
        // $crawler = $client->request('GET', '/');

        $this->assertTrue($client->getResponse()->isOk());
    }
}
