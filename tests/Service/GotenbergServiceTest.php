<?php

namespace App\Tests\Service;

use App\Service\GotenbergService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class GotenbergServiceTest extends TestCase
{
    public function testConvertHtmlToPdf(): void
    {
        // Simuler une réponse PDF (contenu binaire factice)
        $fakePdfContent = '%PDF-1.4 fake pdf content';

        $mockResponse = new MockResponse($fakePdfContent, [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/pdf'],
        ]);

        $mockHttpClient = new MockHttpClient($mockResponse);

        $gotenbergService = new GotenbergService($mockHttpClient, 'http://localhost:3000');

        $htmlContent = '<html><body><h1>Test</h1></body></html>';
        $result = $gotenbergService->convertHtmlToPdf($htmlContent);

        $this->assertEquals($fakePdfContent, $result);
    }

    public function testConvertHtmlToPdfWithOptions(): void
    {
        $fakePdfContent = '%PDF-1.4 fake pdf with options';

        $mockResponse = new MockResponse($fakePdfContent, [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/pdf'],
        ]);

        $mockHttpClient = new MockHttpClient($mockResponse);

        $gotenbergService = new GotenbergService($mockHttpClient, 'http://localhost:3000');

        $htmlContent = '<html><body><h1>Test</h1></body></html>';
        $headerHtml = '<html><body><p>Header</p></body></html>';
        $footerHtml = '<html><body><p>Footer</p></body></html>';

        $result = $gotenbergService->convertHtmlToPdfWithOptions(
            $htmlContent,
            $headerHtml,
            $footerHtml,
            ['landscape' => 'true']
        );

        $this->assertEquals($fakePdfContent, $result);
    }

    public function testConvertUrlToPdf(): void
    {
        $fakePdfContent = '%PDF-1.4 fake url pdf';

        $mockResponse = new MockResponse($fakePdfContent, [
            'http_code' => 200,
            'response_headers' => ['Content-Type' => 'application/pdf'],
        ]);

        $mockHttpClient = new MockHttpClient($mockResponse);

        $gotenbergService = new GotenbergService($mockHttpClient, 'http://localhost:3000');

        $result = $gotenbergService->convertUrlToPdf('https://example.com');

        $this->assertEquals($fakePdfContent, $result);
    }

    public function testConvertHtmlToPdfVerifiesEndpoint(): void
    {
        $requestHistory = [];

        $mockResponse = new MockResponse('%PDF-1.4', [
            'http_code' => 200,
        ]);

        $mockHttpClient = new MockHttpClient(function ($method, $url, $options) use (&$requestHistory, $mockResponse) {
            $requestHistory[] = [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ];
            return $mockResponse;
        });

        $gotenbergUrl = 'http://gotenberg:3000';
        $gotenbergService = new GotenbergService($mockHttpClient, $gotenbergUrl);

        $gotenbergService->convertHtmlToPdf('<html><body>Test</body></html>');

        $this->assertCount(1, $requestHistory);
        $this->assertEquals('POST', $requestHistory[0]['method']);
        $this->assertEquals($gotenbergUrl . '/forms/chromium/convert/html', $requestHistory[0]['url']);
    }

    public function testConvertUrlToPdfVerifiesEndpoint(): void
    {
        $requestHistory = [];

        $mockResponse = new MockResponse('%PDF-1.4', [
            'http_code' => 200,
        ]);

        $mockHttpClient = new MockHttpClient(function ($method, $url, $options) use (&$requestHistory, $mockResponse) {
            $requestHistory[] = [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ];
            return $mockResponse;
        });

        $gotenbergUrl = 'http://gotenberg:3000';
        $gotenbergService = new GotenbergService($mockHttpClient, $gotenbergUrl);

        $gotenbergService->convertUrlToPdf('https://example.com');

        $this->assertCount(1, $requestHistory);
        $this->assertEquals('POST', $requestHistory[0]['method']);
        $this->assertEquals($gotenbergUrl . '/forms/chromium/convert/url', $requestHistory[0]['url']);
    }
}
