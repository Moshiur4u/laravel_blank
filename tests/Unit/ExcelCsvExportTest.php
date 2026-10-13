<?php

use App\Support\ExcelCsvExport;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Tests\TestCase;

uses(TestCase::class);

test('exports Excel-compatible UTF-8 CSV and neutralizes formula cells', function () {
    $response = ExcelCsvExport::download('sample-report.csv', function (callable $writeRow): void {
        $writeRow(['Report', 'Value']);
        $writeRow(['Product', '=1+1']);
    });

    expect($response)->toBeInstanceOf(StreamedResponse::class)
        ->and($response->headers->get('Content-Type'))->toBe('text/csv; charset=UTF-8')
        ->and($response->headers->get('Content-Disposition'))->toContain('sample-report.csv');

    ob_start();
    $response->sendContent();
    $csv = ob_get_clean();

    expect($csv)->toStartWith("\xEF\xBB\xBF")
        ->and($csv)->toContain("'=1+1");
});
