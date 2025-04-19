<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;

class SearchController extends Controller
{
    /**
     * Entity models for the search
     * @var array
     */
    protected $entityModels = [
        'product' => \App\Models\Product::class,
        'macro-category' => \App\Models\Category::class,
        'category' => \App\Models\Category::class,
        'sub-category' => \App\Models\Category::class,
        'faq' => \App\Models\Faq::class,
        'contact' => \App\Models\Contact::class,
        'show' => \App\Models\Show::class,
    ];

    /**
     * Abstract Controller constructor override
     */
    public function __construct()
    {
        parent::__construct('search');
        $this->loggingEnabled = env('LOG_SEARCH', $this->loggingEnabled);
    }

    // ****************************************************************
    // ******************* PUBLIC ROUTES (FE) *************************
    // ****************************************************************

    /**
     * Search for products by label
     * @param Request $request
     * @return Response
     */
    public function searchProducts(Request $request)
    {
        $query = $request->input('query');
        if (strlen($query) < 3) {
            return response()->json(['error' => 'Query must be at least 3 characters long'], 400);
        }

        $perPage = $request->input('limit', 10);
        $orderBy = $request->input('order_by', 'created_at');
        $order = $request->input('order', 'asc');
        $lang = $request->input('lang');

        try {
            $products = Product::where(function ($q) use ($query, $lang) {
                if ($lang == 'ita') {
                    $q->whereRaw('LOWER(label_ita) LIKE ?', ['%' . strtolower($query) . '%']);
                } else {
                    $q->WhereRaw('LOWER(label_eng) LIKE ?', ['%' . strtolower($query) . '%']);
                }
            })
                ->where('draft', false)
                ->where('deleting', false)
                ->select('id', 'label_ita', 'label_eng', 'price')
                ->orderBy($orderBy, $order)
                ->paginate($perPage);

            $products->getCollection()->transform(function ($product) {
                $pictureUrl = $product->images->isNotEmpty() ? url(Storage::url($product->images->first()->path)) : null;
                return [
                    'id' => $product->id,
                    'label_ita' => $product->label_ita,
                    'label_eng' => $product->label_eng,
                    'formatted_updated_at' => $product->formatted_updated_at,
                    'image_url' => $pictureUrl,
                    'price' => $product->price,
                ];
            });

            return response()->json($products);
        } catch (\Exception $e) {
            $this->log('error', 'searchProducts Error', ['exception' => $e]);
            return response()->json(['error' => 'An error occurred while searching for products'], 500);
        }
    }

    // ****************************************************************
    // ******************* PRIVATE ROUTES (BE) ************************
    // ****************************************************************

    /**
     * Search for entities by label for the admin panel
     * @param Request $request
     * @param string $entity - The entity to search for
     * @param string $query - The search query
     * @return Response
     */
    public function searchEntity(Request $request)
    {
        // $this->logMethodAndUri($request);

        $entity = $request->input('entity');
        $query = $request->input('query');
        if (strlen($query) < 3) {
            $this->log('error', 'Search entity error: ', ['error' => 'Query must be at least 3 characters long']);
            return response()->json(['error' => 'Query must be at least 3 characters long'], 400);
        }

        if (!isset($this->entityModels[$entity])) {
            $this->log('error', 'Entity not found:', ['entity' => $entity]);
            return response()->json(['error' => 'Entity not found'], 404);
        }

        $perPage = $request->input('limit', 10); 
        $orderBy = $request->input('order_by', 'created_at');
        $order = $request->input('order', 'asc');

        $this->log('info', 'Searching entity:' . $entity . ' with query: ', ['query' => $query]);

        try {
            $model = $this->entityModels[$entity];

            $results = $model::where(function ($q) use ($query) {
                $q->whereRaw('LOWER(label_ita) LIKE ?', ['%' . strtolower($query) . '%']);
            });

            if ($entity === 'macro-category') {
                $results = $results->where('type', 0);
            } elseif ($entity === 'category') {
                $results = $results->where('type', 1);
            } elseif ($entity === 'sub-category') {
                $results = $results->where('type', 2);
            }

            if ($entity === 'product') {
                $results = $results->select('id', 'label_ita', 'updated_at', 'creator');
            } else {
                $results = $results->select('id', 'label_ita', 'updated_at');
            }

            $results = $results->orderBy($orderBy, $order)
                ->paginate($perPage);

            $results->getCollection()->transform(function ($item) {
                $item->formatted_updated_at = $item->updated_at->format('d-m-Y H:i:s');
                return $item;
            });

            if ($results->isEmpty()) {
                $this->log('info', 'Search entity - No results found for entity: ' . $entity);
            }

            return response()->json($results);
        } catch (\Exception $e) {
            $this->log('error', 'Search entity Error', ['exception' => $e]);
            return response()->json(['error' => 'An error occurred while searching for the entity'], 500);
        } 
    }
}
