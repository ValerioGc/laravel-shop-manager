<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Image;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use App\Models\ImageAssociation;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\CategoryController;
use App\Utils\CategoryProductUtils;
use App\Utils\ConvertImageUtils;
use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\DB;



/**
 * APIs for managing products
 * @package App\Http\Controllers
 * @version v1.0
 * @date 2021-06-17
 */
class ProductController extends Controller
{

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('products');
        $this->loggingEnabled = env('LOG_PRODUCTS', $this->loggingEnabled);
    }

    /**
     * Read a product by id, based on the user's role
     */
    public function getProduct($id)
    {
        $this->log('info', 'getProduct', ['id' => $id]);

        try {
            if (auth('sanctum')->check()) {
                $product = $this->getProductForAdmin($id);
            } else {
                $product = $this->getProductForPublic($id);
            }

            if (!$product) {
                $this->log('error', 'Prodotto non trovato', ['id' => $id]);
                return response()->json(['status' => 'error', 'message' => 'Prodotto non trovato'], 404);
            }

            return response()->json(['data' => $product], 200);
        } catch (Exception $e) {
            $this->log('error', 'Error fetching product', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->log('info', 'getProduct completed');
        }
    }

    // ****************************************************************
    // ******************* PUBLIC ROUTES (FE) *************************
    // *************************************************************

    /**
     * Get all products for the public site
     * @param int $id
     * @return Product
     */
    private function getProductForPublic($id)
    {
        $product = Product::with(['condition', 'images' => function ($query) {
            $query->orderBy('order', 'asc');
        }])->find($id);

        if (!$product) {
            return null;
        }

        return [
            'code' => $product->code,
            'quantity' => $product->quantity,
            'label_ita' => $product->label_ita,
            'label_eng' => $product->label_eng,
            'year' => $product->year,
            'description_ita' => $product->description_ita,
            'description_eng' => $product->description_eng,
            'price' => $product->price,
            'condition' => $product->condition ? [
                'label_ita' => $product->condition->label_ita,
                'label_eng' => $product->condition->label_eng,
            ] : null,
            'images_url' => $product->images->map(function ($image) {
                return url(Storage::url($image->path));
            })
        ];
    }

    /**
     * Filter products for the public site
     * @param Request $request
     * @return JsonResponse
     */
    public function filterProducts(Request $request)
    {
        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $orderBy = $request->query('orderBy', 'created_at');
            $order = $request->query('order', 'desc');
            $categoryId = $request->query('category_id', null);
            $inEvidence = $request->query('in_evidence', null);

            $query = Product::where('deleting', false)
                ->where('draft', false)
                ->with(['condition', 'images'])
                ->orderBy($orderBy, $order);

            if (!is_null($categoryId)) {
                $categoryIds = CategoryController::getAllChildCategories($categoryId);
                $query->whereHas('categories', function ($query) use ($categoryIds) {
                    $query->whereIn('category_id', $categoryIds);
                });
            }

            if ($inEvidence === 'true') {
                // in_evidence = true
                $evidenceProducts = Product::where('deleting', false)
                    ->where('draft', false)
                    ->where('in_evidence', true)
                    ->orderBy('created_at', 'desc')
                    ->get();

                // in_evidence = false
                $otherProducts = Product::where('deleting', false)
                    ->where('draft', false)
                    ->where('in_evidence', false)
                    ->orderBy('created_at', 'desc')
                    ->get();

                $productsCollection = $evidenceProducts->merge($otherProducts);

                // Manual pagination
                $total = $productsCollection->count();
                $productsCollection = $productsCollection->slice(($page - 1) * $limit, $limit)->values();
                $products = new \Illuminate\Pagination\LengthAwarePaginator($productsCollection, $total, $limit, $page);
            } else {
                $products = $query->paginate($limit, ['*'], 'page', $page);
            }

            $products->getCollection()->transform(function ($product) {
                $firstImage = $product->images->sortBy('order')->first();
                return [
                    'id' => $product->id,
                    'label_ita' => $product->label_ita,
                    'label_eng' => $product->label_eng,
                    'price' => $product->price,
                    'image_url' => $firstImage ? url(Storage::url($firstImage->path)) : null,                ];
            });

            return response()->json($products, 200);
        } catch (Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    // ******************************************************
    //*************** PRIVATE ROUTES (BO) *******************
    //*******************************************************

    /**
     * Get all products for the admin panel
     * @param Request $request
     * @param int $id
     * @return Product
     */
    private function getProductForAdmin($id)
    {
        $product = Product::with(['condition', 'images' => function ($query) {
            $query->orderBy('order', 'asc');
        }, 'categories'])->find($id); 

        if (!$product) {
            return null;
        }

        return [
            'id' => $product->id,
            'code' => $product->code,
            'quantity' => $product->quantity,
            'label_ita' => $product->label_ita,
            'label_eng' => $product->label_eng,
            'year' => $product->year,
            'description_ita' => $product->description_ita,
            'description_eng' => $product->description_eng,
            'price' => $product->price,
            'in_evidence' => $product->in_evidence,
            'draft' => $product->draft,
            'condition' => $product->condition ? [
                'id' => $product->condition->id,
                'label_ita' => $product->condition->label_ita,
                'label_eng' => $product->condition->label_eng,
            ] : null,
            'creator' => $product->creator,
            'formatted_updated_at' => $product->formatted_updated_at,
            'created_at' => $product->created_at,
            'images_url' => $product->images->sortBy('order')->map(function ($image) {
                return url(Storage::url($image->path));
            }),
            'categories' => $product->categories->map(function ($category) {
                return [
                    'id' => $category->id,
                    'label_ita' => $category->label_ita,
                    'type' => $category->type,
                ];
            }),
        ];
    }

    /**
     * Read all paginated products for the admin panel 
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllPaginateProducts(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Read all paginate products', ['request' => $request->all()]);

        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $orderBy = $request->query('orderBy', 'updated_at');
            $order = $request->query('order', 'desc');
            $uncategorized = $request->query('uncat', 'false');

            $query = Product::where('deleting', false)
                ->with(['condition', 'images']);

            if ($request->has('draft')) {
                $draft = $request->query('draft');
                if ($draft === 'true') {
                    $this->log('info', 'Filtering by draft', ['draft' => $draft]);
                    $query->where('draft', true);
                }
            } else {
                $query->where('draft', false);
            }

            if ($uncategorized === 'true') {
                $query->whereDoesntHave('categories');
            }

            $products = $query->orderBy($orderBy, $order)->paginate($limit, ['*'], 'page', $page);

            $products->getCollection()->transform(function ($product) {
                $pictureUrl = $product->images->isNotEmpty() ? url(Storage::url($product->images->first()->path)) : null;
                return [
                    'id' => $product->id,
                    'label_ita' => $product->label_ita,
                    'formatted_updated_at' => $product->formatted_updated_at,
                    'creator' => $product->creator
                ];
            });

            if ($products->isEmpty()) {
                $this->log('info', 'No products found');
            }

            return response()->json($products, 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read all paginate products error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    /**
     * Create a new product
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Creating new product', ['request' => $request->all()]);

        $request->merge(['in_evidence' => filter_var($request->in_evidence, FILTER_VALIDATE_BOOLEAN)]);
        $request->merge(['draft' => filter_var($request->draft, FILTER_VALIDATE_BOOLEAN)]);

        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer',
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'year' => 'nullable|integer',
                'description_ita' => 'required|string',
                'description_eng' => 'required|string',
                #'price' => 'sometimes|numeric',
                'draft' => 'required|boolean',
                'in_evidence' => 'required|boolean',
                'condition_id' => 'required|integer',
                'creator' => 'sometimes|string',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp',
                'categories' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Create product - Validation Error', ['errors' => $validator->errors()]);
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => $validator->errors()
                    ],
                    422
                );
            }

            $existingProduct = Product::where('label_ita', $request->label_ita)
                ->where('label_eng', $request->label_eng)
                ->whereHas('categories', function ($query) use ($request) {
                    $categories = json_decode($request->categories, true); 
                    $query->whereIn('categories.id', $categories);
                })
                ->first();

            if ($existingProduct) {
                $this->log('error', 'Product already exists', ['product' => $existingProduct]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prodotto con stessa categoria, nome:' . $existingProduct->label_ita . ' e nome (eng): ' .  $existingProduct->label_eng . ' già esistente'
                ], 422);
            }
            $price = $request->price;
            $price = $request->price;

            if (!empty($price) && preg_match('/^\d+([.,]\d{1,2})?$/', trim($price))) {
                $price .= ' €';
            }

            $product = Product::create([
                'code' => $request->code,
                'quantity' => $request->quantity,
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'year' => $request->year,
                'description_ita' => $request->description_ita,
                'description_eng' => $request->description_eng,
                'price' => $price,
                'in_evidence' => $request->in_evidence,
                'draft' => $request->draft,
                'creator' => $request->creator,
                'condition_id' => $request->condition_id
            ]);

            if ($request->hasFile('images')) {
                $this->log('info', 'Adding images');
                foreach ($request->file('images') as $index => $file) {
                    $imageKey = 'images.' . $index;
                    $this->log('info', 'Processing image', ['imageKey' => $imageKey, 'originalName' => $file->getClientOriginalName()]);
                    $image = ConvertImageUtils::processImageForEntity($request, $imageKey, 'products', "images/products/{$product->id}");
                    $this->log('info', 'Image processed', ['imagePath' => $image->path]);
                    ImageAssociation::create([
                        'image_id' => $image->id,
                        'type_entity' => 0,
                        'entity_id' => $product->id,
                    ]);
                }
            }

            if ($request->has('categories') && !empty(json_decode($request->input('categories'), true))) {
                $categories = $request->input('categories');
                $result = CategoryProductUtils::createOrUpdateCategoryProductRelations($categories, $product->id, 2);

                if ($result !== 'success') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante la creazione delle relazioni categoria-prodotto',
                    ], 500);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto inserito con successo',
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Create product error:', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
            $this->log('info', 'Create product completed');
        }
    }


    /**
     * Edit existing product by id
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function editProduct(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Updating product with id:', ['id' => $id]);
        $this->log('info', 'New data: ', ['request' => $request->all()]);

        $request->merge(['in_evidence' => filter_var($request->in_evidence, FILTER_VALIDATE_BOOLEAN)]);
        $request->merge(['draft' => filter_var($request->draft, FILTER_VALIDATE_BOOLEAN)]);

        try {
            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer',
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'year' => 'nullable|integer',
                'description_ita' => 'required|string',
                'description_eng' => 'required|string',
                #'price' => 'sometimes|numeric',
                'in_evidence' => 'required|boolean',
                'draft' => 'required|boolean',
                'condition_id' => 'required|integer',
                'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,svg,webp',
                'categories' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Update product - Validation Error', ['errors' => $validator->errors()]);
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => $validator->errors()
                    ],
                    422
                );
            }

            if (!$id) {
                $this->log('error', 'Product id not valid');
                return response()->json([
                    'status' => 'error',
                    'message' => 'ID prodotto non valido'
                ], 400);
            }

            $existingProduct = Product::where('label_ita', $request->label_ita)
                ->where('label_eng', $request->label_eng)
                ->whereHas('categories', function ($query) use ($request) {
                    $categories = json_decode($request->categories, true);
                    $query->whereIn('categories.id', $categories);
                })
                ->where('id', '!=', $id)
                ->first();

            if ($existingProduct) {
                $this->log('error', 'Product already exists', ['product' => $existingProduct]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prodotto con stessa categoria, nome:' . $existingProduct->label_ita . ' e nome (eng): ' .  $existingProduct->label_eng . ' già esistente'
                ], 422);
            }

            $product = Product::findOrFail($id);


            $price = $request->price;

            if (!empty($price) && preg_match('/^\d+([.,]\d{1,2})?$/', trim($price)) && !str_contains($price, '€')) {
                $price .= ' €';
            }

            $product->update([
                'code' => $request->code,
                'quantity' => $request->quantity,
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'year' => $request->year,
                'draft' => $request->draft,
                'description_ita' => $request->description_ita,
                'description_eng' => $request->description_eng,
                'price' => $price,
                'in_evidence' => $request->in_evidence,
                'condition_id' => $request->condition_id,
            ]);

            if ($request->has('remove_images')) {
                $this->log('info', 'Removing images', ['images' => $request->input('remove_images')]);
                $removeImages = $request->input('remove_images');
                foreach ($removeImages as $imageUrl) {
                    $image = Image::where('path', 'LIKE', '%' . basename($imageUrl) . '%')->first();
                    if ($image) {
                        $imageAssociation = ImageAssociation::where('image_id', $image->id)->where('entity_id', $product->id)->first();
                        if ($imageAssociation) {
                            Storage::disk('public')->delete($image->path);

                            $thumbPath = 'images/products/' . $product->id . '/thumbnails/' . pathinfo($image->path, PATHINFO_FILENAME) . '_thumb.webp';
                            if (Storage::disk('public')->exists($thumbPath)) {
                                Storage::disk('public')->delete($thumbPath);
                                $this->log('info', 'Thumbnail deleted', ['path' => $thumbPath]);
                            }

                            $image->delete();
                            $imageAssociation->delete();
                            $this->log('info', 'Image and association deleted', ['image_id' => $image->id]);
                        }
                    }
                }
            }

            $allImagesOrder = json_decode($request->input('images_order'), true);

            if ($request->hasFile('images')) {
                $this->log('info', 'Adding new images');
                foreach ($request->file('images') as $index => $file) {
                    $image = ConvertImageUtils::processImageForEntity($request, 'images.' . $index, 'products', "images/products/{$product->id}");
                    $filename = $file->getClientOriginalName();
                    foreach ($allImagesOrder as &$order) {  
                        if (isset($order['filename']) && $order['filename'] === $filename) {
                            $order['url'] = $image->path;
                            break;  
                        }
                    }
                    ImageAssociation::create([
                        'image_id' => $image->id,
                        'type_entity' => 0,
                        'entity_id' => $product->id,
                    ]);
                }
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

            if ($request->has('categories')) {
                $categories = $request->input('categories');
                $result = CategoryProductUtils::createOrUpdateCategoryProductRelations($categories, $product->id, 2);

                if ($result !== 'success') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante l\'aggiornamento delle relazioni categoria-prodotto',
                    ], 500);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto aggiornato con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Update product error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
            $this->log('info', 'Updating product completed');
        }
    }


    /**
     * Clone a product by id
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cloneProduct(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Cloning product with id:', ['id' => $id]);
    
        try {
            $product = Product::findOrFail($id);

            $productClone = Product::where('label_ita', $product->label_ita . ' (Clone)')
                                    ->orWhere('label_eng', $product->label_eng . ' (Clone)')
                                    ->first();

            if ($productClone) {
                if ($productClone->deleting == true) {
                    $this->deleteProduct($request, $productClone->id);
                    $this->log('info', 'Product clone in recycle bin removed', ['product' => $productClone]);
                } else {
                    $this->log('error', 'Product clone already exists', ['product' => $productClone]);
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Prodotto clone con nome:' . $productClone->label_ita . ' e nome (eng) ' .  $productClone->label_eng . ' già esistente'
                    ], 422);
                }
            }

            $clonedProduct = Product::create([
                'code' => $product->code, 
                'quantity' => $product->quantity,
                'label_ita' => $product->label_ita . ' (Clone)',
                'label_eng' => $product->label_eng . ' (Clone)',
                'year' => $product->year,
                'creator' => auth('sanctum')->user()->name,
                'deleting' => false,
                'draft' => true,
                'description_ita' => $product->description_ita,
                'description_eng' => $product->description_eng,
                'price' => $product->price,
                'in_evidence' => false,
                'condition_id' => $product->condition_id,
            ]);

            $this->log('info', 'Categories:', ['categories' => $product->categories]);

            $cat = [];
            foreach ($product->categories as $category) {
                $cat[] = $category->id;
            }

            $result = CategoryProductUtils::createOrUpdateCategoryProductRelations(json_encode($cat), $clonedProduct->id, 2);
            $this->log('info', 'Creating category-product relations', ['result' => $result]);
            
            if ($result !== 'success') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Errore durante l\'aggiornamento delle relazioni categoria-prodotto',
                ], 500);
            }
    
            $this->log('info', 'Product cloned successfully', ['new_product_id' => $clonedProduct->id]);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto clonato con successo',
                'product_id' => $clonedProduct->id,
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Clone product error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
            $this->log('info', 'Cloning product completed');
        }
    }
    
    // ******************* DRAFT METHODS *******************

    /**
     * Change the draft status for a product
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function draftProduct(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Changing draft status for Product with id: ', ['id' => $id]);

        try {
            $product = Product::findOrFail($id);

            if (!$product) {
                $this->log('error', 'Product not found');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prodotto non trovato'
                ], 404);
            }

            if ($product->draft == true) {
                $product->draft = false;
                $this->log('info', 'Current status: true | new Product draft status = false');
            } else {
                $product->draft = true;
                $this->log('info', 'Current status: false | new Product draft status = true');
            }
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto messo in bozza con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Changing draft status error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
            $this->log('info', 'Changing draft status completed');
        }
    }

    // ******************* DELETE AND TRASH METHODS *******************

    /**
     * Delete a product by id
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function deleteProduct(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Deleting product with id: ', ['id' => $id]);

        try {
            $product = Product::findOrFail($id);

            if (!$product) {
                $this->log('error', 'Product not found');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prodotto non trovato'
                ], 404);
            }

            $images = DB::table('image_associations')
                ->join('images', 'image_associations.image_id', '=', 'images.id')
                ->where('image_associations.entity_id', $product->id)
                ->where('image_associations.type_entity', 0) // type_entity = 0 -> product
                ->select('images.path')
                ->get();

            $this->log('info', 'Deleting images', ['images' => $images]);

            if ($images->isNotEmpty()) {
                foreach ($images as $image) {
                    if (Storage::disk('public')->exists($image->path)) {
                        Storage::disk('public')->delete($image->path);
                        $this->log('info', 'Immagine eliminata dal percorso: ', ['path' => $image->path]);
                    } else {
                        $this->log('info', 'Immagine non trovata nel percorso:', ['path' => $image->path]);
                    }
                }
            }

            $productFolder = 'images/products/' . $product->id;
            if (Storage::disk('public')->exists($productFolder)) {
                Storage::disk('public')->deleteDirectory($productFolder);
                $this->log('info', 'Cartella del prodotto eliminata: ', ['folder' => $productFolder]);
            } else {
                $this->log('info', 'Cartella del prodotto non trovata: ', ['folder' => $productFolder]);
            }

            $product->delete();

            $this->log('info', 'Delete product completed');

            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto eliminato con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Error deleting product', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
        finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
        }
    }


    /**
     * Read all trash bin products -  products with deleting = true
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllDeletingPaginateProducts(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Read All paginated trash bin products', ['request' => $request->all()]);

        try {
            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $orderBy = $request->query('orderBy', 'updated_at');
            $order = $request->query('order', 'desc');

            $products = Product::where('deleting', true)->orderBy($orderBy, $order)->paginate($limit, ['*'], 'page', $page);

            return response()->json($products, 200);
        } catch (Exception $e) {
            $this->log('error', 'Read All paginated trash bin products error:', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } 
    }

    /**
     * Soft delete a product - set deleting = true
     * @param int $id
     * @return JsonResponse
     */
    public function softDeleteProduct(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Moving to bin product with id: ', ['id' => $id]);

        try {
            $product = Product::findOrFail($id);

            if ($product->deleting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Prodotto già nel cestino'
                ], 400);
            }

            $product->deleting = true;
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto impostato per la cancellazione con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Soft deleting product error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
            $this->log('info', 'Soft deleting product completed');
        }
    }

    /**
     * Restore a soft deleted product - set deleting = false
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function restoreSoftDeleteProduct(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Restore soft delete product with id:', ['id' => $id]);

        try {
            $product = Product::findOrFail($id);

            if (!$product->deleting) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Il prodotto non è nel cestino'
                ], 400);
            }

            $product->deleting = false;
            $product->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Prodotto ripristinato con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Restore soft delete product error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
            $this->log('info', 'Restore soft delete product completed');
        }
    }

    /**
     * Empty the product trash by deleting all products with deleting = true
     * @param Request $request
     * @return JsonResponse
     */
    public function deleteTrashProduct(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Empty product trash');

        try {

            Product::where('deleting', true)->chunkById(100, function ($products) {
                foreach ($products as $product) {
                    $this->log('info', 'Deleting product with id: ', ['id' => $product->id]);

                    $images = DB::table('image_associations')
                        ->join('images', 'image_associations.image_id', '=', 'images.id')
                        ->where('image_associations.entity_id', $product->id)
                        ->where('image_associations.type_entity', 0) // type_entity = 0 per prodotti
                        ->select('images.path')
                        ->get();

                    $this->log('info', 'Deleting images', ['images' => $images]);

                    if ($images->isNotEmpty()) {
                        foreach ($images as $image) {
                            if (Storage::disk('public')->exists($image->path)) {
                                Storage::disk('public')->delete($image->path);
                                $this->log('info', 'Immagine eliminata dal percorso: ', ['path' => $image->path]);
                            } else {
                                $this->log('info', 'Immagine non trovata nel percorso: ', ['path' => $image->path]);
                            }
                        }
                    }

                    $productFolder = 'images/products/' . $product->id; 
                    if (Storage::disk('public')->exists($productFolder)) {
                        Storage::disk('public')->deleteDirectory($productFolder);
                        $this->log('info', 'Cartella immagini del prodotto eliminata: ', ['folder' => $productFolder]);
                    } else {
                        $this->log('info', 'Cartella immagini del prodotto non trovata: ', ['folder' => $productFolder]);
                    }

                    $product->delete();
                }
            });

            $this->log('info', 'Empty product trash completed');

            return response()->json([
                'status' => 'success',
                'message' => 'Cestino svuotato con successo'
            ], 200);
        } catch (Exception $e) {
            $this->log('error', 'Delete product error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
            $this->clearEntityCache('search');
        }
    }
}
