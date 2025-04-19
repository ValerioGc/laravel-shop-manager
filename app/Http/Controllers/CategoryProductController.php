<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Classes\ErrorHandler;
use App\Models\CategoryProduct;
use App\Utils\CategoryProductUtils;
use Illuminate\Support\Facades\Validator;

/**
 * REST API controller for managing the relations between categories and products.
 * @package App\Http\Controllers
 * @date 2021-06-01
 * @version 1.0
 */
class CategoryProductController extends Controller
{
    /**
     * Create or update the relations between a category and a list of products.
     * @param Request $request The request object.
     * @param int $categoryId The category ID.
     * @return \Illuminate\Http\JsonResponse The response object.
     */
    public function createOrUpdateCategoryRelations(Request $request, $categoryId)
    {
        try {
            if (!$categoryId) {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => 'ID categoria mancante.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'products' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => $validator->errors()
                ], 422);
            }

            $result = CategoryProductUtils::createOrUpdateCategoryProductRelations($request->products, $categoryId, 1);

            if ($result !== 'success') {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => 'Errore durante l\'aggiornamento delle relazioni categoria-prodotto',
                ], 500);
            }

            return response()->json([
                'status' => 'successo',
                'messaggio' => 'Associazioni create/aggiornate con successo.'
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('category');
        }
    }


    /**
     * Create or update the relations between a product and a list of categories.
     * @param Request $request The request object.
     * @param int $productId The product ID.
     * @return \Illuminate\Http\JsonResponse The response object.
     */
    public function createOrUpdateProductRelations(Request $request, $productId)
    {
        try {
            if (!$productId) {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => 'ID prodotto mancante.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'categories' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => $validator->errors()
                ], 422); 
            }

            $result = CategoryProductUtils::createOrUpdateCategoryProductRelations($request->categories, $productId, 2);

            if ($result !== 'success') {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => 'Errore durante l\'aggiornamento delle relazioni prodotto-categoria',
                ], 500);
            }

            return response()->json([
                'status' => 'successo',
                'messaggio' => 'Associazioni create/aggiornate con successo.'
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } finally {
            $this->clearEntityCache('product');
        }
    }


    /**
     * Get the relations between a category and its products.
     * @param int $id The category ID.
     * @return \Illuminate\Http\JsonResponse The response object.
     */
    public function getCategoryRelations($id)
    {
        try {

            if (!$id) {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => 'ID categoria mancante.'
                ], 400);
            }

            $categoryProducts = CategoryProduct::where('category_id', $id)
                ->with('product')
                ->get();

            if ($categoryProducts->isEmpty()) {
                return response()->json([
                    'data' => [
                        'category_id' => $id,
                        'products' => []
                    ]
                ], 200);
            }

            $products = $categoryProducts->map(function ($categoryProduct) {
                return [
                    'id' => $categoryProduct->product_id,
                    'label_ita' => $categoryProduct->product->label_ita
                ];
            });

            return response()->json([
                'data' => [
                    'category_id' => $id,
                    'products' => $products
                ]
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        } 
    }

    /**
     * Get the relations between a product and its categories.
     * @param int $id The product ID.
     * @return \Illuminate\Http\JsonResponse The response object.
     */
    public function getProductRelations($id)
    {

        try {

            if (!$id) {
                return response()->json([
                    'status' => 'errore',
                    'messaggio' => 'ID prodotto mancante.'
                ], 400);
            }

            $productCategories = CategoryProduct::where('product_id', $id)
                ->with('category')
                ->get();

            if ($productCategories->isEmpty()) {
                return response()->json([
                    'data' => [
                        'product' => $id,
                        'categories' => []
                    ]
                ], 200);
            }

            $categories = $productCategories->map(function ($productCategory) {
                return [
                    'id' => $productCategory->category_id,
                    'label_ita' => $productCategory->category->label_ita
                ];
            });

            return response()->json([
                'data' => [
                    'product' => $id,
                    'categories' => $categories
                ]
            ], 200);
        } catch (\Exception $e) {
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }
}