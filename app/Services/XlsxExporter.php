<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * Minimal dependency-free XLSX writer backed by ZipArchive.
 *
 * Produces a spreadsheet workbook that opens cleanly in Excel/LibreOffice
 * using inline (non-shared) string cells — no external package required.
 */
class XlsxExporter
{
    /**
     * Stream rows to an .xlsx download.
     *
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    public function stream(string $filename, array $headers, iterable $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $path = tempnam(sys_get_temp_dir(), 'xlsx');
            $zip = new ZipArchive;

            if ($zip->open($path, ZipArchive::CREATE) !== true) {
                throw new RuntimeException('Could not create spreadsheet archive.');
            }

            $zip->addFromString('[Content_Types].xml', $this->contentTypes());
            $zip->addFromString('_rels/.rels', $this->rels());
            $zip->addFromString('xl/workbook.xml', $this->workbook());
            $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRels());
            $zip->addFromString('xl/worksheets/sheet1.xml', $this->sheet($headers, $rows));
            $zip->close();

            readfile($path);
            @unlink($path);
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Transactions" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  iterable<int, array<int, scalar|null>>  $rows
     */
    private function sheet(array $headers, iterable $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetData>';

        $xml .= $this->row(array_map(fn (string $h) => ['t' => 's', 'v' => $h], $headers));

        foreach ($rows as $row) {
            $xml .= $this->row(array_map(fn ($cell) => $this->cell($cell), $row));
        }

        return $xml.'</sheetData></worksheet>';
    }

    /**
     * @param  array<int, array{t: string, v: scalar|null}>  $cells
     */
    private function row(array $cells): string
    {
        $xml = '<row>';

        foreach ($cells as $cell) {
            $value = $cell['v'];

            if ($value === null) {
                $xml .= '<c/>';

                continue;
            }

            if ($cell['t'] === 'n') {
                $xml .= '<c><v>'.(is_numeric($value) ? $value : 0).'</v></c>';

                continue;
            }

            $xml .= '<c t="inlineStr"><is><t xml:space="preserve">'.$this->escape((string) $value).'</t></is></c>';
        }

        return $xml.'</row>';
    }

    /**
     * @return array{t: string, v: scalar|null}
     */
    private function cell(mixed $value): array
    {
        if (is_numeric($value)) {
            return ['t' => 'n', 'v' => $value];
        }

        return ['t' => 's', 'v' => $value];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
