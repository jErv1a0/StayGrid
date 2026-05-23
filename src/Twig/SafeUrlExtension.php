<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

class SafeUrlExtension extends AbstractExtension
{
    public function __construct(private UrlGeneratorInterface $generator)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('safe_path', [$this, 'safePath']),
        ];
    }

    /**
     * Generate a path for a route name, with an optional fallback route name.
     * If both are missing, returns '/' as a last-resort fallback.
     *
     * @param string $name The primary route name
     * @param array $params Route parameters
     * @param string|null $fallbackRoute A fallback route name
     */
    public function safePath(string $name, array $params = [], ?string $fallbackRoute = null): string
    {
        try {
            return $this->generator->generate($name, $params);
        } catch (RouteNotFoundException $e) {
            if ($fallbackRoute) {
                try {
                    return $this->generator->generate($fallbackRoute, $params);
                } catch (RouteNotFoundException $e) {
                    // ignore and fall through
                }
            }

            // Final fallback
            return '/';
        }
    }
}
