<?php

declare(strict_types=1);

/**
 * The one Laravel piece a framework package needs: the `config()` helper. The Laravel competitor in
 * `docs/competitors.md` reads its defaults through it, so the benchmark stands in for a Laravel
 * application with a `config()` that returns each caller's default.
 *
 * This file has no namespace on purpose: PHP resolves an unqualified call such as `config(...)`
 * inside a namespace to the global function when the namespaced one does not exist.
 */

if (!function_exists('config')) {
    /**
     * @param string|null $key the configuration key, ignored
     * @param mixed $default the value to return
     */
    function config(?string $key = null, mixed $default = null): mixed
    {
        return $default;
    }
}
