<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaffLoginDashboardTest extends WebTestCase
{
    public function testStaffDashboardAfterLogin(): void
    {
        $client = static::createClient();

        // Visit login page to get CSRF token
        $crawler = $client->request('GET', '/login');

        $form = $crawler->filter('form')->form([
            '_username' => 'staff',
            '_password' => 'password',
        ]);
        $client->submit($form);

        // Visit the staff dashboard route directly and ensure it doesn't produce a 500
        $crawler = $client->request('GET', '/staff/dashboard');

        $status = $client->getResponse()->getStatusCode();

        $this->assertNotEquals(500, $status, 'Staff dashboard returned 500. Response content: ' . $client->getResponse()->getContent());
    }
}
