<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TranslateController extends Controller
{
    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('shows');
        $this->loggingEnabled = env('LOG_SHOW', $this->loggingEnabled);
    }

    /**
     * Translate text from Italian to English using Google Translate API
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function translateText(Request $request)
    {
        $this->logMethodAndUri($request);
        $text = $request->input('q');

        $apiUrl = 'https://translation.googleapis.com/language/translate/v2';
        try  {
            $apiKey = config('services.google_translate.api_key');

            $queryParams = http_build_query([
                'q' => $text,
                'source' => 'it',
                'target' => 'en',
                'format' => 'text',
                'key' => $apiKey,
            ]);

            $fullUrl = $apiUrl . '?' . $queryParams;

            $response = Http::withHeaders([
                'Content-Type' => 'application/x-www-form-urlencoded',
            ])->send('POST', $fullUrl, [
                'body' => ''
            ]); 

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json(['error' => 'Errore nella traduzione'], $response->status());
            }
        } catch (\Exception $e) {
            $this->log('error', $e);
            return response()->json(['error' => 'Errore nella traduzione'], 500);
        }
    }
}

