<?php
require_once 'vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;
use App\Entity\LogInUsers;

// Load .env
$dotenv = new Dotenv();
$dotenv->loadEnv('.env');

// Boot kernel
$kernel = new Kernel($_ENV['APP_ENV'] ?? 'dev', $_ENV['APP_DEBUG'] ?? false);
$kernel->boot();
$container = $kernel->getContainer();

$em = $container->get('doctrine')->getManager();
$hasher = $container->get('password_hasher');

// Create admin
$admin = new LogInUsers();
$admin->setEmail('admin@staygrid.com');
$admin->setRoles(['ROLE_ADMIN']);
$admin->setPassword($hasher->hashPassword($admin, 'superuser'));
$em->persist($admin);
echo "✓ Admin user created\n";

// Create staff
$staff = new LogInUsers();
$staff->setEmail('staff@gmail.com');
$staff->setRoles(['ROLE_STAFF']);
$staff->setPassword($hasher->hashPassword($staff, 'superuser'));
$em->persist($staff);
echo "✓ Staff user created\n";

// Create client
$client = new LogInUsers();
$client->setEmail('client@staygrid.com');
$client->setRoles(['ROLE_CLIENT']);
$client->setPassword($hasher->hashPassword($client, 'superuser'));
$em->persist($client);
echo "✓ Client user created\n";

$em->flush();
echo "\n✅ All users created successfully!\n";
