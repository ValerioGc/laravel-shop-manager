<?php

namespace App\Http\Controllers;

use App\Models\Show;
use App\Models\Image;
use App\Models\ImageAssociation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Utils\ConvertImageUtils;


use Exception;


/**
 * REST API Microservice Controller for the Show model
 * @package App\Http\Controllers
 * @date 05/08/2024
 * @version 1.0
 */
class ShowController extends Controller
{

    protected function formatDateRange($startDate, $endDate)
    {
        $start = \Carbon\Carbon::parse($startDate);
        $end = $endDate ? \Carbon\Carbon::parse($endDate) : null;

        if ($end && $start->month == $end->month) {
            return 'Dal ' . $start->format('d') . ' al ' . $end->format('d/m/Y');
        } elseif ($end) {
            return 'Dal ' . $start->format('d/m') . ' al ' . $end->format('d/m/Y');
        } else {
            return $start->format('d/m/Y');
        }
    }

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('shows');
        $this->loggingEnabled = env('LOG_SHOW', $this->loggingEnabled);
    }

    /**
     * Get Show by ID (public and admin) 
     * @param $id
     * @return Response
     */
    public function getShow(Request $request, $id)
    {

        try {
            if (auth('sanctum')->check()) {
                $show = $this->getShowForAdmin($request, $id);
            } else {
                $show = $this->getShowForPublic($id);
            }

            if (!$show) {
                $this->log('error', 'Read show - Error', ['message' => 'Show not found']);
                return response()->json(['status' => 'error', 'message' => 'Fiera non trovata'], 404);
            }


            return response()->json(['data' => $show], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // ****************************************************************
    // ******************* PUBLIC ROUTES (FE) *************************
    // ****************************************************************

    /**
     * Get all shows for the frontend website
     * @return Show List
     */
    public function getShowForPublic($id)
    {
        try {
            $show = Show::with(['image', 'images' => function ($query) {
                $query->orderBy('order', 'asc');
            }])->findOrFail($id);

            $formattedDate = $this->formatDateRange($show->start_date, $show->end_date);

            $formattedShow = [
                'id' => $show->id,
                'label_ita' => $show->label_ita,
                'label_eng' => $show->label_eng,
                'location' => $show->location,
                'start_date' => $formattedDate,
                'end_date' => $show->end_date,  
                'description_ita' => $show->description_ita,
                'description_eng' => $show->description_eng,
                'link' => $show->link,
                'image_url' => $show->image ? url(Storage::url($show->image->path)) : null,
                'images_url' => $show->images->map(function ($image) {
                    return url(Storage::url($image->path));
                })->all(),
            ];

            return $formattedShow;
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * Get paginated old shows for the frontend website
     * @param Request $request
     * @return Show List
     */
    public function getPaginatedOldShows(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $orderBy = $request->query('orderBy', 'updated_at');
            $order = $request->query('order', 'desc');

            $query = Show::select('id', 'label_ita', 'location', 'label_eng', 'start_date', 'end_date', 'image_id')
            ->where(function ($q) {
                $q->where('end_date', '<', now());
            });

            $shows = $query->orderBy($orderBy, $order)->paginate($limit, ['*'], 'page', $page);

            $formattedShows = $shows->map(function ($show) {
                $formattedDate = $this->formatDateRange($show->start_date, $show->end_date);

                return [
                    'id' => $show->id,
                    'label_ita' => $show->label_ita,
                    'label_eng' => $show->label_eng,
                    'start_date' => $formattedDate,
                    'location' => $show->location,
                    'end_date' => $show->end_date,  
                    'image_url' => $show->image ? url(Storage::url($show->image->path)) : null,
                ];
            });

            return response()->json(['data' => $formattedShows, 'total' => $shows->total()], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get new shows for the frontend website used in the carousel
     * @param Request $request
     * @return Show List
     */
    public function getNewShows(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);

            $shows = Show::select('label_ita', 'label_eng', 'location', 'start_date', 'end_date', 'link', 'image_id')
            ->where(function ($q) {
                $q->where('end_date', '>=', now())->orWhereNull('end_date');
            })
                ->orderBy('start_date', 'asc')
                ->limit($limit)
                ->get();

            $shows = $shows->map(function ($show) {
                return [
                    'label_ita' => $show->label_ita,
                    'label_eng' => $show->label_eng,
                    'start_date' => $show->start_date,
                    'location' => $show->location,
                    'end_date' => $show->end_date,
                    'link' => $show->link,
                    'image_url' => $show->image ? url(Storage::url($show->image->path)) : null,
                ];
            });

            return response()->json(['data' => $shows], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }


    // ****************************************************************
    // ******************* PRIVATE ROUTES (BE) ************************
    // ****************************************************************

    /**
     * Get Show by ID for Admin
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function getShowForAdmin(Request $request, $id)
    {
        $this->logMethodAndUri($request);

        try {
            $show = Show::with(['image', 'images' => function ($query) {
                $query->orderBy('order', 'asc');
            }])->findOrFail($id);

            if (!$show) {
                $this->log('error', 'Read show - Error', ['message' => 'Show not found']);
                return response()->json(['status' => 'error', 'message' => 'Fiera non trovata'], 404);
            }

            $formattedShow = [
                'id' => $show->id,
                'label_ita' => $show->label_ita,
                'label_eng' => $show->label_eng,
                'start_date' => $show->start_date,
                'end_date' => $show->end_date,
                'description_ita' => $show->description_ita,
                'location' => $show->location,
                'description_eng' => $show->description_eng,
                'link' => $show->link,
                'image_url' => $show->image ? url(Storage::url($show->image->path)) : null,
                'images_url' => $show->images->map(function ($image) {
                    return url(Storage::url($image->path));
                }),
                'created_at' => $show->created_at,
                'formatted_updated_at' => $show->formatted_updated_at,
            ];

            $this->log('info', 'Show read completed', ['show' => $show]);
            return $formattedShow;
        } catch (\Exception $e) {
            $this->log('error', 'Read show - Error', ['exception' => $e]);
            return null;
        }
    }

    /**
     * Get all shows paginated for the frontend website
     * @param Request $request
     * @param $page - Page number
     * @param $limit - Number of items per page
     * @param $orderBy - Order by field
     * @param $order - Order direction
     * @return Show List
     */
    public function getAllShowPaginated(Request $request)
    {
        $this->logMethodAndUri($request);

        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $orderBy = $request->query('orderBy', 'updated_at');
            $order = $request->query('order', 'desc');

            $query = Show::select('id', 'label_ita', 'updated_at')
                ->orderBy($orderBy, $order);

            $shows = $query->paginate($limit, ['*'], 'page', $page);

            $formattedShows = $shows->getCollection()->map(function ($show) {
                return [
                    'id' => $show->id,
                    'label_ita' => $show->label_ita,
                    'location' => $show->location,
                    'formatted_updated_at' => $show->updated_at ? $show->updated_at->format('d-m-Y') : null,
                ];
            });

            $shows->setCollection($formattedShows);

            if ($shows->isEmpty()) {
                $this->log('error', 'Read all paginated shows - Error', ['message' => 'Shows not found']);
            }

            return response()->json(['data' => $shows], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read all paginated shows - Error', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new Show
     * @param Request $request
     * @return Response
     */
    public function create(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Creating new show');

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'location' => 'nullable|string',
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'description_ita' => 'nullable|string',
                'description_eng' => 'nullable|string',
                'link' => 'nullable|string',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Create show - Validation Error: ', ['errors' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            // Crea il nuovo show
            $show = Show::create([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'location' => $request->location,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description_ita' => $request->description_ita,
                'description_eng' => $request->description_eng,
                'link' => $request->link,
            ]);

            $showFolder = 'images/shows/' . $show->id;
            Storage::disk('public')->makeDirectory($showFolder);
            Storage::disk('public')->makeDirectory($showFolder . '/thumbnails');
            $this->log('info', 'Cartella della fiera creata: ', ['folder' => $showFolder]);

            if ($request->hasFile('image')) {
                $request->merge(['is_logo' => true]); 
                $image = ConvertImageUtils::processImageForEntity($request, 'image', 'fiere', $showFolder);
                $show->update(['image_id' => $image->id]);
            }

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $request->merge(['is_logo' => false]);
                    $associatedImage = ConvertImageUtils::processImageForEntity($request, 'images.' . $index, 'fiere', $showFolder);
                    ImageAssociation::create([
                        'image_id' => $associatedImage->id,
                        'entity_id' => $show->id,
                        'type_entity' => 1, // type_entity = 1 (shows)
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Fiera creata con successo',
                'data' => $show
            ], 201);
        } catch (\Exception $e) {
            $this->log('error', 'Create show - Error creating show', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        } finally {
            $this->clearEntityCache('show');
        }
    }

    /**
     * Edit a Show
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function edit(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Editing show with id: ', ['id' => $id]);

        $request->merge(['removed_image' => filter_var($request->removed_image, FILTER_VALIDATE_BOOLEAN)]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'location' => 'nullable|string',
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'nullable|date_format:Y-m-d',
                'description_ita' => 'nullable|string',
                'description_eng' => 'nullable|string',
                'link' => 'nullable|string',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp',
                'removed_image' => 'boolean'
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Edit show - Validation Error: ', ['errors' => $validator->errors()]);
                return response()->json([
                    'status' => 'error',
                    'message' => $validator->errors()
                ], 422);
            }

            $show = Show::findOrFail($id);
            if (!$show) {
                $this->log('error', 'Edit show - Error', ['message' => 'Show non trovato']);
                return response()->json(['status' => 'error', 'message' => 'Show non trovato'], 404);
            }

            $showFolder = 'images/shows/' . $show->id;
            $thumbnailFolder = $showFolder . '/thumbnails';

            if (!Storage::disk('public')->exists($showFolder)) {
                Storage::disk('public')->makeDirectory($showFolder);
                Storage::disk('public')->makeDirectory($thumbnailFolder);
                $this->log('info', 'Cartella della fiera creata: ', ['folder' => $showFolder]);
            }

            if ($request->removed_image) {
                $removedImage = Image::find($show->image_id);
                if ($removedImage) {
                    Storage::disk('public')->delete($removedImage->path);
                    $thumbPath = $thumbnailFolder . '/' . pathinfo($removedImage->path, PATHINFO_FILENAME) . '_thumb.webp';
                    if (Storage::disk('public')->exists($thumbPath)) {
                        Storage::disk('public')->delete($thumbPath);
                    }
                    $removedImage->delete();
                    $show->update(['image_id' => null]);
                    $this->log('info', 'Immagine singola rimossa e thumbnail cancellata: ', ['image_id' => $removedImage->id]);
                }
            }

            $previousImage = $show->image;
            if ($request->hasFile('image')) {
                $request->merge(['is_logo' => true]);
                $image = ConvertImageUtils::processImageForEntity($request, 'image', 'fiere', $showFolder);
                $show->update(['image_id' => $image->id]);

                if ($previousImage && $previousImage->id !== $show->image_id) {
                    Storage::disk('public')->delete($previousImage->path);
                    $previousThumbPath = $thumbnailFolder . '/' . pathinfo($previousImage->path, PATHINFO_FILENAME) . '_thumb.webp';
                    if (Storage::disk('public')->exists($previousThumbPath)) {
                        Storage::disk('public')->delete($previousThumbPath);
                    }
                    $previousImage->delete();
                    $this->log('info', 'Immagine precedente e thumbnail cancellata: ', ['image_id' => $previousImage->id]);
                }
            }

            if ($request->has('remove_images')) {
                $removeImages = $request->input('remove_images');
                foreach ($removeImages as $imageUrl) {
                    $image = Image::where('path', 'LIKE', '%' . basename($imageUrl) . '%')->first();
                    if ($image) {
                        $imageAssociation = ImageAssociation::where('image_id', $image->id)->where('entity_id', $show->id)->first();
                        if ($imageAssociation) {
                            Storage::disk('public')->delete($image->path);
                            $thumbPath = $thumbnailFolder . '/' . pathinfo($image->path, PATHINFO_FILENAME) . '_thumb.webp';
                            if (Storage::disk('public')->exists($thumbPath)) {
                                Storage::disk('public')->delete($thumbPath);
                            }
                            $image->delete();
                            $imageAssociation->delete();
                            $this->log('info', 'Immagine multipla rimossa e thumbnail cancellata: ', ['image_id' => $image->id]);
                        }
                    }
                }
            }
            $allImagesOrder = json_decode($request->input('images_order'), true);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $file) {
                    $request->merge(['is_logo' => false]); 
                    $associatedImage = ConvertImageUtils::processImageForEntity($request, 'images.' . $index, 'fiere', $showFolder);
                    $filename = $file->getClientOriginalName();
                    foreach ($allImagesOrder as &$order) {  
                        if (isset($order['filename']) && $order['filename'] === $filename) {
                            $order['url'] = $associatedImage->path;
                            break;  
                        }
                    }
                    ImageAssociation::create([
                        'image_id' => $associatedImage->id,
                        'entity_id' => $show->id,
                        'type_entity' => 1, // type_entity = 1 (shows)
                    ]);
                    $this->log('info', 'Nuova immagine multipla salvata: ', ['image_id' => $associatedImage->id]);
                }
            }

            $remainingImagesCount = ImageAssociation::where('entity_id', $show->id)->where('type_entity', 1)->count();
            if (!$show->image && $remainingImagesCount == 0 && Storage::disk('public')->exists($showFolder)) {
                Storage::disk('public')->deleteDirectory($showFolder);
                $this->log('info', 'Cartella della fiera eliminata perché nessuna immagine rimasta: ', ['folder' => $showFolder]);
            }
            if ($request->has('images_order')) {
                $this->log('info', 'Updating images order');
                foreach ($allImagesOrder as $image) {
                    if (is_string($image['url'])) {
                        $img = Image::where('path', 'LIKE', '%' . basename($image['url']) . '%')->first();
                        if ($img) {
                            $img->order = $image['order'];
                            $img->save();
                        }
                    }
                }
            }

            $show->update([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'location' => $request->location,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description_ita' => $request->description_ita,
                'description_eng' => $request->description_eng,
                'link' => $request->link,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Fiera aggiornata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Edit show - Error updating show', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        } finally {
            $this->clearEntityCache('show');
        }
    }


    /**
     * Delete a Show
     * @param $id
     * @return Response
     */
    public function deleteShow(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Deleting show with id: ', ['id' => $id]);

        try {
            $show = Show::findOrFail($id);

            if (!$show) {
                $this->log('error', 'Show not found');
                return response()->json(['status' => 'error', 'message' => 'Fiera non trovata'], 404);
            }

            if ($show->image) {
                Storage::disk('public')->delete($show->image->path);
                $show->image->delete();
            }

            $images = ImageAssociation::where('entity_id', $show->id)->where('type_entity', 1)->get(); 
            foreach ($images as $imageAssociation) {
                $image = Image::find($imageAssociation->image_id);
                if ($image) {
                    Storage::disk('public')->delete($image->path);
                    $image->delete();
                    $imageAssociation->delete();
                }
            }

            $showFolder = 'images/shows/' . $show->id;
            if (Storage::disk('public')->exists($showFolder)) {
                Storage::disk('public')->deleteDirectory($showFolder);
                $this->log('info', 'Cartella della fiera eliminata: ', ['folder' => $showFolder]);
            } else {
                $this->log('info', 'Cartella della fiera non trovata: ', ['folder' => $showFolder]);
            }

            $show->delete();

            $this->log('info', 'Show deleted successfully');
            return response()->json([
                'status' => 'success',
                'message' => 'Fiera eliminata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Delete show error: ', ['exception' => $e]);
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        } finally {
            $this->clearEntityCache('show');
        }
    }
}
