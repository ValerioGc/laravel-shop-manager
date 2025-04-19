<?php

namespace App\Utils;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\Image;
use Illuminate\Support\Facades\Log;
use Exception;


/**
 * Utility class to handle image conversion, resizing, thumbnail creation, and watermarking.
 */
class ConvertImageUtils
{

    /**
     * Process a single image, convert it to WebP, and handle direct saving for unsupported formats (e.g., SVG, WebP).
     * 
     * @param Request $request The HTTP request containing the image file.
     * @param string $inputName The name of the input field containing the image.
     * @param string $storagePath The path to store the image.
     * @return Image The image model instance with path information.
     */
    public static function processSingleImage(Request $request, $inputName, $storagePath)
    {
        $file = $request->file($inputName);
        $labelItaSanitized = Str::slug($request->label_ita);
        $timestamp = now()->timestamp;
        $randomString = Str::random(6);
        $extension = strtolower($file->getClientOriginalExtension());
        $compressionRatio = env('IMAGE_COMPRESSION_RATIO', 75);

        Log::info('Processing single image', ['inputName' => $inputName, 'extension' => $extension]);

        // No conversion or resize
        if ($extension === 'webp' || $extension === 'svg') {
            $fileName = "{$labelItaSanitized}_{$timestamp}_{$randomString}.{$extension}";
            $imagePath = $file->storeAs($storagePath, $fileName, 'public');
            Log::info('Image saved directly', ['imagePath' => $imagePath]);
            return Image::create(['path' => $imagePath]);
        }

        // Convert to WebP for JPEG/JPG/PNG
        $fileName = "{$labelItaSanitized}_{$timestamp}_{$randomString}.webp";
        $imagePath = "{$storagePath}/{$fileName}";
        $image = null;

        try {
            switch ($extension) {
                case 'jpeg':
                case 'jpg':
                    $image = imagecreatefromjpeg($file->getPathname());
                    break;
                case 'png':
                    $image = imagecreatefrompng($file->getPathname());
                    break;
                default:
                    throw new Exception('Unsupported image type: ' . $extension);
            }

            ob_start();
            imagewebp($image, null, $compressionRatio);
            $webpContent = ob_get_clean();

            Storage::disk('public')->put($imagePath, $webpContent);
            imagedestroy($image);

            Log::info('Image converted and saved as WebP', ['imagePath' => $imagePath]);
            return Image::create(['path' => $imagePath]);
        } catch (Exception $e) {
            Log::error('Error processing image', ['exception' => $e]);
            throw $e;
        }
    }

