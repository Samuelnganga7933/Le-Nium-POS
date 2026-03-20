<?php

/**
 * PRODUCT CONTROLLER INTEGRATION GUIDE
 * 
 * This file shows the exact code additions needed to ProductController
 * to fully integrate PART 2 features.
 * 
 * Location: app/Http/Controllers/ProductController.php
 * 
 * Required: Copy these methods into your existing ProductController
 */

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\SKUGenerationService;
use App\Services\CSVImportService;
use App\Services\ImageUploadService;
use App\Services\ProfitCalculationService;
use App\Services\CategoryManagementService;
use Illuminate\Http\Request;

class ProductContollerIntegrationReference
{
    /**
     * INTEGRATION 1: Update store() method
     * 
     * This shows how to integrate SKU generation and image handling
     * into the existing store() method.
     * 
     * FIND YOUR CURRENT store() METHOD AND UPDATE IT:
     */
    public function store(Request $request)
    {
        // EXISTING VALIDATION CODE
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:products',
            'barcode' => 'nullable|string|unique:products',
            'category' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier' => 'nullable|string',
            'unit' => 'nullable|string',
            'brand' => 'nullable|string',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'expiry_date' => 'nullable|date',
            // NEW VALIDATION
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'sku' => 'nullable|string|unique:products',
        ]);

        // NEW: Auto-generate SKU if not provided
        if (empty($validated['sku'])) {
            $skuService = new SKUGenerationService();
            $validated['sku'] = $skuService->generate($validated['category'] ?? 'other');
        }

        // NEW: Handle image upload
        if ($request->hasFile('image')) {
            $imageService = new ImageUploadService();
            $imagePath = $imageService->upload($request->file('image'));
            if ($imagePath) {
                $validated['image'] = $imagePath;
            }
        }

        // EXISTING: Create product
        $product = Product::create($validated);

        return redirect()->route('products.show', $product)
            ->with('success', 'Product created successfully!');
    }

    /**
     * INTEGRATION 2: Update update() method
     * 
     * Add stock adjustment and image handling to existing update() method
     */
    public function update(Request $request, Product $product)
    {
        // EXISTING VALIDATION
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:products,name,' . $product->id,
            'category' => 'nullable|string|max:50',
            'price' => 'required|numeric|min:0.01',
            'cost_price' => 'nullable|numeric|min:0',
            'reorder_level' => 'required|integer|min:0',
            'supplier' => 'nullable|string',
            'brand' => 'nullable|string',
            'unit' => 'nullable|string',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'description' => 'nullable|string',
            'barcode' => 'nullable|string|unique:products,barcode,' . $product->id,
            'expiry_date' => 'nullable|date',
            // NEW VALIDATION
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif,webp|max:2048',
            'stock_adjustment' => 'nullable|integer',
            'adjustment_type' => 'nullable|in:add,subtract,set',
            'remove_image' => 'nullable|boolean',
        ]);

        // NEW: Handle stock adjustment
        if ($request->filled('stock_adjustment') && $request->filled('adjustment_type')) {
            $adjustment = (int) $request->stock_adjustment;
            $type = $request->adjustment_type;

            switch ($type) {
                case 'add':
                    $validated['stock'] = $product->stock + $adjustment;
                    break;
                case 'subtract':
                    $validated['stock'] = max(0, $product->stock - $adjustment);
                    break;
                case 'set':
                    $validated['stock'] = $adjustment;
                    break;
            }
        }

        $imageService = new ImageUploadService();

        // NEW: Handle image removal
        if ($request->remove_image) {
            $imageService->delete($product->image);
            $validated['image'] = null;
        }

        // NEW: Handle image replacement
        if ($request->hasFile('image')) {
            $imagePath = $imageService->upload(
                $request->file('image'),
                $product->image  // Pass old image for deletion
            );
            if ($imagePath) {
                $validated['image'] = $imagePath;
            }
        }

        // EXISTING: Update product
        $product->update($validated);

        return redirect()->route('products.show', $product)
            ->with('success', 'Product updated successfully!');
    }

    /**
     * INTEGRATION 3: Create NEW importForm() method
     * 
     * Shows the CSV import form
     */
    public function importForm()
    {
        $csvService = new CSVImportService();
        $sampleCsv = CSVImportService::getSampleCSV();

        return view('admin.products.import', [
            'sampleCsv' => $sampleCsv,
        ]);
    }

    /**
     * INTEGRATION 4: Create NEW import() method
     * 
     * Handles CSV file import
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:5120',
            'dry_run' => 'nullable|boolean',
        ]);

        $csvService = new CSVImportService();
        $dryRun = $request->boolean('dry_run', false);

        $result = $csvService->import($request->file('file'), $dryRun);

        if (!$result['success']) {
            return back()
                ->withErrors($result['errors'])
                ->with('warnings', $result['warnings']);
        }

        $message = sprintf(
            'Successfully imported %d products',
            $result['imported']
        );

        if ($result['skipped'] > 0) {
            $message .= sprintf(' (Skipped %d)', $result['skipped']);
        }

        return back()
            ->with('success', $message)
            ->with('warnings', $result['warnings']);
    }

    /**
     * INTEGRATION 5: Create NEW sampleCSV() method
     * 
     * Allows users to download CSV template
     */
    public function sampleCSV()
    {
        $csv = CSVImportService::getSampleCSV();

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="products_sample.csv"',
        ]);
    }

    /**
     * INTEGRATION 6: Create NEW category() method
     * 
     * Shows products filtered by category
     */
    public function category($category)
    {
        $categoryService = new CategoryManagementService();

        // Validate category exists
        if (!$categoryService->isValid($category)) {
            abort(404, 'Category not found');
        }

        $filters = [
            'search' => request('search'),
            'sort' => request('sort', 'name'),
            'direction' => request('direction', 'asc'),
            'low_stock' => request('low_stock', false),
            'min_price' => request('min_price'),
            'max_price' => request('max_price'),
        ];

        $products = $categoryService->getProducts($category, $filters)
            ->paginate(20);

        $stats = $categoryService->getStatistics($category);
        $categoryInfo = $categoryService->getCategory($category);

        return view('admin.products.category', [
            'products' => $products,
            'category' => $category,
            'categoryInfo' => $categoryInfo,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    /**
     * INTEGRATION 7: Update show() method (Optional)
     * 
     * Add profit calculations to product detail view
     */
    public function show(Product $product)
    {
        $profitService = new ProfitCalculationService();
        $profitSummary = $profitService->getProductProfitSummary($product);
        $categoryService = new CategoryManagementService();
        $categoryInfo = $categoryService->getCategoryForProduct($product->id);

        return view('admin.products.show', [
            'product' => $product,
            'profitSummary' => $profitSummary,
            'categoryInfo' => $categoryInfo,
        ]);
    }

    /**
     * INTEGRATION 8: Create NEW profitAnalysis() method
     * 
     * Shows profitability dashboard
     */
    public function profitAnalysis()
    {
        $profitService = new ProfitCalculationService();
        $categoryService = new CategoryManagementService();

        // Get low margin products
        $products = Product::all();
        $analysis = $profitService->analyzeProfitability($products);

        // Get category profit stats
        $categoryStats = $categoryService->getDashboardStats();

        return view('admin.products.profit-analysis', [
            'analysis' => $analysis,
            'categoryStats' => $categoryStats,
        ]);
    }

    /**
     * INTEGRATION 9: Update destroy() method (Optional)
     * 
     * Add image cleanup when deleting product
     */
    public function destroy(Product $product)
    {
        // NEW: Delete associated image
        if ($product->image) {
            $imageService = new ImageUploadService();
            $imageService->delete($product->image);
        }

        // EXISTING: Delete product
        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully!');
    }
}

/**
 * =====================================================
 * ROUTES TO ADD TO routes/web.php
 * =====================================================
 * 
 * Add these routes to your web.php file:
 * 
 * // Product management (RESTful)
 * Route::resource('admin/products', 'ProductController');
 * 
 * // CSV import
 * Route::get('admin/products/import', 'ProductController@importForm')
 *     ->name('products.import-form');
 * Route::post('admin/products/import', 'ProductController@import')
 *     ->name('products.import');
 * Route::get('admin/products/import-sample', 'ProductController@sampleCSV')
 *     ->name('products.sample-csv');
 * 
 * // Category filtering
 * Route::get('admin/products/category/{category}', 'ProductController@category')
 *     ->name('products.category');
 * 
 * // Profit analysis
 * Route::get('admin/products/profit-analysis', 'ProductController@profitAnalysis')
 *     ->name('products.profit-analysis');
 */

/**
 * =====================================================
 * STEP-BY-STEP INTEGRATION INSTRUCTIONS
 * =====================================================
 * 
 * 1. OPEN YOUR ProductController
 *    File: app/Http/Controllers/ProductController.php
 * 
 * 2. ADD SERVICE IMPORTS AT TOP
 *    Copy the use statements from line 8-12 of this file
 * 
 * 3. UPDATE EXISTING METHODS
 *    - store() - Add SKU generation and image handling
 *    - update() - Add stock adjustment and image replacement
 *    - destroy() - Add image cleanup
 * 
 * 4. ADD NEW METHODS
 *    - importForm() - Show import form
 *    - import() - Process CSV import
 *    - sampleCSV() - Download template
 *    - category() - Filter by category
 *    - profitAnalysis() - Profit dashboard
 *    - show() - Add profit calculations (optional)
 * 
 * 5. ADD ROUTES
 *    Update routes/web.php with the routes listed above
 * 
 * 6. CREATE VIEWS
 *    - resources/views/admin/products/import.blade.php
 *    - resources/views/admin/products/category.blade.php
 *    - resources/views/admin/products/profit-analysis.blade.php (optional)
 * 
 * 7. TEST
 *    - Create a product with image
 *    - Upload CSV file
 *    - Filter by category
 *    - View profit analysis
 * 
 * =====================================================
 */

/**
 * =====================================================
 * SERVICE DEPENDENCY INJECTION PATTERN
 * =====================================================
 * 
 * Alternative: Type-hint services in constructor
 * 
 * public function __construct(
 *     private SKUGenerationService $skuService,
 *     private CSVImportService $csvService,
 *     private ImageUploadService $imageService,
 *     private ProfitCalculationService $profitService,
 *     private CategoryManagementService $categoryService,
 * ) {}
 * 
 * Then use: $this->skuService->generate(...)
 * 
 * Instead of: new SKUGenerationService()->generate(...)
 */

/**
 * =====================================================
 * ENVIRONMENT CONFIGURATION
 * =====================================================
 * 
 * Ensure these are set in .env:
 * 
 * FILESYSTEM_DISK=public
 * APP_URL=http://localhost:8000
 * 
 * Create storage symlink if needed:
 * php artisan storage:link
 * 
 * Set permissions:
 * chmod -R 755 storage/app/public
 * chmod -R 755 public/storage
 */

/**
 * =====================================================
 * QUICK REFERENCE: WHAT EACH SERVICE DOES
 * =====================================================
 * 
 * SKUGenerationService
 * └─ $service->generate('electronics')
 *    └─ Returns: 'ELEC-000001'
 * 
 * CSVImportService
 * └─ $service->import($file, $dryRun = false)
 *    └─ Returns: ['success' => bool, 'imported' => int, ...]
 * 
 * ImageUploadService
 * └─ $service->upload($file, $oldPath)
 *    └─ Returns: 'products/filename.jpg'
 * └─ $service->delete($path)
 *    └─ Also deletes thumbnails
 * 
 * ProfitCalculationService
 * └─ $service->getProductProfitSummary($product)
 *    └─ Returns: Complete profit analysis
 * 
 * CategoryManagementService
 * └─ $service->getProducts('electronics', $filters)
 *    └─ Returns: Filtered product collection
 */
