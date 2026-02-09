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
}
