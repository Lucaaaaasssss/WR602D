<?php

namespace App\Service;

use Symfony\Component\Mime\Part\DataPart;
use Symfony\Component\Mime\Part\Multipart\FormDataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GotenbergService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $gotenbergUrl,
    ) {
    }

    /**
     * Convertit du contenu HTML en PDF via Gotenberg
     */
    public function convertHtmlToPdf(string $htmlContent): string
    {
        $formData = new FormDataPart([
            'files' => new DataPart($htmlContent, 'index.html', 'text/html'),
        ]);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/convert/html', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }

    /**
     * Convertit du contenu HTML en PDF avec des options personnalisées
     */
    public function convertHtmlToPdfWithOptions(
        string $htmlContent,
        ?string $headerHtml = null,
        ?string $footerHtml = null,
        array $options = []
    ): string {
        $formFields = [
            'files' => new DataPart($htmlContent, 'index.html', 'text/html'),
        ];

        if ($headerHtml !== null) {
            $formFields['header'] = new DataPart($headerHtml, 'header.html', 'text/html');
        }

        if ($footerHtml !== null) {
            $formFields['footer'] = new DataPart($footerHtml, 'footer.html', 'text/html');
        }

        // Options supplémentaires (margins, landscape, etc.)
        foreach ($options as $key => $value) {
            $formFields[$key] = $value;
        }

        $formData = new FormDataPart($formFields);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/convert/html', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }

    /**
     * Convertit une URL en PDF
     */
    public function convertUrlToPdf(string $url): string
    {
        $formData = new FormDataPart([
            'url' => $url,
        ]);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/convert/url', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }

    /**
     * Convertit du Markdown en PDF (via rendu HTML côté Gotenberg)
     */
    public function convertMarkdownToPdf(string $markdownContent): string
    {
        // Gotenberg 8 markdown : index.html = wrapper avec {{ toHTML "document.md" }}
        $wrapper = <<<HTML
<!doctype html>
<html><head><meta charset="utf-8">
<style>body{font-family:sans-serif;margin:40px;line-height:1.6;}</style>
</head><body>{{ toHTML "document.md" }}</body></html>
HTML;

        $formData = new FormDataPart([
            'files' => [
                new DataPart($wrapper, 'index.html', 'text/html'),
                new DataPart($markdownContent, 'document.md', 'text/markdown'),
            ],
        ]);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/convert/markdown', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }

    /**
     * Convertit un fichier Office (Word, Excel, PowerPoint) en PDF via LibreOffice
     */
    public function convertOfficeToPdf(string $fileContent, string $filename): string
    {
        $formData = new FormDataPart([
            'files' => new DataPart($fileContent, $filename),
        ]);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/libreoffice/convert', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }

    /**
     * Fusionne plusieurs PDFs en un seul
     */
    public function mergePdfs(array $pdfContents): string
    {
        $fields = [];
        foreach ($pdfContents as $i => $content) {
            $fields[] = new DataPart($content, sprintf('%04d.pdf', $i), 'application/pdf');
        }

        $formData = new FormDataPart(['files' => $fields]);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/pdfengines/merge', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }

    /**
     * Capture d'écran d'une URL en PNG
     */
    public function screenshotUrl(string $url): string
    {
        $formData = new FormDataPart([
            'url' => $url,
        ]);

        $response = $this->httpClient->request('POST', $this->gotenbergUrl . '/forms/chromium/screenshot/url', [
            'headers' => $formData->getPreparedHeaders()->toArray(),
            'body' => $formData->bodyToIterable(),
        ]);

        return $response->getContent();
    }
}
