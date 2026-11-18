<?php

namespace App\Services\Schools;

use Illuminate\Validation\ValidationException;

class CsvFileReader
{
    /**
     * @return array{headers: array<int, string>, records: array<int, array<string, string|null>>}
     */
    public function read(string $path, string $errorKey): array
    {
        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([$errorKey => 'The uploaded CSV file could not be opened.']);
        }

        $rawHeaders = fgetcsv($handle);

        if ($rawHeaders === false) {
            fclose($handle);

            throw ValidationException::withMessages([$errorKey => 'The uploaded CSV file is empty.']);
        }

        $headers = array_map(
            fn ($header): string => preg_replace('/^\xEF\xBB\xBF/', '', trim((string) $header)),
            $rawHeaders,
        );

        if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
            fclose($handle);

            throw ValidationException::withMessages([$errorKey => 'The CSV header contains an empty or duplicate column name. Download a fresh template and try again.']);
        }

        $records = [];
        $rowNumber = 1;

        while (($values = fgetcsv($handle)) !== false) {
            $rowNumber++;

            if (collect($values)->every(fn ($value): bool => trim((string) $value) === '')) {
                continue;
            }

            if (count($values) > count($headers)) {
                fclose($handle);

                throw ValidationException::withMessages([$errorKey => "Row {$rowNumber} has more columns than the template header."]);
            }

            $records[] = array_combine(
                $headers,
                array_slice(array_pad($values, count($headers), null), 0, count($headers)),
            );
        }

        fclose($handle);

        return ['headers' => $headers, 'records' => $records];
    }
}
