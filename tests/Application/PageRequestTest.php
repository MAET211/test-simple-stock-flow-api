<?php

declare(strict_types=1);

namespace Tests\Application;

use App\Core\Application\PageRequest;

test('page request uses default page 1 and size 20', function (): void {
    $req = new PageRequest;

    expect($req->page)->toBe(1);
    expect($req->size)->toBe(20);
    expect($req->offset())->toBe(0);
});

test('page request caps size above 100 to 100', function (): void {
    $req = new PageRequest(page: 2, size: 999);

    expect($req->page)->toBe(2);
    expect($req->size)->toBe(100);
    expect($req->offset())->toBe(100);
});

test('page request normalizes page less than 1 to 1', function (): void {
    $req = new PageRequest(page: -5, size: 10);

    expect($req->page)->toBe(1);
    expect($req->size)->toBe(10);
    expect($req->offset())->toBe(0);
});

test('page request normalizes size less than 1 to 20', function (): void {
    $req = new PageRequest(page: 1, size: 0);

    expect($req->page)->toBe(1);
    expect($req->size)->toBe(20);
});
