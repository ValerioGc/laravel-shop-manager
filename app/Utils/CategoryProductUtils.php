<?php

namespace App\Utils;

use Illuminate\Support\Facades\Log;
use App\Models\CategoryProduct;
use App\Classes\ErrorHandler;

/**
 * Utility class to manage the category-product relations
 * @package App\Utils
 * @date 2021-06-30
 * @version 1.0
 */
class CategoryProductUtils
{
    /**
     * Create or update the category-product relations
     * @param string $array - JSON array of category ids or product ids
     * @param int $id - product or category id
     * @param int $type - 1: category, 2: product
     * @return string
     */
    public static function createOrUpdateCategoryProductRelations($array, $id, $type)
    {
        if (!$id) {
            return 'error';
        }

        if (!$array) {
            return 'error';
        }

        $entity = $type == 1 ? 'categories' : 'products';
        Log::channel($entity)->info('Adding' . $type == 1 ? 'products':'categories', ['categories' => $array]);

        try {
            $newData = json_decode($array, true);
            if (!is_array($newData)) {
                Log::channel($entity)->error('Invalid JSON array provided.', ['array' => $array]);
                throw new \Exception('Invalid JSON array provided.');
            }

            $existingAssociations = [];

            if ($type == 1) {
                $existingAssociations = CategoryProduct::where('category_id', $id)->get();
            } else if ($type == 2) {
                $existingAssociations = CategoryProduct::where('product_id', $id)->get();
            } else {
                Log::channel($entity)->error('Invalid type provided.', ['type' => $type]);
                throw new \Exception('Invalid type provided.');
            }

            $managedAssociations = [];

            foreach ($newData as $element) {
                if ($type == 1) {
                    $existingAssociation = $existingAssociations->firstWhere('product_id', $element);
                } else if ($type == 2) {
                    $existingAssociation = $existingAssociations->firstWhere('category_id', $element);
                }

                if ($existingAssociation) {
                    $existingAssociation->update([
                        'product_id' => $type == 1 ? $element : $id,
                        'category_id' => $type == 1 ? $id : $element
                    ]);
                } else {
                    CategoryProduct::create([
                        'product_id' => $type == 1 ? $element : $id,
                        'category_id' => $type == 1 ? $id : $element
                    ]);
                }

                $managedAssociations[] = $element;
            }

            $existingAssociations->each(function ($association) use ($managedAssociations, $type) {
                if ($type == 1 && !in_array($association->product_id, $managedAssociations)) {
                    $association->delete();
                } else if ($type == 2 && !in_array($association->category_id, $managedAssociations)) {
                    $association->delete();
                }
            });

            return 'success';
        } catch (\Exception $e) {
            Log::channel($entity)->error('Error creating/updating category-product relations', ['exception' => $e]);
            return ErrorHandler::handleApiInternalServerError(__METHOD__, $e);
        }
    }
}
