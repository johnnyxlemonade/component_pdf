<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

use DateTimeImmutable;

/**
 * @template T of string|int|float|DateTimeImmutable
 */
final class Table
{
    /**
     * @param array<string, T|null> $data
     */
    public function __construct(
        protected readonly array $data = []
    ) {}

    /**
     * @return array<string, T|null>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return T|null
     */
    public function getConfig(?string $index = null)
    {
        if ($this->isConfigKeyValid($index)) {
            /** @var T|null */
            return $this->data[$index] ?? null;
        }
        return null;
    }

    private function isConfigKeyValid(?string $index): bool
    {
        return $index !== null && $index !== '';
    }
}
