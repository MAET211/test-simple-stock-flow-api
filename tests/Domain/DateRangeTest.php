<?php

declare(strict_types=1);

use App\Core\Domain\Exceptions\InvalidDateRange;
use App\Core\Domain\ValueObjects\DateRange;

it('rejects a range whose end is before its start', function (): void {
    $from = new DateTimeImmutable('2026-02-01T00:00:00+00:00');
    $to = new DateTimeImmutable('2026-01-01T00:00:00+00:00');

    expect(fn () => new DateRange($from, $to))
        ->toThrow(InvalidDateRange::class, 'La fecha final no puede ser anterior a la inicial.');
});

it('accepts an empty range where both ends are equal', function (): void {
    $instant = new DateTimeImmutable('2026-01-01T00:00:00+00:00');

    expect(new DateRange($instant, $instant))->toBeInstanceOf(DateRange::class);
});

it('includes the start and excludes the end', function (): void {
    $from = new DateTimeImmutable('2026-01-01T00:00:00+00:00');
    $to = new DateTimeImmutable('2026-02-01T00:00:00+00:00');
    $range = new DateRange($from, $to);

    expect($range->contains($from))->toBeTrue()
        ->and($range->contains($to))->toBeFalse();
});

it('normalizes both ends to UTC', function (): void {
    $range = new DateRange(
        new DateTimeImmutable('2026-01-01T00:00:00-05:00'),
        new DateTimeImmutable('2026-02-01T00:00:00-05:00'),
    );

    expect($range->from->format('c'))->toBe('2026-01-01T05:00:00+00:00');
});
