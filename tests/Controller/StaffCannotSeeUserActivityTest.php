<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StaffCannotSeeUserActivityTest extends WebTestCase
{
    public function testStaffCannotAccessUserActivity(): void
    {
        $client = static::createClient();

        // Visit login page to get CSRF token
        $crawler = $client->request('GET', '/login');
        $token = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        // Submit login form as staff
        $client->request('POST', '/login', [
            '_username' => 'staff@gmail.com',
            '_password' => 'staff12345',
            '_csrf_token' => $token,
        ]);

        // Attempt to access admin-only activity page
        $client->request('GET', '/admin/user-activity/');

        // Expect 403 Forbidden (IsGranted('ROLE_ADMIN'))
        $this->assertTrue(in_array($client->getResponse()->getStatusCode(), [403, 302]), 'Expected 403 or redirect to login when staff attempts to access admin user activity. Actual: ' . $client->getResponse()->getStatusCode());
    }
}
