<?php
declare(strict_types=1);

namespace DolzeZampa\WS\Http\Middleware;

use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;

class CachingMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri()->getPath();

        if (Cache::has($uri)) {
            $cachedValue = Cache::get($uri);

            if (is_array($cachedValue)) {
                return response($cachedValue);
            }

            if (is_string($cachedValue)) {
                $decoded = json_decode($cachedValue, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    return response($decoded);
                }
            }

            // Fallback sicuro: mantiene la firma di response(array ...)
            return response(['data' => $cachedValue]);
        }

        $response = $handler->handle($request);
        Cache::put($uri, $response->getBody()->__toString(), 3600); // Cache for 1 hour



        return $response;
    }
}