<?php
declare(strict_types=1);

class ExcelWorkbook
{
    /** @var list<array{name: string, headers: list<string>, rows: list<list<mixed>>}> */
    private array $sheets = [];

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    public function addSheet(string $name, array $headers, array $rows): void
    {
        $this->sheets[] = [
            'name' => $this->uniqueSheetName($name),
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    public function bytes(): string
    {
        if ($this->sheets === []) {
            throw new RuntimeException('Workbook has no sheets');
        }
        if (!class_exists(ZipArchive::class)) {
            throw new RuntimeException('PHP zip extension is required to export Excel');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        if ($tmp === false) {
            throw new RuntimeException('Could not create a temporary file for Excel export');
        }

        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) !== true) {
            unlink($tmp);
            throw new RuntimeException('Could not write the Excel file');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        foreach ($this->sheets as $i => $sheet) {
            $zip->addFromString(
                'xl/worksheets/sheet' . ($i + 1) . '.xml',
                $this->sheetXml($sheet['headers'], $sheet['rows'])
            );
        }
        $zip->close();

        $bytes = file_get_contents($tmp);
        unlink($tmp);
        if ($bytes === false) {
            throw new RuntimeException('Could not read the Excel file');
        }
        return $bytes;
    }

    private function uniqueSheetName(string $name): string
    {
        $clean = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', ' ', $name) ?? $name;
        $clean = trim($clean);
        if ($clean === '') {
            $clean = 'Sheet';
        }
        $base = function_exists('mb_substr') ? mb_substr($clean, 0, 31) : substr($clean, 0, 31);
        $candidate = $base;
        $n = 2;
        $used = array_column($this->sheets, 'name');
        while (in_array($candidate, $used, true)) {
            $suffix = ' (' . $n . ')';
            $keep = 31 - strlen($suffix);
            $stem = function_exists('mb_substr') ? mb_substr($base, 0, max(1, $keep)) : substr($base, 0, max(1, $keep));
            $candidate = $stem . $suffix;
            $n++;
        }
        return $candidate;
    }

    private function contentTypesXml(): string
    {
        $overrides = [
            '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>',
        ];
        foreach ($this->sheets as $i => $_) {
            $n = $i + 1;
            $overrides[] = '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . implode('', $overrides)
            . '</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function workbookXml(): string
    {
        $sheets = '';
        foreach ($this->sheets as $i => $sheet) {
            $n = $i + 1;
            $sheets .= '<sheet name="' . $this->xml($sheet['name']) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $sheets . '</sheets>'
            . '</workbook>';
    }

    private function workbookRelsXml(): string
    {
        $rels = '';
        foreach ($this->sheets as $i => $_) {
            $n = $i + 1;
            $rels .= '<Relationship Id="rId' . $n . '"'
                . ' Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet"'
                . ' Target="worksheets/sheet' . $n . '.xml"/>';
        }
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . $rels
            . '</Relationships>';
    }

    /**
     * @param list<string> $headers
     * @param list<list<mixed>> $rows
     */
    private function sheetXml(array $headers, array $rows): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetData>';
        $xml .= $this->rowXml(1, $headers);
        foreach ($rows as $i => $row) {
            $xml .= $this->rowXml($i + 2, $row);
        }
        return $xml . '</sheetData></worksheet>';
    }

    /**
     * @param list<mixed> $cells
     */
    private function rowXml(int $rowNumber, array $cells): string
    {
        $xml = '<row r="' . $rowNumber . '">';
        foreach (array_values($cells) as $i => $value) {
            $ref = self::columnLetter($i) . $rowNumber;
            if ($value === null || $value === '') {
                continue;
            }
            if (is_int($value) || is_float($value) || (is_string($value) && is_numeric($value) && !preg_match('/^0\\d/', $value))) {
                $xml .= '<c r="' . $ref . '"><v>' . $this->xml((string) $value) . '</v></c>';
                continue;
            }
            $text = $this->cellText((string) $value);
            $space = preg_match('/^\\s|\\s$/', $text) === 1 ? ' xml:space="preserve"' : '';
            $xml .= '<c r="' . $ref . '" t="inlineStr"><is><t' . $space . '>' . $this->xml($text) . '</t></is></c>';
        }
        return $xml . '</row>';
    }

    private function cellText(string $value): string
    {
        $value = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '', $value) ?? $value;
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 32767);
        }
        return substr($value, 0, 32767);
    }

    private function xml(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    public static function columnLetter(int $index): string
    {
        $letter = '';
        $n = $index + 1;
        while ($n > 0) {
            $n--;
            $letter = chr(65 + ($n % 26)) . $letter;
            $n = intdiv($n, 26);
        }
        return $letter;
    }
}
