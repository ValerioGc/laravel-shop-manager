<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


/**
 * Middleware for Cache-Control headers
 * It sets the Cache-Control header for the API responses and checks the ETag
 * For the public API routes, it sets the Cache-Control header to public and max-age=21600 (6 hours)
 * On endpoint /api/public/faq, /api/public/contact and /api/public/specific
 * @package App\Http\Middleware
 * @date 11/08/2024
 * @version 1.0
 * @param \Illuminate\Http\Request $request
 * @param \Closure $next
 * @return mixed
 */
class CacheControl
{
    public function handle(Request $request, Closure $next)
    {
        try {

            Log::channel('cache')->info('Adding Etag 304 for caching', [
                'path' => $request->path(),
            ]);

            $response = $next($request);

            if ($request->is('api/public/faq')
                || $request->is('api/public/contact') 
                || $request->is('api/public/specific') 
            ) {

                $response->headers->set('Cache-Control', 'public, max-age=21600'); // 6 hours

                $etag = md5($response->getContent());
                $response->headers->set('ETag', $etag);

                if ($request->headers->has('If-None-Match') && $request->header('If-None-Match') === $etag) {
                    Log::channel('cache')->info('ETag match, returning 304', ['etag' => $etag]);
                    $response->setStatusCode(304);
                    $response->setContent(null);
                }
            } 
        } catch (\Exception $e) {
            Log::channel('cache')->error('CacheControl middleware error: ', [
                'path' => $request->path(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
        return $response;
    }
}
