<?php
require __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

$env = 'dev';
$debug = true;
$kernel = new Kernel($env, $debug);
$kernel->boot();

// Create a minimal Twig environment (independent of Symfony private services)
$loader = new \Twig\Loader\FilesystemLoader(__DIR__ . '/../templates');
$twig = new \Twig\Environment($loader);
// Add minimal functions used in templates to allow parsing
$twig->addFunction(new \Twig\TwigFunction('asset', function ($path) { return $path; }));
$twig->addFunction(new \Twig\TwigFunction('path', function ($route) { return '#'.$route; }));
$twig->addFunction(new \Twig\TwigFunction('csrf_token', function ($id) { return 'csrf_'.$id; }));
// Provide a minimal 'app' global with request attributes for parsing
$twig->addGlobal('app', (object)['request' => null]);

try {
    echo $twig->render('admin/base.html.twig', []);
    echo "\nRendered successfully\n";
} catch (\Throwable $e) {
    echo "Exception: " . get_class($e) . "\n";
    echo $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
