<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Response;
use RuntimeException;
use Symfony\Component\DomCrawler\Crawler;

class ImageGrabbingService
{
    public const CONCURRENT_REQUEST_QTY = 8;
    
    /**
     * @throws RuntimeException
     */
    public function processImages(string $baseUrl): array
    {
        $html = file_get_contents($baseUrl);
        if (false === $html) {
            throw new RuntimeException('Не могу открыть введенный URL');
        }
        $imageNodes = $this->getImageNodes($html);
        
        $imagePaths = [];
        foreach ($imageNodes as $imageNode) {
            $src = $imageNode->getAttribute('src');
            if ($this->isExternalImage($src)) {
                continue;
            }
            $imagePaths[] = $src;
        }
        
        $imagePaths = $this->cleanUp($imagePaths);
        
        $imagesSize = $this->getImageSizes($baseUrl, $imagePaths);
        $absoluteImageUris = array_map(static function (string $path) use ($baseUrl) {
            return sprintf('%s%s', $baseUrl, $path);
        }, $imagePaths);
        
        return [$absoluteImageUris, $imagesSize];
    }
    
    private function isExternalImage(string $src): bool
    {
        return $this->isAbsoluteUrl($src);
    }
    
    private function isAbsoluteUrl(string $url): bool
    {
        $parsed = parse_url($url);
        return isset($parsed['scheme'], $parsed['host']);
    }
    
    private function cleanUp(array $imagePaths): array
    {
        $imagePaths = array_unique($imagePaths);
        return array_filter($imagePaths, static function (string $path) {
            return !empty($path);
        });
    }
    
    private function getImageSizes(string $baseUrl, array $imagePaths): int
    {
        $imageSizes = [];
        $client = new Client(['base_uri' => $baseUrl]);
        $requests = static function ($paths) use ($client) {
            foreach ($paths as $path) {
                yield static function () use ($client, $path) {
                    return $client->getAsync($path);
                };
            }
        };
        $pool = new Pool($client, $requests($imagePaths), [
            'concurrency' => self::CONCURRENT_REQUEST_QTY,
            'fulfilled' => function (Response $response, $index) use (&$imageSizes) {
                $imageSize = (int)($response->getHeader('Content-Length')[0] ?? 0);
                $imageSizes[] = $imageSize;
            }
        ]);
        $promise = $pool->promise();
        $promise->wait();
        return array_sum($imageSizes);
    }
    
    /**
     * @param string $html
     * @return Crawler
     */
    public function getImageNodes(string $html): Crawler
    {
        return (new Crawler($html))->filter('img');
    }
}
