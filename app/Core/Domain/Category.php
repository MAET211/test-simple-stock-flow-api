<?php

declare(strict_types=1);

namespace App\Core\Domain;

use App\Core\Domain\Exceptions\CategoryNameRequired;
use App\Core\Domain\ValueObjects\CategoryId;

/**
 * Reference entity, read-only for the system: no port creates, renames or deletes categories.
 */
final class Category
{
    private function __construct(
        private readonly CategoryId $id,
        private string $name,
    ) {}

    public static function create(CategoryId $id, string $name): self
    {
        return new self($id, self::normalize($name));
    }

    public function id(): CategoryId
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = self::normalize($name);
    }

    private static function normalize(string $name): string
    {
        $trimmed = trim($name);

        if ($trimmed === '') {
            throw new CategoryNameRequired;
        }

        return $trimmed;
    }
}
