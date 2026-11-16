<?php

namespace App\Services\Schools;

use Symfony\Component\HttpFoundation\StreamedResponse;

class CsvTemplateDownload
{
    /**
     * @param  array<int, string>  $headers
     * @param  array<int, string|int|null>  $example
     */
    public function response(string $filename, array $headers, array $example): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $example): void {
            $stream = fopen('php://output', 'w');

            if ($stream === false) {
                return;
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);
            fputcsv($stream, $example);
            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
