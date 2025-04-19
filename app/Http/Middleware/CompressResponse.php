<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;

/**
 * Middleware for compressing JSON responses
 * It compresses the JSON responses using Gzip
 * @package App\Http\Middleware
 * @date 11/08/2024
 * @version 1.0
 * @param \Illuminate\Http\Request $request
 * @param \Closure $next
 * @return mixed
 */
class CompressResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->is('api/auth/*')) { // exclude auth routes
            return $response;
        }

        if ($response->headers->has('Content-Encoding') && $response->headers->get('Content-Encoding') === 'gzip') {
            Log::channel('cache')->info('Response already compressed, skipping compression');
            return $response; // response already compressed
        }

        if ($request->is('api/*') && $response->headers->get('Content-Type') === 'application/json') {

            try {
                $content = $response->getContent();

                $encoding = $request->header('Accept-Encoding');
                if (strpos($encoding, 'gzip') !== false) {

                    if (function_exists('gzencode')) {
                        $compressedContent = gzencode($content);
                        $compressedSize = strlen($compressedContent);

                        Log::channel('cache')->info('Compressing response with gzip', [
                            'original_size' => strlen($content),
                            'compressed_size' => $compressedSize
                        ]);

                        return new StreamedResponse(function () use ($compressedContent) {
                            echo $compressedContent;
                        }, $response->getStatusCode(), [
                            'Content-Encoding' => 'gzip',
                            'Content-Type' => 'application/json',
                            'Content-Length' => $compressedSize
                        ]);
                    } else {
                        Log::channel('cache')->error('Gzip compression not available');
                    }
                }
            } catch (\Exception $e) {
                Log::channel('cache')->error('Compress Response middleware error: ', [
                    'path' => $request->path(),
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
        return $response;
    }
}
