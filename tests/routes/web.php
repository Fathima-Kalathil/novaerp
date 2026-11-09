<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

/*
 | One-time database setup for hosts without a shell (e.g. Vercel).
 | Open  https://your-app/setup/<SETUP_TOKEN>  once, then delete SETUP_TOKEN.
 */
Route::get('/setup/{token}', function (string $token) {
    $expected = (string) config('erp.setup_token');
    abort_unless($expected !== '' && hash_equals($expected, $token), 404);

    $log = '';
    try {
        Artisan::call('migrate', ['--force' => true]);
        $log .= Artisan::output();
        Artisan::call('db:seed', ['--force' => true]);
        $log .= Artisan::output();
        $log .= "\nSetup finished. Now REMOVE the SETUP_TOKEN environment variable and redeploy.";
    } catch (Throwable $e) {
        $log .= "\nSetup FAILED: ".$e->getMessage();
    }

    return response('<pre>'.e($log).'</pre>');
});

/*
 | Fallback so compiled assets also work on hosts that send every request to PHP.
 | (Normally the web server / Vercel CDN serves /build/* directly and this is never hit.)
 */
Route::get('/build/{path}', function (string $path) {
    $base = realpath(public_path('build'));
    $file = realpath(public_path('build/'.$path));
    abort_unless($base && $file && str_starts_with($file, $base.DIRECTORY_SEPARATOR) && is_file($file), 404);

    $mime = match (pathinfo($file, PATHINFO_EXTENSION)) {
        'js' => 'text/javascript',
        'css' => 'text/css',
        'json' => 'application/json',
        'svg' => 'image/svg+xml',
        'woff2' => 'font/woff2',
        default => 'application/octet-stream',
    };

    return response()->file($file, ['Content-Type' => $mime, 'Cache-Control' => 'public, max-age=31536000, immutable']);
})->where('path', '.*');

Route::get('/', fn () => redirect('/dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:10,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy']);
    Route::get('/dashboard', DashboardController::class);

    // Products can be viewed by every role (sales needs them), edited by warehouse/admin.
    Route::get('/products', [ProductController::class, 'index']);

    Route::middleware('role:warehouse')->group(function () {
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::post('/products/{product}/adjust', [ProductController::class, 'adjust']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy']);

        Route::resource('suppliers', SupplierController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('purchase-orders', PurchaseOrderController::class)
            ->parameters(['purchase-orders' => 'purchaseOrder'])
            ->only(['index', 'create', 'store', 'show']);
        Route::post('/purchase-orders/{purchaseOrder}/order', [PurchaseOrderController::class, 'order']);
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('/purchase-orders/{purchaseOrder}/cancel', [PurchaseOrderController::class, 'cancel']);
    });

    Route::middleware('role:sales')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('sales-orders', SalesOrderController::class)
            ->parameters(['sales-orders' => 'salesOrder'])
            ->only(['index', 'create', 'store', 'show']);
        Route::post('/sales-orders/{salesOrder}/confirm', [SalesOrderController::class, 'confirm']);
        Route::post('/sales-orders/{salesOrder}/ship', [SalesOrderController::class, 'ship']);
        Route::post('/sales-orders/{salesOrder}/cancel', [SalesOrderController::class, 'cancel']);

        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show']);
        Route::post('/invoices/{invoice}/pay', [InvoiceController::class, 'pay']);
    });

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->middleware('role:admin');
});
