<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JsonController;
use App\Http\Controllers\ShowController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConditionController;
use App\Http\Controllers\CategoryProductController;
use App\Http\Controllers\ProtectedFileController;
use App\Http\Controllers\TranslateController;


// ***************************** CSP VIOLATION REPORT *****************************
Route::post('/csp-violation', function (Request $request) { 
    Log::channel('security')->info('CSP Violation: ', $request->all());
    return response()->json(['status' => 'ok']);
});


// ******************* AUTH ************************ 
Route::prefix('auth')->middleware('auth:sanctum')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login'])->name('login')->withoutMiddleware('auth:sanctum');
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/users',[AuthController::class, 'getAllUsers']);
    Route::post('/delete-user/{id}',[AuthController::class, 'deleteUser']);
    Route::get('/user-details', [AuthController::class, 'user']);
    Route::post('/update-profile', [AuthController::class, 'updateProfile']);
    Route::get('protected/images/users/{filename}', [ProtectedFileController::class, 'show'])->name('protected.image');

});


// ******************* PUBLIC ROUTES (FE) ************************
Route::prefix('public')->group(function () {

    //  Products
    Route::prefix('product')->group(function () {
        Route::get('/get/{id}', [ProductController::class, 'getProduct']);
        Route::get('/filter/paginate', [ProductController::class, 'filterProducts']);
    });

    //  Categories 
    Route::get('/category/{lang}', [CategoryController::class, 'getAllCategories']);

    //  Search 
    Route::get('/search/products', [SearchController::class, 'searchProducts']);

    //  FAQ 
    Route::get('/faq', [FaqController::class, 'getAllFaqs']);

    // Show
    Route::prefix('show')->group(function () {
        Route::get('/old', [ShowController::class, 'getPaginatedOldShows']);
        Route::get('/new', [ShowController::class, 'getNewShows']);
        Route::get('/get/{id}', [ShowController::class, 'getShow']);
    });

    //  Contacts 
    Route::prefix('contact')->group(function () {
        Route::get('/', [ContactController::class, 'getAllContacts']);
        Route::get('/specific', [ContactController::class, 'getFilteredContacts']);
    });

    // Config
    Route::get('/config/read', [JsonController::class, 'readJson']);
});

