<?php declare(strict_types=1);

namespace Lemonade\Pdf\Data;

/**
 * @Table
 * @\Lemonade\Pdf\Data\Table
 */
final class Table
{

    /**
     * @param array<string,string> $data
     */
    public function __construct(

        protected readonly array $data = []

    )
    {
    }

    /**
     * @return array<string, string>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @param string|null $index
     * @return string
     */
    public function getConfig(string $index = null): string
    {

        if((string) $index !== "") {

            return ($this->data[$index] ?? "");
        }

        return (string) $index;
    }



}