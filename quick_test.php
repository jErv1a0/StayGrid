<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class QuickTest extends WebTestCase
{
    public function testApi()
    {
        $client = static::createClient();

        // Test status endpoint (should work without auth)
        $client->request('GET', '/api/status');
        echo "Status: " . $client->getResponse()->getStatusCode() . "\n";
        echo "Response: " . $client->getResponse()->getContent() . "\n\n";

        // Test login
        $client->request('POST', '/api/login', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'email' => 'test@example.com',
            'password' => 'test'
        ]));
        echo "Login: " . $client->getResponse()->getStatusCode() . "\n";
        echo "Response: " . $client->getResponse()->getContent() . "\n\n";
    }
}

$test = new QuickTest();
$test->testApi();