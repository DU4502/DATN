<?php

namespace App\Support;

use RuntimeException;

class SimpleXlsxWriter
{
    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $sheets
     */
    public function write(string $path, array $sheets): void
    {
        if ($sheets === []) {
            throw new RuntimeException('Không có dữ liệu để xuất Excel.');
        }

        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException('Không thể tạo thư mục xuất Excel.');
        }

        if (file_exists($path) && ! unlink($path)) {
            throw new RuntimeException('Không thể ghi đè file Excel hiện có.');
        }

        $files = $this->buildArchiveFiles($sheets);
        $this->writeZipArchive($path, $files);
    }

    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $sheets
     * @return array<string, string>
     */
    private function buildArchiveFiles(array $sheets): array
    {
        $sheetCount = count($sheets);
        $normalizedSheets = [];
        $usedSheetNames = [];

        foreach ($sheets as $index => $sheet) {
            $sheetName = $this->sanitizeSheetName((string) ($sheet['name'] ?? ('Sheet'.($index + 1))));
            $sheetName = $this->makeUniqueSheetName($sheetName, $usedSheetNames);
            $usedSheetNames[] = $sheetName;
            $normalizedSheets[] = [
                'name' => $sheetName,
                'rows' => $this->normalizeRows((array) ($sheet['rows'] ?? [])),
            ];
        }

        $files = [
            '[Content_Types].xml' => $this->buildContentTypesXml($sheetCount),
            '_rels/.rels' => $this->buildRootRelationshipsXml(),
            'docProps/app.xml' => $this->buildAppPropertiesXml($normalizedSheets),
            'docProps/core.xml' => $this->buildCorePropertiesXml(),
            'xl/workbook.xml' => $this->buildWorkbookXml($normalizedSheets),
            'xl/_rels/workbook.xml.rels' => $this->buildWorkbookRelationshipsXml($sheetCount),
            'xl/styles.xml' => $this->buildStylesXml(),
        ];

        foreach ($normalizedSheets as $index => $sheet) {
            $files['xl/worksheets/sheet'.($index + 1).'.xml'] = $this->buildWorksheetXml($sheet['rows']);
        }

        return $files;
    }

    /**
     * @param array<string, string> $files
     */
    private function writeZipArchive(string $path, array $files): void
    {
        $handle = fopen($path, 'wb');
        if ($handle === false) {
            throw new RuntimeException('Không thể mở file Excel để ghi.');
        }

        $centralDirectory = '';
        $offset = 0;

        foreach ($files as $name => $content) {
            $name = $this->normalizeArchivePath($name);
            $content = (string) $content;
            $compressedSize = strlen($content);
            $uncompressedSize = $compressedSize;
            $crc = crc32($content);
            $nameLength = strlen($name);
            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034b50,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $compressedSize,
                $uncompressedSize,
                $nameLength,
                0
            );

            fwrite($handle, $localHeader);
            fwrite($handle, $name);
            fwrite($handle, $content);

            $centralDirectory .= pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014b50,
                20,
                20,
                0,
                0,
                0,
                0,
                $crc,
                $compressedSize,
                $uncompressedSize,
                $nameLength,
                0,
                0,
                0,
                0,
                0,
                $offset
            );
            $centralDirectory .= $name;

            $offset += strlen($localHeader) + $nameLength + $compressedSize;
        }

        $centralDirectoryOffset = $offset;
        fwrite($handle, $centralDirectory);

        $centralDirectorySize = strlen($centralDirectory);
        $entryCount = count($files);
        $eocd = pack(
            'VvvvvVVv',
            0x06054b50,
            0,
            0,
            $entryCount,
            $entryCount,
            $centralDirectorySize,
            $centralDirectoryOffset,
            0
        );

        fwrite($handle, $eocd);
        fclose($handle);
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     * @return array<int, array<int, mixed>>
     */
    private function normalizeRows(array $rows): array
    {
        return array_values(array_map(static function ($row): array {
            if (! is_array($row)) {
                return [$row];
            }

            return array_values($row);
        }, $rows));
    }

    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $sheets
     */
    private function buildContentTypesXml(int $sheetCount): string
    {
        $sheetOverrides = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $sheetOverrides .= '<Override PartName="/xl/worksheets/sheet'.$i.'.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'<Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/>'
            .$sheetOverrides
            .'</Types>';
    }

    private function buildRootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $sheets
     */
    private function buildWorkbookXml(array $sheets): string
    {
        $sheetXml = '';
        foreach ($sheets as $index => $sheet) {
            $sheetXml .= '<sheet name="'.$this->escapeXml($sheet['name']).'" sheetId="'.($index + 1).'" r:id="rId'.($index + 1).'"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'.$sheetXml.'</sheets>'
            .'</workbook>';
    }

    private function buildWorkbookRelationshipsXml(int $sheetCount): string
    {
        $relationships = '';
        for ($i = 1; $i <= $sheetCount; $i++) {
            $relationships .= '<Relationship Id="rId'.$i.'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet'.$i.'.xml"/>';
        }

        $relationships .= '<Relationship Id="rId'.($sheetCount + 1).'" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .$relationships
            .'</Relationships>';
    }

    /**
     * @param array<int, array{name: string, rows: array<int, array<int, mixed>>}> $sheets
     */
    private function buildAppPropertiesXml(array $sheets): string
    {
        $titles = '';
        foreach ($sheets as $sheet) {
            $titles .= '<vt:lpstr>'.$this->escapeXml($sheet['name']).'</vt:lpstr>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes">'
            .'<Application>Laravel</Application>'
            .'<DocSecurity>0</DocSecurity>'
            .'<ScaleCrop>false</ScaleCrop>'
            .'<HeadingPairs>'
            .'<vt:vector size="2" baseType="variant">'
            .'<vt:variant><vt:lpstr>Worksheets</vt:lpstr></vt:variant>'
            .'<vt:variant><vt:i4>'.count($sheets).'</vt:i4></vt:variant>'
            .'</vt:vector>'
            .'</HeadingPairs>'
            .'<TitlesOfParts>'
            .'<vt:vector size="'.count($sheets).'" baseType="lpstr">'
            .$titles
            .'</vt:vector>'
            .'</TitlesOfParts>'
            .'<Company></Company>'
            .'<LinksUpToDate>false</LinksUpToDate>'
            .'<SharedDoc>false</SharedDoc>'
            .'<HyperlinksChanged>false</HyperlinksChanged>'
            .'<AppVersion>16.0300</AppVersion>'
            .'</Properties>';
    }

    private function buildCorePropertiesXml(): string
    {
        $now = gmdate('Y-m-d\TH:i:s\Z');

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:creator>Chill Drink</dc:creator>'
            .'<cp:lastModifiedBy>Chill Drink</cp:lastModifiedBy>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:created>'
            .'<dcterms:modified xsi:type="dcterms:W3CDTF">'.$now.'</dcterms:modified>'
            .'</cp:coreProperties>';
    }

    private function buildStylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }

    /**
     * @param array<int, array<int, mixed>> $rows
     */
    private function buildWorksheetXml(array $rows): string
    {
        $maxColumns = 0;
        foreach ($rows as $row) {
            $maxColumns = max($maxColumns, count($row));
        }

        $dimension = $rows === []
            ? 'A1'
            : 'A1:'.$this->columnLetter($maxColumns).count($rows);

        $sheetData = '';
        foreach ($rows as $rowIndex => $row) {
            $sheetData .= '<row r="'.($rowIndex + 1).'">';
            foreach ($row as $columnIndex => $value) {
                $cellRef = $this->columnLetter($columnIndex + 1).($rowIndex + 1);
                $sheetData .= $this->buildCellXml($cellRef, $value);
            }
            $sheetData .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<dimension ref="'.$dimension.'"/>'
            .'<sheetViews><sheetView workbookViewId="0"/></sheetViews>'
            .'<sheetFormatPr defaultRowHeight="15"/>'
            .'<sheetData>'.$sheetData.'</sheetData>'
            .'</worksheet>';
    }

    private function buildCellXml(string $reference, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '<c r="'.$reference.'" t="inlineStr"><is><t></t></is></c>';
        }

        if (is_bool($value)) {
            $value = $value ? 1 : 0;
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="'.$reference.'"><v>'.$this->formatNumeric($value).'</v></c>';
        }

        $textValue = $this->normalizeTextCellValue((string) $value);

        return '<c r="'.$reference.'" t="inlineStr"><is><t'.$this->xmlSpaceAttribute($textValue).'>'.$this->escapeXml($textValue).'</t></is></c>';
    }

    private function formatNumeric(int|float $value): string
    {
        if (is_int($value)) {
            return (string) $value;
        }

        $formatted = rtrim(rtrim(number_format($value, 10, '.', ''), '0'), '.');

        return $formatted === '' ? '0' : $formatted;
    }

    private function xmlSpaceAttribute(string $value): string
    {
        return preg_match('/^\s|\s$/', $value) ? ' xml:space="preserve"' : '';
    }

    private function sanitizeSheetName(string $name): string
    {
        $name = trim(preg_replace('#[\\\\/?*\[\]:]#', ' ', $name) ?? $name);
        if ($name === '') {
            $name = 'Sheet';
        }

        return mb_substr($name, 0, 31);
    }

    /**
     * @param array<int, string> $usedNames
     */
    private function makeUniqueSheetName(string $name, array $usedNames): string
    {
        if (! in_array($name, $usedNames, true)) {
            return $name;
        }

        $baseName = $name;
        $suffix = 2;

        do {
            $candidate = $baseName.' ('.$suffix.')';
            $candidate = mb_substr($candidate, 0, 31);
            $suffix++;
        } while (in_array($candidate, $usedNames, true));

        return $candidate;
    }

    private function normalizeTextCellValue(string $value): string
    {
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@'], true)) {
            return "'".$value;
        }

        return $value;
    }

    private function normalizeArchivePath(string $path): string
    {
        return str_replace('\\', '/', ltrim($path, '/'));
    }

    private function columnLetter(int $index): string
    {
        $index = max(1, $index);
        $letters = '';

        while ($index > 0) {
            $index--;
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26);
        }

        return $letters;
    }

    private function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
