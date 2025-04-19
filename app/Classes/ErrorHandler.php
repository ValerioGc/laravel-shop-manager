<?php
namespace App\Classes;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * This class handles errors in the project
 */
class ErrorHandler {
    /**
     * This method uses Laravel Log class to log errors
     *
     * @param string $method: most of the times the value of this parameters is __METHOD__ constant
     *
     * @param \Exception $e: the exception
     *
     * @return void
     */
    public static function logError(string $method, \Exception $e): void
    {
        Log::info($method);
        Log::info($e->getMessage());
        Log::info($e->getTraceAsString());
    }

    /**
     * This method handles Internal Server Error in API requests.
     * This is mostly used in the main "catch" block in the methods of the controllers
     *
     * @param string $method: most of the times the value of this parameters is __METHOD__ constant
     *
     * @param \Exception $e: the exception
     *
     * @return JsonResponse
     */
    public static function handleApiInternalServerError(string $method, \Exception $e)
    {
        self::logError($method, $e);
        return response()->json([
            "error" => "Errore generico"
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * This method handles Bad Request errors in API requests.
     * This method is mostly used after the failure of request input validation
     *
     * @param string $method: most of the times the value of this parameters is __METHOD__ constant
     *
     * @param string $error: A string representing the error (usually the first error in the validation)
     *
     * @return JsonResponse
     */
    public static function handleApiBadRequestError(string $method, string $error)
    {
        Log::info($method);
        Log::info("Bad request");
        return response()->json(["error" => $error], Response::HTTP_BAD_REQUEST);
    }

    /**
     * This method handles Unauthorized errors in API requests.
     * This method is used after authentication failures
     *
     * @param string $method: most of the times the value of this parameters is __METHOD__ constant
     *
     * @param string $error: A string representing the error
     *
     * @return JsonResponse
     */
    public static function handleApiUnauthorizedError(string $method, string $error)
    {
        return self::handleApiError($method, "Unauthorized", $error, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * This method handles Not Found errors in API requests.
     * This method is used everytime a resource is not found
     *
     * @param string $method: most of the times the value of this parameters is __METHOD__ constant
     *
     * @param string $error: A string representing the error
     *
     * @return JsonResponse
     */
    public static function handleApiNotFoundError(string $method, string $error)
    {
        return self::handleApiError($method, "Resource not found", $error, Response::HTTP_NOT_FOUND);
    }

    /**
     * This method handles general errors in API requests.
     *
     * @param string $method: most of the times the value of this parameters is __METHOD__ constant
     *
     * @param string $logMessage: the message that is logged
     *
     * @param string $error: A string representing the error
     *
     * @param string|int $statusCode: The status code of the response
     *
     * @return JsonResponse
     */
    public static function handleApiError(string $method, string $logMessage, string $error, string|int $statusCode = 200)
    {
        Log::info($method);
        Log::info($logMessage);
        return response()->json(["error" => $error], $statusCode);
    }
}