// ******************* PRIVATE ROUTES ************************
Route::prefix('private')->middleware('auth:sanctum')->group(function () {

    // TODO Sistemare rotte
    Route::prefix('user')->group(function () {
        Route::get('/paginate', [AuthController::class, 'getAllPaginatedUsers']);
        Route::post('/new', [AuthController::class, 'register']);
        Route::get('/{id}', [AuthController::class, 'user']);
        Route::post('/delete/{id}', [AuthController::class, 'deleteUser']);
        Route::post('/edit/{id}', [AuthController::class, 'updateProfile']);
    });


    // ***************************** TRANSLATE *****************************
    Route::post('/translate', [TranslateController::class, 'translateText']);

    // ***************************** FAQS *****************************
    Route::prefix('faq')->group(function () {
        Route::get('/paginate', [FaqController::class, 'getAllPaginateFaq']);
        Route::get('/get/{id}', [FaqController::class, 'getFaq']);
        Route::post('/new', [FaqController::class, 'create']);
        Route::post('/edit/{id}', [FaqController::class, 'editFaq']);
        Route::delete('/delete/{id}', [FaqController::class, 'deleteFaq']);
    });

    //  ***************************** Conditions  *****************************
    Route::prefix('condition')->group(function () {
        Route::get('/', [ConditionController::class, 'getAllConditions']);
        Route::get('/paginate', [ConditionController::class, 'getAllPaginateCondition']);
        Route::get('/get/{id}', [ConditionController::class, 'getCondition']);
        Route::post('/new', [ConditionController::class, 'create']);
        Route::post('/edit/{id}', [ConditionController::class, 'editCondition']);
        Route::delete('/delete/{id}', [ConditionController::class, 'deleteCondition']);
    });

    // ***************************** Contacts *****************************
    Route::prefix('contact')->group(function () {
        Route::get('/paginate', [ContactController::class, 'getAllPaginateContact']);
        Route::get('/get/{id}', [ContactController::class, 'getContact']);
        Route::post('/new', [ContactController::class, 'create']);
        Route::post('/edit/{id}', [ContactController::class, 'editContact']);
        Route::delete('/delete/{id}', [ContactController::class, 'deleteContact']);
    });

    // ***************************** Categories *****************************
    Route::prefix('category')->group(function () {
        Route::get('/paginate', [CategoryController::class, 'getPaginatedCategories']);
        Route::get('/get/{id}', [CategoryController::class, 'getCategory']);
        Route::get('/all/{type}', [CategoryController::class, 'getAllTypeCategories']);
        Route::post('/new', [CategoryController::class, 'create']);
        Route::post('/edit/{id}', [CategoryController::class, 'editCategory']);
        Route::delete('/delete/{id}', [CategoryController::class, 'deleteCategory']);
        Route::get('/paginate/type', [CategoryController::class, 'getTypeCategories']);
        Route::post('/{id}/product/unlink', [CategoryController::class, 'removeProductsAssociation']);
    });

    // ***************************** Category Product Associations ***************************** 
    Route::prefix('catProduct')->group(function () {
        Route::get('/category/{id}/product/get', [CategoryProductController::class, 'getCategoryRelations']);
        Route::get('/product/{id}/category/get', [CategoryProductController::class, 'getProductRelations']);
        Route::post('/product/category/{id}', [CategoryProductController::class, 'createOrUpdateProductRelations']);
        Route::post('/category/product/{id}', [CategoryProductController::class, 'createOrUpdateCategoryRelations']);
    });

    // *****************************API PRODUCTS *****************************
    Route::prefix('product')->group(function () {
        Route::get('/', [ProductController::class, 'getAllAscProducts']);
        Route::get('/paginate', [ProductController::class, 'getAllPaginateProducts']);
        Route::get('/filter/paginate', [ProductController::class, 'filterProducts']);
        Route::get('/get/{id}', [ProductController::class, 'getProduct']);
        Route::post('/new', [ProductController::class, 'create']);
        Route::post('/edit/{id}', [ProductController::class, 'editProduct']);
        Route::post('/clone/{id}', [ProductController::class, 'cloneProduct']);
        // ***************************** DRAFT *****************************
        Route::post('/draft/{id}', [ProductController::class, 'draftProduct']);
        // ***************************** DELETE OPTIONS *****************************
        Route::get('/deleting/paginate', [ProductController::class, 'getAllDeletingPaginateProducts']);
        Route::post('/soft/delete/{id}', [ProductController::class, 'softDeleteProduct']);
        Route::post('/restore/soft/{id}', [ProductController::class, 'restoreSoftDeleteProduct']);
        Route::delete('/delete/{id}', [ProductController::class, 'deleteProduct']);
        Route::post('/delete/empty', [ProductController::class, 'deleteTrashProduct']);
    });

    // ***************************** Config *****************************
    Route::prefix('config')->group(function () {
        Route::get('/read', [JsonController::class, 'readJson']);
        Route::post('/edit', [JsonController::class, 'writeJson']);
    });    
    
    //  ***************************** API SHOWS ***************************** 
    Route::prefix('show')->group(function () {
        Route::get('/paginate', [ShowController::class, 'getAllShowPaginated']);
        Route::get('/get/{id}', [ShowController::class, 'getShow']);
        Route::post('/new', [ShowController::class, 'create']);
        Route::post('/edit/{id}', [ShowController::class, 'edit']);
        Route::delete('/delete/{id}', [ShowController::class, 'deleteShow']);
    });


    // ***************************** API SEARCH *****************************
    Route::get('/search', [SearchController::class, 'searchEntity']);

});

