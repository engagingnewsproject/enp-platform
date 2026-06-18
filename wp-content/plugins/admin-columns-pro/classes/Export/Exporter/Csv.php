<?php

namespace ACP\Export\Exporter;

final class Csv
{

    private array $rows;

    private array $headers;

    private string $delimiter;

    public function __construct(array $rows, array $headers = [], ?string $delimiter = null)
    {
        $this->rows = $rows;
        $this->headers = $headers;
        $this->delimiter = $delimiter ?? ',';
    }

    public function get_contents(): string
    {
        $stream = fopen('php://memory', 'wb');

        if ($this->headers) {
            // Writes UTF8 BOM for Excel support
            fprintf($stream, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($stream, $this->headers, $this->delimiter);
        }

        foreach ($this->rows as $row) {
            fputcsv($stream, array_map([$this, 'format_output'], $row), $this->delimiter);
        }

        $csv = stream_get_contents($stream, -1, 0);

        fclose($stream);

        return (string)$csv;
    }

    public function count_rows(): int
    {
        return count($this->rows);
    }

    /**
     * Format the output to a string. For scalars (integers, strings, etc.), it returns the input
     * value cast to a string. For arrays, it (deeply) applies this function to the array values
     * and returns them in a comma-separated string
     *
     * @param mixed $value Input value
     *
     * @return string Formatted value
     */
    private function format_output($value): string
    {
        if (is_scalar($value)) {
            // convert HTML entities to symbols
            $value = html_entity_decode((string)$value, ENT_QUOTES, 'utf-8');

            // Remove newlines from value
            return str_replace(PHP_EOL, ' ', (string)$value);
        }

        if (is_array($value)) {
            return implode(', ', array_map([$this, 'format_output'], $value));
        }

        return '';
    }
}