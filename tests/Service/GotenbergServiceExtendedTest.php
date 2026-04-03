<?php

namespace App\Tests\Service;

use App\Service\GotenbergService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GotenbergServiceExtendedTest extends TestCase
{
    private function makeService(string $fakeContent, string &$capturedUrl = null): GotenbergService
    {
        $mockHttpClient = new MockHttpClient(function ($method, $url, $options) use ($fakeContent, &$capturedUrl) {
            $capturedUrl = $url;
            return new MockResponse($fakeContent, ['http_code' => 200]);
        });
        return new GotenbergService($mockHttpClient, 'http://gotenberg:3000');
    }

    public function testConvertMarkdownToPdfCallsCorrectEndpoint(): void
    {
        $capturedUrl = null;
        $service     = $this->makeService('%PDF fake', $capturedUrl);
        $result      = $service->convertMarkdownToPdf('# Hello');

        $this->assertEquals('%PDF fake', $result);
        $this->assertStringContainsString('/forms/chromium/convert/markdown', $capturedUrl);
    }

    public function testConvertOfficeToPdfCallsCorrectEndpoint(): void
    {
        $capturedUrl = null;
        $service     = $this->makeService('%PDF fake', $capturedUrl);
        $result      = $service->convertOfficeToPdf('fake docx content', 'document.docx');

        $this->assertEquals('%PDF fake', $result);
        $this->assertStringContainsString('/forms/libreoffice/convert', $capturedUrl);
    }

    public function testMergePdfsCallsCorrectEndpoint(): void
    {
        $capturedUrl = null;
        $service     = $this->makeService('%PDF merged', $capturedUrl);
        $result      = $service->mergePdfs(['%PDF 1', '%PDF 2']);

        $this->assertEquals('%PDF merged', $result);
        $this->assertStringContainsString('/forms/pdfengines/merge', $capturedUrl);
    }

    public function testScreenshotUrlCallsCorrectEndpoint(): void
    {
        $capturedUrl = null;
        $service     = $this->makeService("\x89PNG screenshot", $capturedUrl);
        $result      = $service->screenshotUrl('https://example.com');

        $this->assertEquals("\x89PNG screenshot", $result);
        $this->assertStringContainsString('/forms/chromium/screenshot/url', $capturedUrl);
    }

    public function testConvertMarkdownAcceptsEmptyContent(): void
    {
        $service = $this->makeService('%PDF empty');
        $result  = $service->convertMarkdownToPdf('');
        $this->assertEquals('%PDF empty', $result);
    }
}
