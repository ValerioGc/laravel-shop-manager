<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;

/**
 * Base controller for all controllers in the application
 * @package App\Http\Controllers
 * @date 05/08/2024
 * @version 1.0
 */
abstract class Controller
{
    protected $loggingEnabled;
    protected $logChannel;

    /**
     * Controller constructor.
     */
    public function __construct($logChannel = 'default')
    {
        $this->loggingEnabled = env('LOG_GLOBAL', true); // Default logging, overridden in specific controllers
        $this->logChannel = $logChannel;
    }

    /**
     * Log a message to the specified channel
     * @param string $level - The log level
     * @param string $message - The message to log
     * @param array $context - Additional context data
     */
    protected function log($level, $message, $context = [])
    {
        if ($this->loggingEnabled) {
            Log::channel($this->logChannel)->log($level, $message, $context);
        }
    }

    /**
     * Log text divider and the method and URI of the request
     * @param Request $request
     */
    protected function logMethodAndUri(Request $request)
    {
        if ($this->loggingEnabled) {
            $route = $request->route();
            $methodName = $route ? $route->getActionMethod() : 'Unknown';
            $uri = $request->path();
            $this->log('info', "--------------------------------------------------");
            $this->log('info', "Method: $methodName | URI: $uri");
        }
    }

    /**
     * Clear cache entries related to a specific entity when using database driver.
     * @param string $entity - The entity name (e.g., 'show', 'product')
     * @param int|string $id - The ID of the entity
     */
    protected function clearEntityCache($entity, $id = null)
    {
        $cacheKeyPublicPrefix = 'response_cache_api/public/' . $entity;
        $cacheKeyPrivatePrefix = 'response_cache_api/private/' . $entity;

        if ($id !== null && $id !== '') {
            $cacheKeyPublicPrefix .= '/get/' . $id;
            $cacheKeyPrivatePrefix .= '/get/' . $id;
        }

        $cacheKeyPublicPrefixWithoutParams = 'response_cache_api/public/' . $entity . '/paginate';
        $cacheKeyPrivatePrefixWithoutParams = 'response_cache_api/private/' . $entity . '/paginate';

        $table = env('DB_CACHE_TABLE', 'cache');

        try {
            $keys = DB::table($table)
                ->where(function ($query) use (
                    $cacheKeyPublicPrefix, 
                    $cacheKeyPrivatePrefix, 
                    $cacheKeyPublicPrefixWithoutParams, 
                    $cacheKeyPrivatePrefixWithoutParams,
                    $entity
                ) {
                    $query->where('key', 'like', $cacheKeyPublicPrefix . '%')
                        ->orWhere('key', 'like', 'response_cache_api/public/' . $entity . '%')
                        ->orWhere('key', 'like', $cacheKeyPrivatePrefix . '%')
                        ->orWhere('key', 'like', 'response_cache_api/private/' . $entity . '%')
                        // Search also keys of paginable routes, ignoring query parameters
                        ->orWhere('key', 'like', $cacheKeyPublicPrefixWithoutParams . '%')
                        ->orWhere('key', 'like', $cacheKeyPrivatePrefixWithoutParams . '%');
                })
                ->pluck('key');

            if ($keys->isEmpty()) {
                $this->log('info', "No cache entries found for entity $entity with ID $id");
                return;
            }

            foreach ($keys as $key) {
                Cache::forget($key);
                $this->log('info', "Cache cleared for key: $key");
            }
        } catch (\Exception $e) {
            $this->log('error', "Error clearing cache for entity $entity with ID $id: " . $e->getMessage());
        }
    }

}
