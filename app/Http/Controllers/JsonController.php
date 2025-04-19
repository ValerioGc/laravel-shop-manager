<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Utils\ConvertImageUtils;

use Exception;

/**
 * API Microservices controller for JSON configuration of the frontend website
 * @package App\Http\Controllers
 * @uses fe_condition.json
 * @date 05/08/2024
 * @version 1.0
 */
class JsonController extends Controller
{
    protected $loggingEnabled;

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('config');
        $this->loggingEnabled = env('LOG_CONFIG', $this->loggingEnabled);
    }

    /**
     * Read the JSON configuration file for the frontend website (and bo admin panel)
     * @return Response Json configuration
     */
    public function readJson()
    {
        $path = storage_path() . "/fe_config.json";
        if (!File::exists($path)) {
            return response()->json(['error' => 'Configurazione non trovata'], Response::HTTP_NOT_FOUND);
        }

        try {
            $json = File::get($path);
            $data = json_decode($json, true);

            $prettyJson = json_encode($data, JSON_PRETTY_PRINT);
            return response($prettyJson)->header('Content-Type', 'application/json');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Errore lettura configurazione file.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Edit the existing JSON configuration file for the frontend website
     * @param Request $request
     * @return Response
     */
    public function writeJson(Request $request)
    {
        
        $this->logMethodAndUri($request);
        $this->log('info', 'Edit configuration file');

        $path = storage_path() . "/fe_config.json";
        if (!File::exists($path)) {
            $this->log('error', 'Edit configuration file - Config not found', ['path' => $path]);
            return response()->json(['error' => 'Configurazione non trovata'], Response::HTTP_NOT_FOUND);
        }

        try {
            $currentData = json_decode(File::get($path), true);
            $newData = json_decode($request->input('config'), true);

            if ($request->hasFile('bannerImg')) {
                if (isset($currentData['settings']['bannerImg'])) {
                    $oldImagePath = str_replace(url('/storage'), '', $currentData['settings']['bannerImg']);
                    Storage::disk('public')->delete($oldImagePath);
                }

                try {
                    $image = ConvertImageUtils::processSingleImage($request, 'bannerImg', 'images/banners');
                    ConvertImageUtils::resizeImage(
                        $image->path,
                        env('BANNER_IMAGE_MAX_WIDTH', 1920),
                        env('BANNER_IMAGE_MAX_HEIGHT', 1080)
                    );

                    $newData['settings']['bannerImg'] = Storage::url($image->path);
                } catch (Exception $e) {
                    $this->log('error', 'Error processing image: ', ['exception' => $e]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante la elaborazione dell\'immagine'
                    ], 500);
                }
            }

            if ($request->hasFile('showPageImg')) {
                if (isset($currentData['settings']['showPageImg'])) {
                    $oldImagePath = str_replace(url('/storage'), '', $currentData['settings']['showPageImg']);
                    Storage::disk('public')->delete($oldImagePath);
                }

                try {
                    $image = ConvertImageUtils::processSingleImage($request, 'showPageImg', 'images/banners');

                    ConvertImageUtils::resizeImage(
                        $image->path,
                        env('SHOWPAGE_IMAGE_MAX_WIDTH', 1920),
                        env('SHOWPAGE_IMAGE_MAX_HEIGHT', 1080)
                    );

                    $newData['settings']['showPageImg'] = Storage::url($image->path);
                } catch (Exception $e) {
                    $this->log('error', 'Error processing image: ', ['exception' => $e]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante la elaborazione dell\'immagine'
                    ], 500);
                }
            }

            $updatedData = $this->arrayRecursiveMerge($currentData, $newData);

            // Minify JSON
            $minifiedJson = json_encode($updatedData);
            File::put($path, $minifiedJson);

            try {
                $this->moveConfigFile();
            } catch (\Exception $e) {
                $this->log('error', 'Edit configuration file error: ', ['exception' => $e]);
                return response()->json(['error' => 'Errore spostamento file configurazione.'], Response::HTTP_INTERNAL_SERVER_ERROR);
            } 

            return response()->json($updatedData);
        } catch (\Exception $e) {
            $this->log('error', 'Edit configuration file error: ', ['exception' => $e]);
            return response()->json(['error' => 'Errore aggiornamento configurazione.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        } finally {
            $this->clearEntityCache('config');
            $this->log('info', 'Edit configuration file completed - Invalidating stored cache');
        }
    }

    public function moveConfigFile()
    {
        $enviroment = env('APP_ENV', 'test');
        $this->log('info', 'Move configuration file to frontend directory', ['enviroment' => $enviroment]);
        
        if (($enviroment) === 'development'){
            return;
        }  
        
        $fileName = '/fe_config.json';
        $baseSource = '/data/vhosts/shop.com/';
        $env = '';
        if ($enviroment == 'test'){
            $env = '-' . $enviroment;
        }

        $bePath =  'service' . $env . '.shop.com/storage';

        $fePath = null;
        if($enviroment === 'test') {
            $fePath =  'test.shop.com';
        }   else {
            $fePath =  'httpdocs';
        }

        $beSource = $baseSource . $bePath . $fileName;
        $this->log('info', 'beSource', ['source' => $beSource]);

        $destination = $baseSource . $fePath . $fileName;
        $this->log('info', 'feSource', ['destination' => $destination]);

        if ($fePath == null || $bePath == null) {
            return response()->json(['error' => 'Error moving file, path not found'], 500);       
        }
            
        $this->log('info', 'Move configuration file to frontend directory', ['source' => $beSource, 'destination' => $destination]);
        
        if (file_exists($beSource)) {
            if (copy($beSource, $destination)) {
                return response()->json(['message' => 'File moved successfully'], 200);
            } else {
                return response()->json(['error' => 'Error moving file'], 500);
            }
        } else {
            return response()->json(['error' => 'Source file not found'], 404);
        }
    }

    /**
     * Merge two arrays recursively - used to update the JSON configuration file
     * @param array $original - Current configuration
     * @param array $new - New configuration
     * @return array
     */
    private function arrayRecursiveMerge($original, $new)
    {
        foreach ($new as $key => $value) {
            if (array_key_exists($key, $original) && is_array($value)) {
                $original[$key] = $this->arrayRecursiveMerge($original[$key], $new[$key]);
            } else {
                $original[$key] = $value;
            }
        }
        return $original;
    }
}
