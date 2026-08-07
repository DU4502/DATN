<?php

namespace Tests\Unit;

use App\Support\SimpleXlsxWriter;
use Tests\TestCase;

class SimpleXlsxWriterTest extends TestCase
{
    public function test_writer_escapes_text_and_generates_unique_sheet_names(): void
    {
        $writer = new SimpleXlsxWriter();
        $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'simple-xlsx-writer-'.uniqid().'.xlsx';

        try {
            $writer->write($path, [
                [
                    'name' => 'Báo cáo / Tài:chính*?[]',
                    'rows' => [
                        ['=1+1', '+cmd', '-cmd', '@user', 123, '  giữ khoảng trắng  '],
                    ],
                ],
                [
                    'name' => 'Báo cáo / Tài:chính*?[]',
                    'rows' => [
                        ['Cột 1', 'Cột 2'],
                    ],
                ],
            ]);

            $this->assertFileExists($path);

            $workbookXml = file_get_contents('phar://'.$path.'/xl/workbook.xml');
            $sheet1Xml = file_get_contents('phar://'.$path.'/xl/worksheets/sheet1.xml');

            $this->assertIsString($workbookXml);
            $this->assertIsString($sheet1Xml);
            $this->assertStringContainsString('name="Báo cáo   Tài chính"', $workbookXml);
            $this->assertStringContainsString('name="Báo cáo   Tài chính (2)"', $workbookXml);
            $this->assertStringContainsString('<t>\'=1+1</t>', $sheet1Xml);
            $this->assertStringContainsString('<t>\'+cmd</t>', $sheet1Xml);
            $this->assertStringContainsString('<v>123</v>', $sheet1Xml);
            $this->assertStringContainsString('<t xml:space="preserve">  giữ khoảng trắng  </t>', $sheet1Xml);
            $this->assertStringNotContainsString('<f>', $sheet1Xml);
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
