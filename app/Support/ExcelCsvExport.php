<?php

namespace App\Support;

use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExcelCsvExport
{
    /**
     * Writes safe UTF-8 CSV rows that open correctly in spreadsheet applications.
     */
    public static function download(string $filename, callable $writeRows): StreamedResponse
    {
        return Response::streamDownload(function () use ($writeRows): void {
            $output = fopen('php://output', 'w');
            if ($output === false) {
                throw new \RuntimeException('Unable to open the report export stream.');
            }

            try {
                fwrite($output, "\xEF\xBB\xBF");
                $writeRow = static function (array $row) use ($output): void {
                    $safeRow = array_map(static function ($value): string {
                        if ($value === null) {
                            return '';
                        }

                        if (! is_scalar($value)) {
                            throw new \InvalidArgumentException('Report export values must be scalar.');
                        }

                        $value = (string) $value;

                        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'".$value : $value;
                    }, $row);

                    fputcsv($output, $safeRow, ',', '"', '\\');
                };

                $writeRows($writeRow);
            } finally {
                fclose($output);
            }
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
