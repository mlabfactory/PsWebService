<?php

use PS\Webservice\Domain\ObjectInterface;
/**
 * This file is a helper file that contains various functions.
 */

if(!function_exists('config')) {
    function confing(string $key, string $value): string {
        return $_ENV[$key] ?? $value;
    }
}

if(!function_exists('response')) {
    function response(array|ObjectInterface $dataResponse, int $statusCode = 200, array $headers=[]): \Psr\Http\Message\ResponseInterface {
        $response = new \Slim\Psr7\Response();

        if($dataResponse instanceof ObjectInterface) {
            $dataResponse = $dataResponse->toArray();
        }

        $jsonData = json_encode($dataResponse);
        if ($jsonData === false) {
            $errorResponse = new \Slim\Psr7\Response();
            $errorResponse->getBody()->write('Errore nella codifica JSON dei dati');
            return $errorResponse->withStatus(500);
        }
        
        $response->getBody()->write(
            json_encode([
            "success" => $statusCode >= 200 && $statusCode < 300,
            "data" => $jsonData
        ],true)
        );

        foreach ($headers as $key => $value) {
            $response = $response->withHeader($key, $value);
        }
        
        return $response->withHeader('Content-Type', 'application/json')->withStatus($statusCode);
    }

    if(!function_exists('storage_path')) {
        function storage_path(string $path = ''): string {
            return __DIR__ . '/../storage/' . ltrim($path, '/');
        }
    }
}

// More functions...
