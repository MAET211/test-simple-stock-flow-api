<?php

declare(strict_types=1);

/*
 * Domain isolation (strict hexagonal architecture).
 * Reference: docs/spec-laravel/architecture.md section 8.
 */

/** @return list<string> */
function domainFiles(): array
{
    $root = dirname(__DIR__, 2) . '/app/Core/Domain';
    if (!is_dir($root)) {
        return [];
    }

    $files = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if ($file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    return $files;
}

// Anti-vacuous guard: RED until A2 creates the first Domain classes.
test('domain layer exists and contains PHP classes', function (): void {
    expect(domainFiles())->not->toBeEmpty('app/Core/Domain has no PHP classes yet (expected RED in A1).');
});

arch('domain does not depend on Laravel, third parties or other layers')
    ->expect('App\Core\Domain')
    ->not->toUse([
        'Illuminate', 'Laravel', 'Symfony', 'Carbon', 'Doctrine', 'Monolog', 'GuzzleHttp', 'Firebase',
        'App\Core\Application', 'App\Core\Infrastructure', 'App\Http', 'App\Models', 'App\Providers',
    ]);

arch('domain files use strict_types')
    ->expect('App\Core\Domain')
    ->toUseStrictTypes();

test('domain only imports its own namespace', function (): void {
    foreach (domainFiles() as $file) {
        preg_match_all('/^use\s+(?:function\s+|const\s+)?([^;\s]+)/m', (string) file_get_contents($file), $m);
        foreach ($m[1] as $import) {
            expect($import)->toStartWith('App\Core\Domain\\', basename($file) . " imports $import");
        }
    }
});

test('domain has no floats, global date functions or debug calls', function (): void {
    $forbidden = '/(?<![>:\w$])(?:dd|dump|var_dump|date|time|strtotime|now|floatval|round)\s*\(|\bfloat\b/';
    foreach (domainFiles() as $file) {
        expect(preg_match($forbidden, (string) file_get_contents($file)))->toBe(0, basename($file) . ' uses a forbidden construct');
    }
});