    /**
     * Resize the given image if its dimensions exceed the maximum width or height.
     * Correct the orientation based on EXIF metadata if applicable.
     * 
     * @param string $imagePath The path to the image.
     * @param int $maxWidth The maximum allowed width of the image.
     * @param int $maxHeight The maximum allowed height of the image.
     */
    public static function resizeImage($imagePath, $maxWidth, $maxHeight)
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));

        // Salta solo i file SVG
        if ($extension === 'svg') {
            Log::info('Skipping resize for unsupported formats: ' . $extension);
            return;
        }

        if (!in_array($extension, ['jpeg', 'jpg', 'png', 'webp'])) {
            Log::error('Unsupported image format for resizing: ' . $extension);
            throw new Exception('Unsupported image format for resizing: ' . $extension);
        }

        $imageFullPath = Storage::disk('public')->path($imagePath);

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($imageFullPath);

                if (function_exists('exif_read_data')) {
                    $exif = @exif_read_data($imageFullPath);
                    if ($exif && isset($exif['Orientation'])) {
                        $image = self::correctOrientation($image, $exif['Orientation']);
                    }
                }
                break;
            case 'png':
                $image = imagecreatefrompng($imageFullPath);
                break;
            case 'webp':
                $image = imagecreatefromwebp($imageFullPath);
                break;
            default:
                Log::error('Unsupported image format for resizing: ' . $extension);
                return;
        }

        $width = imagesx($image);
        $height = imagesy($image);

        if ($width > $maxWidth || $height > $maxHeight) {
            $widthScale = $maxWidth / $width;
            $heightScale = $maxHeight / $height;
            $scale = min($widthScale, $heightScale);
            
            $newWidth = intval($width * $scale);
            $newHeight = intval($height * $scale);

            $resizedImage = imagescale($image, $newWidth, $newHeight);

            ob_start();
            imagewebp($resizedImage, null, env('IMAGE_COMPRESSION_RATIO', 75));
            $resizedContent = ob_get_clean();

            Storage::disk('public')->put($imagePath, $resizedContent);
            imagedestroy($resizedImage);
        }

        imagedestroy($image);
    }

    /**
     * Corregge l'orientamento di un'immagine basandosi sui metadati EXIF.
     *
     * @param resource $image L'immagine da correggere.
     * @param int $orientation Il valore dell'orientamento dai metadati EXIF.
     * @return resource L'immagine con l'orientamento corretto.
     */
    private static function correctOrientation($image, $orientation)
    {
        switch ($orientation) {
            case 3: 
                $image = imagerotate($image, 180, 0);
                break;
            case 6: 
                $image = imagerotate($image, -90, 0);
                break;
            case 8: 
                $image = imagerotate($image, 90, 0);
                break;
        }

        return $image;
    }

    /**
     * Create a thumbnail for the image, maintaining aspect ratio, and saving it in a specific folder.
     * 
     * @param string $imagePath The path to the original image.
     * @param int $thumbnailWidth The maximum width for the thumbnail.
     * @param int $thumbnailHeight The maximum height for the thumbnail.
     * @param string $thumbnailFolder The folder where thumbnails will be stored.
     * @return string|null The path of the created thumbnail or null if creation fails.
     */
    public static function createThumbnail($imagePath, $thumbnailWidth, $thumbnailHeight, $thumbnailFolder)
    {
        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)); 

        // Skip SVG
        if ($extension === 'svg') {
            Log::info('Skipping thumbnail creation for unsupported formats: ' . $extension);
            return;
        }

        if (!Storage::disk('public')->exists($thumbnailFolder)) {
            Storage::disk('public')->makeDirectory($thumbnailFolder);
            Log::info('Thumbnails folder created: ' . $thumbnailFolder);
        }

        $imageFullPath = Storage::disk('public')->path($imagePath);
        Log::info('Full path to the image: ' . $imageFullPath);

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($imageFullPath);
                break;
            case 'png':
                $image = imagecreatefrompng($imageFullPath);
                break;
            case 'webp':
                $image = imagecreatefromwebp($imageFullPath); 
                break;
            default:
                Log::error('Unsupported image format for thumbnail creation: ' . $extension);
                return;
        }

        if (!$image) {
            Log::error('Error loading image for thumbnail creation: ' . $imageFullPath);
            return;
        }

        $originalWidth = imagesx($image);
        $originalHeight = imagesy($image);
        $scale = min($thumbnailWidth / $originalWidth, $thumbnailHeight / $originalHeight);
        $newWidth = (int)($originalWidth * $scale);
        $newHeight = (int)($originalHeight * $scale);

        $thumbnail = imagescale($image, $newWidth, $newHeight);
        if (!$thumbnail) {
            Log::error('Error creating thumbnail');
            imagedestroy($image);
            return;
        }

        $thumbFileName = pathinfo($imagePath, PATHINFO_FILENAME) . '_thumb.webp';
        $thumbPath = "{$thumbnailFolder}/{$thumbFileName}";

        ob_start();
        imagewebp($thumbnail, null, env('IMAGE_COMPRESSION_RATIO', 75)); 
        $thumbContent = ob_get_clean();

        Storage::disk('public')->put($thumbPath, $thumbContent);
        imagedestroy($thumbnail);
        imagedestroy($image);

        Log::info('Thumbnail created and saved at: ' . $thumbPath);

        return $thumbPath;
    }

    /**
     * Process the image based on the entity type (e.g., product, fair), applying resizing, thumbnail creation, and watermark.
     * 
     * @param Request $request The HTTP request containing the image.
     * @param string $inputName The name of the input field containing the image.
     * @param string $entity The entity type (e.g., products, fairs).
     * @param string $storagePath The folder where the image will be stored.
     * @return Image The processed image object.
     */
    public static function processImageForEntity(Request $request, $inputName, $entity, $storagePath)
    {
        // convert image in WebP
        $image = self::processSingleImage($request, $inputName, $storagePath);
        $imagePath = $image->path;

        switch ($entity) {
            case 'website':
                if ($request->type === 'fiere') {
                    // Resize images
                    self::resizeImage(
                        $imagePath,
                        env('FAIRS_IMAGE_MAX_WIDTH', 1920),
                        env('FAIRS_IMAGE_MAX_HEIGHT', 1080)
                    );
                }
                break;

            case 'contacts':
                // resize and convertion
                self::resizeImage(
                    $imagePath,
                    env('CONTACTS_IMAGE_MAX_WIDTH', 50), 
                    env('CONTACTS_IMAGE_MAX_HEIGHT', 50)
                );
                break;

            case 'fiere':
                if ($request->has('is_logo') && $request->is_logo) {
                    // resize images for logo
                    self::resizeImage(
                        $imagePath,
                        env('FAIRS_THUMBNAIL_LOGO_WIDTH', 100),
                        env('FAIRS_THUMBNAIL_LOGO_HEIGHT', 100)
                    );
                } else {
                    // resize and create thumbnails
                    self::resizeImage(
                        $imagePath,
                        env('FAIRS_IMAGE_MAX_WIDTH', 1920),
                        env('FAIRS_IMAGE_MAX_HEIGHT', 1080)
                    );
                    self::createThumbnail(
                        $imagePath,
                        env('FAIRS_THUMBNAIL_IMAGE_WIDTH', 200),
                        env('FAIRS_THUMBNAIL_IMAGE_HEIGHT', 200),
                        $storagePath . '/thumbnails'
                    );
                }
                break;

            case 'products':
                // Resize image
                self::resizeImage(
                    $imagePath,
                    env('PRODUCTS_IMAGE_MAX_WIDTH', 1920),
                    env('PRODUCTS_IMAGE_MAX_HEIGHT', 1080)
                );

                // Resize Thumbnail 
                self::createThumbnail(
                    $imagePath,
                    env('PRODUCTS_THUMBNAIL_WIDTH', 400),
                    env('PRODUCTS_THUMBNAIL_HEIGHT', 400),
                    $storagePath . '/thumbnails'
                );

                // Image watermark
                $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
                if ($extension !== 'svg') {
                    $watermarkPath = resource_path('images/logo.svg');  // LOGO Path in resources
                    self::applyWatermark($imagePath, $watermarkPath);
                }

                break;

            default:
                Log::error('Unknown entity type', ['entity' => $entity]);
                break;
        }

        return $image;
    }

    /**
     * Apply a watermark to the image, resizing the watermark if necessary and positioning it at the bottom right.
     * 
     * @param string $imagePath The path to the image.
     * @param string $watermarkPath The path to the watermark image.
     * @param int|string $maxWidth The maximum width of the watermark (can be percentage or pixel value).
     * @param int|string $maxHeight The maximum height of the watermark (can be percentage or pixel value).
     */
    public static function applyWatermark($imagePath, $watermarkPath, $maxWidth = 200, $maxHeight = 120)
    {
        if(env('WATERMARK_ENABLED', false) === false) {
            return;
        }

        $extension = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION)); 

        // Exclude file SVG
        if ($extension === 'svg') {
            Log::info('Skipping watermark for unsupported formats: ' . $extension);
            return;
        }

        if (!in_array($extension, ['jpeg', 'jpg', 'png', 'webp'])) {
            Log::error('Unsupported image format for watermarking: ' . $extension);
            throw new Exception('Unsupported image format for watermarking: ' . $extension);
        }

        $imageFullPath = Storage::disk('public')->path($imagePath);

        switch ($extension) {
            case 'jpeg':
            case 'jpg':
                $image = imagecreatefromjpeg($imageFullPath);
                break;
            case 'png':
                $image = imagecreatefrompng($imageFullPath);
                break;
            case 'webp':
                $image = imagecreatefromwebp($imageFullPath);
                break;
            default:
                Log::error('Unsupported image format for watermarking: ' . $extension);
                return;
        }

        $imageWidth = imagesx($image);
        $imageHeight = imagesy($image);

        $watermark = imagecreatefrompng($watermarkPath);

        $watermarkWidth = imagesx($watermark);
        $watermarkHeight = imagesy($watermark);

        // Resize watermark to maxWidth and maxHeight si
        if ($watermarkWidth > $maxWidth || $watermarkHeight > $maxHeight) {
            $scale = min($maxWidth / $watermarkWidth, $maxHeight / $watermarkHeight);
            $newWidth = intval($watermarkWidth * $scale);
            $newHeight = intval($watermarkHeight * $scale);

            $resizedWatermark = imagescale($watermark, $newWidth, $newHeight);
            imagedestroy($watermark); 
            $watermark = $resizedWatermark;
            $watermarkWidth = $newWidth;
            $watermarkHeight = $newHeight;
        }

        // watermark position
        $dstX = $imageWidth - $watermarkWidth - 10; 
        $dstY = $imageHeight - $watermarkHeight - 10;

        imagecopy($image, $watermark, $dstX, $dstY, 0, 0, $watermarkWidth, $watermarkHeight);

        ob_start();
        imagewebp($image, null, env('IMAGE_COMPRESSION_RATIO', 75)); 
        $finalImage = ob_get_clean();

        Storage::disk('public')->put($imagePath, $finalImage);

        imagedestroy($image);
        imagedestroy($watermark);

        Log::info('Watermark applied successfully', ['imagePath' => $imagePath]);
    }
}
