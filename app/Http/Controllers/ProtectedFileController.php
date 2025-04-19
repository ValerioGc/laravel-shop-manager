<?php

namespace App\Http\Controllers;

use App\Classes\ErrorHandler;
use Illuminate\Support\Facades\Storage;

/**
 * Controller for protected files
 * @package App\Http\Controllers
 * @date 05/08/2024
 * @version 1.0
 */
class ProtectedFileController extends Controller
{

    /**
     * Show the image file from the protected storage
     * @param string $filename
     */
    public function show($filename)
    {
        try{            
                $disk = Storage::disk('public');
                $exists = $disk->exists($filename);
        
                if (!$exists) {
                    abort(404, 'File not found');
                }
        
                $file = $disk->get($filename);
                $mimeType = $disk->mimeType($filename);
        
                return response($file, 200)->header('Content-Type', $mimeType);
            
        }catch(\Exception $e){
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }
}
