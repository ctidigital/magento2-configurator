<?php
declare(strict_types=1);

namespace CtiDigital\Configurator\Test\Unit\Service;

use CtiDigital\Configurator\Exception\ComponentException;
use CtiDigital\Configurator\Service\SourceDataParser;
use PHPUnit\Framework\TestCase;

/**
 * @covers \CtiDigital\Configurator\Service\SourceDataParser
 */
class SourceDataParserTest extends TestCase
{
    private SourceDataParser $parser;

    /** Temp directory used as a fake Magento base path (BP). */
    private string $tempDir;

    protected function setUp(): void
    {
        $this->parser  = new SourceDataParser();
        $this->tempDir = BP; // set to sys_get_temp_dir() in bootstrap.php
    }

    // ── isSourceRemote ────────────────────────────────────────────────────────

    public function testIsSourceRemoteReturnsTrueForHttpUrl(): void
    {
        $this->assertTrue($this->parser->isSourceRemote('https://example.com/data.yaml'));
    }

    public function testIsSourceRemoteReturnsTrueForFileUri(): void
    {
        $this->assertTrue($this->parser->isSourceRemote('file:///tmp/test.yaml'));
    }

    public function testIsSourceRemoteReturnsFalseForLocalPath(): void
    {
        $this->assertFalse($this->parser->isSourceRemote('path/to/file.yaml'));
    }

    public function testIsSourceRemoteReturnsFalseForEmptyString(): void
    {
        $this->assertFalse($this->parser->isSourceRemote(''));
    }

    // ── parse: missing local file ─────────────────────────────────────────────

    public function testParseThrowsComponentExceptionForMissingLocalFile(): void
    {
        $this->expectException(ComponentException::class);
        $this->expectExceptionMessageMatches('/Could not find file/');

        $this->parser->parse('definitely/does/not/exist.yaml', null);
    }

    // ── parse: YAML ───────────────────────────────────────────────────────────

    public function testParseYamlReturnsArray(): void
    {
        $file = $this->tempDir . '/test_source.yaml';
        file_put_contents($file, "key: value\nfoo: bar\n");

        try {
            $result = $this->parser->parse('test_source.yaml', 'yaml');
        } finally {
            unlink($file);
        }

        $this->assertIsArray($result);
        $this->assertEquals('value', $result['key']);
        $this->assertEquals('bar', $result['foo']);
    }

    // ── parse: JSON ───────────────────────────────────────────────────────────

    public function testParseJsonReturnsObject(): void
    {
        $file = $this->tempDir . '/test_source.json';
        file_put_contents($file, '{"key":"value","num":42}');

        try {
            $result = $this->parser->parse('test_source.json', 'json');
        } finally {
            unlink($file);
        }

        $this->assertEquals('value', $result->key);
        $this->assertEquals(42, $result->num);
    }

    // ── parse: CSV ────────────────────────────────────────────────────────────

    public function testParseCsvReturnsTwoDimensionalArray(): void
    {
        $file = $this->tempDir . '/test_source.csv';
        file_put_contents($file, "name,email\nAlice,alice@example.com\nBob,bob@example.com\n");

        try {
            $result = $this->parser->parse('test_source.csv', 'csv');
        } finally {
            unlink($file);
        }

        // First row is the header row
        $this->assertEquals(['name', 'email'], $result[0]);
        // Second row maps header indexes to values
        $this->assertEquals('Alice', $result[1][0]);
        $this->assertEquals('alice@example.com', $result[1][1]);
        $this->assertEquals('Bob', $result[2][0]);
        $this->assertEquals('bob@example.com', $result[2][1]);
    }

    // ── parse: auto-detect extension ─────────────────────────────────────────

    public function testParseAutoDetectsYamlExtension(): void
    {
        $file = $this->tempDir . '/autodetect.yaml';
        file_put_contents($file, "detected: true\n");

        try {
            $result = $this->parser->parse('autodetect.yaml', null);
        } finally {
            unlink($file);
        }

        $this->assertTrue($result['detected']);
    }

    public function testParseThrowsForUnknownExtension(): void
    {
        $file = $this->tempDir . '/source.xml';
        file_put_contents($file, '<root/>');

        $this->expectException(ComponentException::class);
        $this->expectExceptionMessageMatches('/valid file extension/');

        try {
            $this->parser->parse('source.xml', null);
        } finally {
            unlink($file);
        }
    }

    // ── getRemoteData ─────────────────────────────────────────────────────────
    //
    // file:// URIs are treated as remote by isSourceRemote() (FILTER_VALIDATE_URL
    // accepts them) and are supported by PHP stream wrappers, letting us exercise
    // the remote code path without making real HTTP requests.

    public function testGetRemoteDataFetchesContentViaFileUri(): void
    {
        $file = $this->tempDir . '/remote_fetch_' . uniqid() . '.txt';
        file_put_contents($file, 'remote content');

        try {
            $result = $this->parser->getRemoteData('file://' . $file);
        } finally {
            unlink($file);
        }

        $this->assertSame('remote content', $result);
    }

    // ── parse: remote YAML (file:// URI) ─────────────────────────────────────
    //
    // sourceType must be supplied explicitly: auto-detection calls get_headers()
    // which does not work for file:// URIs, causing getRemoteContentExtension()
    // to return '' and a "valid file extension" exception to be thrown.

    public function testParseRemoteYamlViaFileUri(): void
    {
        $file = $this->tempDir . '/remote_yaml_' . uniqid() . '.yaml';
        file_put_contents($file, "remote_key: remote_value\n");

        try {
            $result = $this->parser->parse('file://' . $file, 'yaml');
        } finally {
            unlink($file);
        }

        $this->assertIsArray($result);
        $this->assertSame('remote_value', $result['remote_key']);
    }

    // ── parse: remote CSV (file:// URI) ──────────────────────────────────────

    public function testParseRemoteCsvViaFileUri(): void
    {
        $file = $this->tempDir . '/remote_csv_' . uniqid() . '.csv';
        file_put_contents($file, "col_a,col_b\nfoo,bar\n");

        try {
            $result = $this->parser->parse('file://' . $file, 'csv');
        } finally {
            unlink($file);
        }

        $this->assertEquals(['col_a', 'col_b'], $result[0]);
        $this->assertEquals('foo', $result[1][0]);
        $this->assertEquals('bar', $result[1][1]);
    }

    // ── parse: remote JSON (file:// URI) ─────────────────────────────────────

    public function testParseRemoteJsonViaFileUri(): void
    {
        $file = $this->tempDir . '/remote_json_' . uniqid() . '.json';
        file_put_contents($file, '{"remote":true,"count":3}');

        try {
            $result = $this->parser->parse('file://' . $file, 'json');
        } finally {
            unlink($file);
        }

        $this->assertTrue($result->remote);
        $this->assertSame(3, $result->count);
    }
}
