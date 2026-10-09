<?php

declare(strict_types=1);

namespace App\Core\Application;

final readonly class PageRequest
{
    public int $page;

    public int $size;

    public function __construct(?int $page = 1, ?int $size = 20)
    {
        $p = $page ?? 1;
        $s = $size ?? 20;

        $this->page = $p < 1 ? 1 : $p;
        $this->size = $s < 1 ? 20 : min($s, 100);
    }

    public static function of(?int $page = 1, ?int $size = 20): self
    {
        return new self($page, $size);
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->size;
    }
}
