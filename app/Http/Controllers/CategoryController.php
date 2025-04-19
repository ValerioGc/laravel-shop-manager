<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Models\CategoryProduct;
use App\Utils\CategoryProductUtils;

use Illuminate\Support\Facades\Log;

/**
 * Class CategoryController
 * @package App\Http\Controllers
 * @group Categories
 * @date 2021-06-01
 * @version 1.0
 */
class CategoryController extends Controller
{

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('categories');
        $this->loggingEnabled = env('LOG_CATEGORIES', $this->loggingEnabled);
    }

    // ****************************************************************
    // ******************* PUBLIC ROUTES (FE) *************************
    // ****************************************************************

    /**
     * Get all categories with tree structure for the frontend website
     * Retrieve all categories and build a tree structure for the aside filter 
     * @param string $lang 'it' or 'eng' for sorting language
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllCategories(string $lang)
    {
        try {
            $categories = Category::all();

            if ($categories->isEmpty()) {
                return response()->json([
                    'status' => 'empty',
                    'message' => 'No categories found',
                ], 204);
            }

            Log::info('Original Categories', ['categories' => $categories->toArray()]);

            // Build the tree structure
            $tree = $this->buildCategoryTree($categories, 0, null, $lang);

            $tree = $this->sortTree($tree, $lang);

            return response()->json(['data' => $tree], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build category list to tree structure 
     * @param \Illuminate\Support\Collection $categories
     * @param int $type
     * @param int|null $parentId
     * @param string $lang 'it' or 'eng' for sorting language
     * @return array
     */
    private function buildCategoryTree($categories, int $type, ?int $parentId = null, string $lang): array
    {
        $branch = [];

        foreach ($categories as $category) {
            if ($category->parent_id == $parentId && $category->type == $type) {
                if ($type == 0) {
                    $children = $this->buildCategoryTree($categories, 1, $category->id, $lang);

                    $branch[] = [
                        'id' => $category->id,
                        'label_eng' => $category->label_eng,
                        'label_ita' => $category->label_ita,
                        'categories' => $this->sortTree($children, $lang),
                    ];
                } elseif ($type == 1) {
                    $children = $this->buildCategoryTree($categories, 2, $category->id, $lang);

                    $branch[] = [
                        'id' => $category->id,
                        'label_eng' => $category->label_eng,
                        'label_ita' => $category->label_ita,
                        'sub_categories' => $this->sortTree($children, $lang),
                    ];
                } elseif ($type == 2) {
                    $branch[] = [
                        'id' => $category->id,
                        'label_eng' => $category->label_eng,
                        'label_ita' => $category->label_ita,
                    ];
                }
            }
        }

        Log::info('Branch Before Sorting', ['branch' => $branch]);

        $sortedBranch = $this->sortTree($branch, $lang);

        Log::info('Branch After Sorting', ['branch' => $sortedBranch]);

        return $sortedBranch;
    }

    /**
     * Sort a tree structure alphabetically by the selected language
     * @param array $tree
     * @param string $lang 'it' or 'eng' for sorting language
     * @return array
     */
    private function sortTree(array $tree, string $lang): array
    {
        usort($tree, function ($a, $b) use ($lang) {
            $key = $lang === 'ita' ? 'label_ita' : 'label_eng';
            return strcmp(strtolower($a[$key]), strtolower($b[$key]));
        });

        return $tree;
    }

    // ******************************************************
    //********************** PRIVATE ************************
    //******************************************************

    /**
     *  Read category by id
     * @param Request $request
     * @param $id
     * @return Category
     */
    public function getCategory(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Reading category with id: ', ['id' => $request->$id]);

        try {
            $category = Category::with('parentCategory:id,label_ita')
            ->find($id);

            if (!$category) {
                $this->log('error', 'Categoria non trovata', ['id' => $id]);
                return response()->json([
                    "status" => "error",
                    'message' => 'Categoria non trovata'
                ], 204);
            }

            $response = [
                'id' => $category->id,
                'label_ita' => $category->label_ita,
                'label_eng' => $category->label_eng,
                'parent' => $category->parentCategory ? $category->parentCategory->label_ita : null,
                'parent_id' => $category->parentCategory ? $category->parentCategory->id : null,
                'type' => $category->type,
            ];

            $products = $category->products()
                ->select('*')
                ->paginate($request->input('limit', 10));

            $linked_products = (object) [
                'total' => $products->total(),
                'current_page' => $products->currentPage(),
                'data' => [],
            ];

            foreach ($products as $product) {
                $product = [
                    'id' => $product->id,
                    'label_ita' => $product->label_ita,
                ];
                array_push($linked_products->data, $product);
            }

            $response['products'] = $linked_products;

            return response()->json(['data' => $response], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read category error: ', ['exception' => $e]);
            return response()->json(['fail' => $e->getMessage()], 500);
        } 
    }

    /**
     * Read all paginated categories
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaginatedCategories(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Read all paginated categories', ['request' => $request->all()]);

        if ($request->has('category_id')) {
            $categoryId = $request->category_id;
        } else {
            $categoryId = null;
        }

        try {
            $orderBy = $request->has('orderBy') ? $request->orderBy : 'updated_at';
            $orderDirection = $request->has('order') ? $request->order : 'desc';
            $limit = $request->has('limit') ? $request->limit : 10;

            $categories = Category::orderBy($orderBy, $orderDirection)
                ->select('id', 'label_ita', 'updated_at')
                ->where('type', $categoryId)
                ->paginate($limit);

            if ($categories->isEmpty()) {
                $this->log('error', 'No categories found');
            }

            return response()->json($categories, 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read all paginated categories error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }

    public function getAllTypeCategories(Request $request)
    {
        try {
            $type = $request->input('type');
            
            $categories = Category::when($type, function ($query, $type) {
                return $query->where('type', $type);
            })
            ->orderBy('label_ita', 'asc')
            ->get();

            return response()->json(['data' => $categories], 200);
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Read all paginated categories by type
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTypeCategories(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Read all filtered by type Categories', ['request' => $request->all()]);

        try {
            $type = $request->input('type');
            $categoriesQuery = Category::query();

            if ($type !== null) {
                $categoriesQuery->where('type', $type);
            }

            $page = $request->query('page', 1);
            $limit = $request->query('limit', 10);
            $orderBy = $request->query('orderBy', 'updated_at');
            $order = $request->query('order', 'desc');

            $categories = $categoriesQuery->orderBy($orderBy, $order)
                ->paginate($limit, ['*'], 'page', $page);

            if ($categories->isEmpty()) {
                $this->log('error', 'No categories found');
            }

            return response()->json($categories, 200);
        } catch (\Exception $e) {
            $this->log('error', 'Read filtered by type Categories error: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } 
    }

    /**
     * Create a new category
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Creating new category: ', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'type' => 'nullable|integer',
                'parent_id' => 'nullable|numeric',
                'products' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Create category - Validation Error', ['validator' => $validator->errors()]);
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => $validator->errors()
                    ],
                    422
                );
            }

            $query = Category::where('label_ita', $request->label_ita)
                ->where('label_eng', $request->label_eng)
                ->where('type', $request->type);

            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            $existingCategory = $query->first();

            if ($existingCategory) {
                $this->log('error', 'Create new category Error: ', ['message' => 'Elemento duplicato, Condizione con nome:  "' . $existingCategory->label_ita . '"e nome(eng): "' . $existingCategory->label_eng . '" già esistente']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Elemento duplicato, Condizione con nome:  "' . $existingCategory->label_ita . '"e nome(eng): "' . $existingCategory->label_eng . '" già esistente'
                ], 422);
            }

            $categoryData = [
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'type' => $request->type,
                'parent_id' => $request->parent_id,
            ];

            $category = Category::create($categoryData);

            if ($request->has('products')) {
                $products = $request->input('products');

                $result = CategoryProductUtils::createOrUpdateCategoryProductRelations($products, $category->id, 1);

                if ($result !== 'success') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante la creazione delle relazioni categoria-prodotto',
                    ], 500);
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Categoria creata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Error creating category: ', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('category');
        }
    }

    /**
     * Edit a category
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function editCategory(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Update category with id', ['id' => $id]);
        $this->log('info', 'Request data: ', ['request' => $request->all()]);

        try {
            $validator = Validator::make($request->all(), [
                'label_ita' => 'required|string',
                'label_eng' => 'required|string',
                'type' => 'nullable|integer',
                'parent_id' => 'nullable|integer',
                'products' => 'sometimes|string',
            ]);

            if ($validator->fails()) {
                $this->log('error', 'Update Category - Validation Error', ['validator' => $validator->errors()]);
                return response()->json(
                    [
                        'status' => 'error',
                        'message' => $validator->errors()
                    ],
                    422
                );
            }

            if (!$id) {
                $this->log('error', 'Category id not provided');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Id categoria non fornito'
                ], 400);
            }

            $categoryFind = Category::find($id);

            if (!$categoryFind) {
                $this->log('error', 'Category not found', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Categoria non trovata'
                ], 404);
            }

            $query = Category::where('label_ita', $request->label_ita)
                ->where('label_eng', $request->label_eng)
                ->where('id', '!=', $id)
                ->where('type', $request->type);

            if ($request->has('parent_id')) {
                $query->where('parent_id', $request->parent_id);
            } else {
                $query->whereNull('parent_id');
            }

            $existingCategory = $query->first();

            if ($existingCategory) {
                $this->log('error', 'Update category Error: ', ['message' => 'Elemento duplicato, Condizione con nome:  "' . $existingCategory->label_ita . '"e nome(eng): "' . $existingCategory->label_eng . '" già esistente']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Elemento duplicato, Condizione con nome:  "' . $existingCategory->label_ita . '"e nome(eng): "' . $existingCategory->label_eng . '" già esistente'
                ], 422);
            }

            $categoryFind->update([
                'label_ita' => $request->label_ita,
                'label_eng' => $request->label_eng,
                'type' => $request->type,
                'parent_id' => $request->parent_id == -1 ? null : $request->parent_id,
            ]);

            // Products association
            $products = $request->input('products');
            if ($request->has('products')) {

                $result = CategoryProductUtils::createOrUpdateCategoryProductRelations($products, $categoryFind->id, 1);

                if ($result !== 'success') {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Errore durante l\'aggiornamento delle relazioni categoria-prodotto',
                    ], 500);
                }
            } 

            return response()->json([
                'status' => 'success',
                'message' => 'Categoria aggiornata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Error updating Category', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('category');
        }
    }

    /**
     * Delete a category
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteCategory(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Deleting Category with id', ['id' => $id]);

        try {

            if (!$id) {
                $this->log('error', 'Id categoria non fornito');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Id categoria non fornito'
                ], 400);
            }

            $category = Category::find($id);

            if (!$category) {
                $this->log('error', 'Categoria non trovata', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Categoria non trovata'
                ], 404);
            }

            // Product association validation
            if ($category->products()->exists()) {
                $this->log('error', 'La categoria ha dei prodotti associati, impossibile eliminare', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'La categoria ha dei prodotti associati, impossibile eliminare'
                ], 500);
            }

            // Category association validation
            if ($category->childCategories()->exists()) {
                $this->log('error', 'La categoria ha delle sottocategorie associate, impossibile eliminare', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'La categoria ha delle sottocategorie associate, impossibile eliminare'
                ], 500);
            }

            $category->delete();

            $this->log('info', 'Category deleted');

            return response()->json([
                'status' => 'success',
                'message' => 'Categoria eliminata con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Delete category error:', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('category');
        }
    }

    /**
     * Remove all products associated with a category
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeProductsAssociation(Request $request, $id)
    {
        $this->logMethodAndUri($request);
        $this->log('info', 'Removing products association from category with id: ', ['id' => $id]);

        try {

            if (!$id) {
                $this->log('error', 'Id categoria non fornito');
                return response()->json([
                    'status' => 'error',
                    'message' => 'Id categoria non fornito'
                ], 400);
            }

            $category = Category::find($id);

            if (!$category) {
                $this->log('error', 'Categoria non trovata', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Categoria non trovata'
                ], 404);
            }

            $productAssociations = CategoryProduct::where('category_id', $id)->count();

            if ($productAssociations == 0) {
                $this->log('error', 'La categoria non ha prodotti associati', ['id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'La categoria non ha prodotti associati'
                ], 400);
            }

            CategoryProduct::where('category_id', $id)->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Associazioni prodotti rimossi con successo'
            ], 200);
        } catch (\Exception $e) {
            $this->log('error', 'Error removing products association', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('category');
        }
    }

    /**
     * Get all child categories of a category 
     * Used in ProductController to get all products associated with a category and its children
     * @param $id
     * @return Category list
     */
    public static function getAllChildCategories($categoryId)
    {
        $arr = [];
        $arr[] = $categoryId;
        $Macrocategory = Category::find($categoryId);
        
        $categories = $Macrocategory->childCategories()->get();

        foreach ($categories as $category) {
            $allCategories = $category->childCategories()->get();
            $arr[] = $category->id;
            foreach($allCategories as $subCategory){
                $arr[] = $subCategory->id;
            }
        }
        return $arr;
    }
}
