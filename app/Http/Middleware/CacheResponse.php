<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\CompressResponse;
use App\Http\Middleware\CacheControl;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Middleware to manage cache responses for GET requests and compress JSON data.
 * It will cache the response for 30 days and compress the JSON data using zlib.
 * The cache key is generated from the request path and query string. 
 * The cache is disabled in development and can be enabled/disabled with the CACHING_ENABLED environment variable.
 * The cache is also disabled for POST, PUT, DELETE, and PATCH requests.
 * The cache is also disabled for requests that do not start with 'api/public' when CACHE_PRIVATE_ENABLE is set to true.
 * @package App\Http\Middleware
 * @date 05/08/2024
 * @version 1.0
 * @see CompressResponse
 * @see CacheControl
 */
class CacheResponse
{
    protected $compressResponse;
    protected $cacheControl;

    public function __construct()
    {
        $this->compressResponse = new CompressResponse();
        $this->cacheControl = new CacheControl();
    }

    public function handle(Request $request, Closure $next)
    {
        $isProduction = env('APP_ENV') === 'production';
        $isCacheEnabled = env('CACHING_ENABLED', false);

        if (
            !$request->isMethod('get') ||
            (env('CACHE_PRIVATE_ENABLE', false) ? !str_starts_with($request->path(), 'api/public') : false) ||
            str_starts_with($request->path(), 'api/private/config')
        ) {
            return $next($request);
        }

        $path = $request->path();
        $queryParams = $request->getQueryString();
        if ($queryParams) {
            $path .= '?' . $queryParams;
        }

        $cacheKey = 'response_cache_' . $path;

        if ($isProduction && $isCacheEnabled) {

            if (Cache::has($cacheKey)) {
                $cachedResponse = Cache::get($cacheKey);
                Log::channel('cache')->info("----------------------------------------------------------------------");
                Log::channel('cache')->info('Cache hit', ['cacheKey' => $cacheKey]);

                $decompressedResponse = $this->safeDecompressJson($cachedResponse);

                if ($decompressedResponse === false) {
                    Log::channel('cache')->error('Decompression failed for cache key: ' . $cacheKey);
                    return $next($request);
                }

                Log::channel('cache')->info('Decompressed JSON data from cache', [
                    'before' => strlen($cachedResponse),
                    'sizeAfter' => strlen($decompressedResponse),
                ]);

                $response = response($decompressedResponse, 200)
                    ->header('Content-Type', 'application/json');

                $compressedResponse = $this->compressResponse->handle($request, function () use ($response) {
                    return $response;
                });

                if (str_starts_with($request->path(), 'api/public')) {
                    return $this->cacheControl->handle($request, function () use ($compressedResponse) {
                        return $compressedResponse;
                    });
                }

                return $compressedResponse;
            }

            $response = $next($request);

            if ($response instanceof StreamedResponse) {
                return $response;
            }

            if ($response->isSuccessful()) {
                $compressedContent = $this->compressJson($response->getContent());
                Cache::put($cacheKey, $compressedContent, now()->addDays(30));

                Log::channel('cache')->info('Saving compressed JSON to cache', ['cacheKey' => $cacheKey]);
            }

            $compressedResponse = $this->compressResponse->handle($request, function () use ($response) {
                return $response;
            });

            if (str_starts_with($request->path(), 'api/public')) {
                return $this->cacheControl->handle($request, function () use ($compressedResponse) {
                    return $compressedResponse;
                });
            }

            return $compressedResponse;
        }

        Log::channel('cache')->info("----------------------------------------------------------------------");
        Log::channel('cache')->info('Caching disabled', [
            'isProduction' => $isProduction,
            'isCacheEnabled' => $isCacheEnabled,
        ]);

        $response = $next($request);

        if ($response instanceof StreamedResponse) {
            return $response;
        }

        $compressedResponse = $this->compressResponse->handle($request, function () use ($response) {
            return $response;
        });

        if (str_starts_with($request->path(), 'api/public')) {
            return $this->cacheControl->handle($request, function () use ($compressedResponse) {
                return $compressedResponse;
            });
        }

        return $compressedResponse;
    }

    /**
     * Compress JSON data using zlib compression.
     *
     * @param string $json
     * @return string
     */
    private function compressJson($json)
    {
        return base64_encode(zlib_encode($json, ZLIB_ENCODING_DEFLATE));
    }

    /**
     * Decompress JSON data with error handling.
     *
     * @param string $compressedData
     * @return string|false
     */
    private function safeDecompressJson($compressedData)
    {
        $decodedData = base64_decode($compressedData, true);

        if ($decodedData === false) {
            return false;
        }

        $decompressedData = @zlib_decode($decodedData);

        if ($decompressedData === false) {
            return false;
        }

        return $decompressedData;
    }
}