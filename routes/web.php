<?php

use App\Http\Controllers\BrandController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DealerApplicationController;
use App\Http\Controllers\DealerController;
use App\Http\Controllers\DealerPriceController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PriceListController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/apply', [DealerApplicationController::class, 'create'])->name('dealers.apply');
Route::post('/apply', [DealerApplicationController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('dealers.apply.store');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::patch('/users/{user}', [UserController::class, 'update'])->name('users.update');

    Route::get('/dealers', [DealerController::class, 'index'])->name('dealers.index');
    Route::get('/dealers/create', [DealerController::class, 'create'])->name('dealers.create');
    Route::post('/dealers', [DealerController::class, 'store'])->name('dealers.store');
    Route::get('/dealers/{dealer}', [DealerController::class, 'show'])->name('dealers.show');
    Route::get('/dealers/{dealer}/edit', [DealerController::class, 'edit'])->name('dealers.edit');
    Route::patch('/dealers/{dealer}', [DealerController::class, 'update'])->name('dealers.update');
    Route::post('/dealers/{dealer}/approve', [DealerController::class, 'approve'])->name('dealers.approve');
    Route::get('/dealers/{dealer}/prices', [DealerPriceController::class, 'index'])->name('dealers.prices.index');
    Route::post('/dealers/{dealer}/prices', [DealerPriceController::class, 'store'])->name('dealers.prices.store');
    Route::get('/dealers/{dealer}/prices/{dealerPrice}/edit', [DealerPriceController::class, 'edit'])->name('dealers.prices.edit');
    Route::patch('/dealers/{dealer}/prices/{dealerPrice}', [DealerPriceController::class, 'update'])->name('dealers.prices.update');
    Route::post('/dealers/{dealer}/reject', [DealerController::class, 'reject'])->name('dealers.reject');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders', [OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/approve', [OrderController::class, 'approve'])->name('orders.approve');
    Route::post('/orders/{order}/prepare', [OrderController::class, 'prepare'])->name('orders.prepare');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
    Route::post('/catalog/quick-order', [CatalogController::class, 'quickOrder'])->name('catalog.quick-order');

    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart', [CartController::class, 'store'])->name('cart.store');
    Route::patch('/cart/items/{item}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartController::class, 'destroy'])->name('cart.items.destroy');
    Route::delete('/cart', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('/products', [ProductController::class, 'store'])->name('products.store');
    Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
    Route::get('/products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::patch('/products/{product}', [ProductController::class, 'update'])->name('products.update');

    Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
    Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
    Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
    Route::patch('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');

    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
    Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
    Route::patch('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');

    Route::get('/warehouses', [WarehouseController::class, 'index'])->name('warehouses.index');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->name('warehouses.store');
    Route::get('/warehouses/{warehouse}/edit', [WarehouseController::class, 'edit'])->name('warehouses.edit');
    Route::patch('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->name('warehouses.update');

    Route::get('/price-lists', [PriceListController::class, 'index'])->name('price-lists.index');
    Route::post('/price-lists', [PriceListController::class, 'store'])->name('price-lists.store');
    Route::get('/price-lists/{priceList}', [PriceListController::class, 'show'])->name('price-lists.show');
    Route::get('/price-lists/{priceList}/edit', [PriceListController::class, 'edit'])->name('price-lists.edit');
    Route::patch('/price-lists/{priceList}', [PriceListController::class, 'update'])->name('price-lists.update');
    Route::post('/price-lists/{priceList}/items', [PriceListController::class, 'storeItem'])->name('price-lists.items.store');
    Route::get('/price-lists/{priceList}/items/{item}/edit', [PriceListController::class, 'editItem'])->name('price-lists.items.edit');
    Route::patch('/price-lists/{priceList}/items/{item}', [PriceListController::class, 'updateItem'])->name('price-lists.items.update');

    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    Route::get('/stock/create', [StockController::class, 'create'])->name('stock.create');
    Route::post('/stock', [StockController::class, 'store'])->name('stock.store');

    Route::get('/units', [UnitController::class, 'index'])->name('units.index');
    Route::post('/units', [UnitController::class, 'store'])->name('units.store');
    Route::get('/units/{unit}/edit', [UnitController::class, 'edit'])->name('units.edit');
    Route::patch('/units/{unit}', [UnitController::class, 'update'])->name('units.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
