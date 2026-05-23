<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class DebugApi extends WebTestCase
{
    public function testDebug()
    {
        $client = static::createClient();

        // Try login
        $client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'nonexistent@example.com', 'password' => 'test'])
        );

        echo "Login status: " . $client->getResponse()->getStatusCode() . "\n";
        echo "Login response: " . $client->getResponse()->getContent() . "\n";

        // Try rooms
        $client->request('GET', '/api/rooms');
        echo "Rooms status: " . $client->getResponse()->getStatusCode() . "\n";
        echo "Rooms response: " . $client->getResponse()->getContent() . "\n";
    }
}

$test = new DebugApi();
$test->testDebug();