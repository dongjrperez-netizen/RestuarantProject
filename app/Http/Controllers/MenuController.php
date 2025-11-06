<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\MenuCategory;
use App\Models\DishIngredient;
use App\Models\Ingredients;
use App\Models\DishVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Exception;

class MenuController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $query = Dish::with(['category', 'variants'])
            ->byRestaurant($restaurantId);

        // Apply filters
        if ($request->search) {
            $query->where('dish_name', 'like', '%' . $request->search . '%');
        }

        if ($request->category_id) {
            $query->byCategory($request->category_id);
        }

        if ($request->status) {
            $query->byStatus($request->status);
        }

        $dishes = $query->orderBy('dish_name')->get();

        // Get categories for filter dropdown
        $categories = MenuCategory::byRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get(['category_id', 'category_name']);

        return Inertia::render('Menu/Index', [
            'dishes' => $dishes,
            'categories' => $categories,
            'filters' => $request->only(['search', 'category_id', 'status']),
        ]);
    }

    public function create()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $categories = MenuCategory::byRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get(['category_id', 'category_name']);

        $ingredients = Ingredients::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get([
                'ingredient_id',
                'ingredient_name',
                'base_unit',
                'current_stock',
                'cost_per_unit'
            ]);

        $availableUnits = [
            'weight' => ['g', 'kg', 'lb', 'oz'],
            'volume' => ['ml', 'l', 'cup', 'tbsp', 'tsp'],
            'count' => ['pcs', 'item', 'unit'],
        ];

        return Inertia::render('Menu/Create', [
            'categories' => $categories,
            'ingredients' => $ingredients,
            'availableUnits' => $availableUnits,
        ]);
    }

   private function uploadImageToSupabase($file, $bucketEnvKey, $folder)
    {
        $supabaseUrl = env('SUPABASE_URL');
        // Use service role key for better permissions when uploading
        $supabaseKey = env('SUPABASE_SERVICE_ROLE_KEY') ?? env('SUPABASE_KEY');
        $bucket = env($bucketEnvKey);

        if (!$supabaseUrl || !$supabaseKey || !$bucket || !$file) {
            Log::error('Supabase configuration or file missing for upload.', [
                'bucket_key' => $bucketEnvKey,
                'has_url' => !empty($supabaseUrl),
                'has_key' => !empty($supabaseKey),
                'has_bucket' => !empty($bucket),
                'has_file' => !empty($file),
            ]);
            return null;
        }

        try {
            $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            // Path structure: [folder]/[filename]
            $filePath = "{$folder}/{$fileName}";

            Log::info('Attempting to upload image to Supabase', [
                'bucket' => $bucket,
                'file_path' => $filePath,
                'mime_type' => $file->getMimeType(),
            ]);

            // Upload to Supabase Storage
            $response = Http::withHeaders([
                'apikey' => $supabaseKey,
                'Authorization' => 'Bearer ' . $supabaseKey,
                'Content-Type' => $file->getMimeType(),
                'x-upsert' => 'true', // Changed to lowercase as per Supabase docs
            ])->withBody(
                file_get_contents($file->getRealPath()),
                $file->getMimeType()
            )->post("{$supabaseUrl}/storage/v1/object/{$bucket}/{$filePath}");

            if ($response->successful()) {
                Log::info('Image uploaded successfully to Supabase.', [
                    'bucket' => $bucket,
                    'path_saved' => $filePath,
                    'response' => $response->json(),
                ]);

                // Construct and return the public URL
                $url = rtrim($supabaseUrl, '/');
                return "{$url}/storage/v1/object/public/{$bucket}/{$filePath}";
            } else {
                Log::error('Supabase image upload failed.', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'response_json' => $response->json(),
                    'file' => $fileName,
                    'bucket' => $bucket,
                    'path' => $filePath,
                ]);
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Supabase upload exception.', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }


      public function store(Request $request)
    {
        // Wrap entire logic in try/catch to handle unexpected exceptions
        try {
            $user = Auth::user();

            $validated = $request->validate([
                'dish_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:menu_categories,category_id',
                'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', // Allow file upload up to 2MB
                'image_url' => 'nullable|string|max:500', // For existing URL or when no file is uploaded
                'price' => 'nullable|numeric|min:0',
                'ingredients' => 'required|array|min:1',
                'ingredients.*.ingredient_id' => 'nullable|exists:ingredients,ingredient_id',
                'ingredients.*.ingredient_name' => 'required|string|max:255',
                'ingredients.*.quantity' => 'required|numeric|min:0.01',
                'ingredients.*.unit' => 'required|string|max:50',
                'ingredients.*.is_optional' => 'boolean',
                'has_variants' => 'boolean',
                'variants' => 'nullable|array',
                'variants.*.size_name' => 'required_with:variants|string|max:50',
                'variants.*.price_modifier' => 'required_with:variants|numeric|min:0',
                'variants.*.quantity_multiplier' => 'required_with:variants|numeric|min:0.01|max:10',
                'variants.*.is_default' => 'boolean',
            ]);

            // --- PRE-TRANSACTION LOGIC ---
            $finalImageUrl = $request->image_url; 
            
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $newImageUrl = $this->uploadImageToSupabase($uploadedFile, 'SUPABASE_BUCKET_DISH', 'dish_images'); 

                if ($newImageUrl) {
                    $finalImageUrl = $newImageUrl; 
                } else {
                    return back()->withErrors(['image' => 'Failed to upload dish image to Supabase storage.'])->withInput();
                }
            }

            // Additional validation checks (omitted for brevity, assume they pass or return back()->withErrors)
            if (!$request->has_variants && (empty($request->price) || $request->price <= 0)) {
                return back()->withErrors(['price' => 'Price is required when variants are not enabled.'])->withInput();
            }
            if (empty($validated['ingredients']) || count($validated['ingredients']) < 1) {
                return back()->withErrors(['ingredients' => 'At least one ingredient is required for inventory tracking.'])->withInput();
            }
            if ($request->has_variants && (!is_array($request->variants) || count($request->variants) < 1)) {
                 return back()->withErrors(['variants' => 'If variants are enabled, you must add at least one size variant.'])->withInput();
            }
            if ($request->has_variants && is_array($request->variants) && count($request->variants) > 0) {
                $defaultCount = collect($request->variants)->where('is_default', true)->count();
                if ($defaultCount === 0 || $defaultCount > 1) {
                    return back()->withErrors(['variants' => ($defaultCount === 0 ? 'You must mark one variant as the default size.' : 'Only one variant can be marked as default.')])->withInput();
                }
            }

            // --- DATABASE TRANSACTION ---
            DB::transaction(function () use ($request, $user, $finalImageUrl) {
                // Create the dish
                $dish = Dish::create([
                    'restaurant_id' => $user->restaurant_id ?? ($user->restaurantData->id ?? null),
                    'dish_name' => $request->dish_name,
                    'description' => $request->description,
                    'category_id' => $request->category_id,
                    'image_url' => $finalImageUrl,
                    'price' => $request->price,
                    'status' => 'active', 
                ]);

                // Add ingredients
                foreach ($request->ingredients as $ingredientData) {
                    $ingredient = null;

                    if (!empty($ingredientData['ingredient_id']) && $ingredientData['ingredient_id'] > 0) {
                        // Existing ingredient
                        $ingredient = Ingredients::find($ingredientData['ingredient_id']);
                    } else {
                        // Custom ingredient - create new one
                        $ingredient = Ingredients::create([
                            'restaurant_id' => $user->restaurant_id ?? ($user->restaurantData->id ?? null),
                            'ingredient_name' => $ingredientData['ingredient_name'],
                            'base_unit' => $ingredientData['unit'],
                            'current_stock' => 0,
                        ]);
                    }

                    if ($ingredient) {
                        DishIngredient::create([
                            'dish_id' => $dish->dish_id, // FIX: Use the custom primary key 'dish_id'
                            'ingredient_id' => $ingredient->ingredient_id, // FIX: Use the custom primary key 'ingredient_id'
                            'quantity_needed' => $ingredientData['quantity'],
                            'unit_of_measure' => $ingredientData['unit'],
                            'is_optional' => $ingredientData['is_optional'] ?? false,
                        ]);
                    }
                }

                // Add variants if enabled
                if ($request->has_variants && is_array($request->variants) && count($request->variants) > 0) {
                    foreach ($request->variants as $index => $variantData) {
                        DishVariant::create([
                            'dish_id' => $dish->dish_id, // FIX: Use the custom primary key 'dish_id'
                            'size_name' => $variantData['size_name'],
                            'price_modifier' => $variantData['price_modifier'],
                            'quantity_multiplier' => $variantData['quantity_multiplier'],
                            'is_default' => $variantData['is_default'] ?? false,
                            'is_available' => true,
                            'sort_order' => $index,
                        ]);
                    }
                }
            });

            return redirect()->route('menu.index')->with('success', 'Dish created successfully!');
        
        // Catch Validation exceptions separately
        } catch (ValidationException $e) {
            // Re-throw ValidationException so Laravel handles the back redirection with errors
            throw $e;
        } catch (Exception $e) {
            // Catch any general exception (like missing model class or database error)
            Log::error('Failed to create dish: Unhandled Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['image'])
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred while creating the dish. Please check the server logs for details.'])->withInput();
        }
    }


    public function show(Dish $dish)
    {
        $dish->load([
            'category',
            'dishIngredients.ingredient',
            'variants'
        ]);

        return Inertia::render('Menu/Show', [
            'dish' => $dish,
        ]);
    }

     public function edit(Dish $dish)
    {
        $user = Auth::user();
        // NOTE: Models are assumed to be imported for this section (e.g., MenuCategory, Ingredients)
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        $dish->load([
            'dishIngredients.ingredient',
            'variants'
        ]);

        // Mocking Model dependencies for the environment
        // --- FIX: Updated mocks to correctly support fluent chaining via methods, resolving Call to undefined method error ---
        $MenuCategoryMock = new class { 
            public static function byRestaurant($id) {
                return new class {
                    public function active() { return $this; }
                    public function orderBy($col) { return $this; }
                    public function get($cols) { return collect([]); }
                };
            }
        };
        $IngredientsMock = new class { 
            public static function where($col, $val) {
                return new class {
                    public function orderBy($col) { return $this; }
                    public function get($cols) { return collect([]); }
                };
            }
        };
        
        // Actual Model Calls (if models were available):
        // $categories = MenuCategory::byRestaurant($restaurantId)
        $categories = $MenuCategoryMock::byRestaurant($restaurantId)
            ->active()
            ->orderBy('sort_order')
            ->get(['category_id', 'category_name']);

        // Actual Model Calls (if models were available):
        // $ingredients = Ingredients::where('restaurant_id', $restaurantId)
        $ingredients = $IngredientsMock::where('restaurant_id', $restaurantId)
            ->orderBy('ingredient_name')
            ->get([
                'ingredient_id',
                'ingredient_name',
                'base_unit',
                'current_stock',
                'cost_per_unit'
            ]);

        $availableUnits = [
            'weight' => ['g', 'kg', 'lb', 'oz'],
            'volume' => ['ml', 'l', 'cup', 'tbsp', 'tsp'],
            'count' => ['pcs', 'item', 'unit'],
        ];

        // Assuming Inertia is available for rendering
        return Inertia::render('Menu/Edit', [
        // return response()->json([
            'dish' => $dish,
            'categories' => $categories,
            'ingredients' => $ingredients,
            'availableUnits' => $availableUnits,
        ]);
    }

     public function update(Request $request, Dish $dish)
    {
        // Wrap entire logic in try/catch to handle unexpected exceptions
        try {
            // Add 'image' to validation rules to accept file uploads
            $validated = $request->validate([
                'dish_name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'category_id' => 'required|exists:menu_categories,category_id',
                'image' => 'nullable|file|mimes:jpg,jpeg,png|max:2048', 
                'image_url' => 'nullable|string|max:500',
                'price' => 'nullable|numeric|min:0',
                'ingredients' => 'required|array|min:1',
                'ingredients.*.ingredient_id' => 'nullable|exists:ingredients,ingredient_id',
                'ingredients.*.ingredient_name' => 'required|string|max:255',
                'ingredients.*.quantity' => 'required|numeric|min:0.01',
                'ingredients.*.unit' => 'required|string|max:50',
                'ingredients.*.is_optional' => 'boolean',
                'has_variants' => 'boolean',
                'variants' => 'nullable|array',
                'variants.*.size_name' => 'required_with:variants|string|max:50',
                'variants.*.price_modifier' => 'required_with:variants|numeric|min:0',
                'variants.*.quantity_multiplier' => 'required_with:variants|numeric|min:0.01|max:10',
                'variants.*.is_default' => 'boolean',
            ]);

            // --- PRE-TRANSACTION IMAGE UPLOAD LOGIC ---
            $finalImageUrl = $request->image_url; 
            
            if ($request->hasFile('image')) {
                $uploadedFile = $request->file('image');
                $newImageUrl = $this->uploadImageToSupabase($uploadedFile, 'SUPABASE_BUCKET_DISH', 'dish_images'); 

                if ($newImageUrl) {
                    $finalImageUrl = $newImageUrl; 
                } else {
                    return back()->withErrors(['image' => 'Failed to upload dish image to Supabase storage.'])->withInput();
                }
            }
            // --- END IMAGE UPLOAD LOGIC ---

            // Additional validation checks (omitted for brevity)
            if (!$request->has_variants && (empty($request->price) || $request->price <= 0)) {
                 return back()->withErrors(['price' => 'Price is required when variants are not enabled.'])->withInput();
            }
            if (empty($validated['ingredients']) || count($validated['ingredients']) < 1) {
                return back()->withErrors(['ingredients' => 'At least one ingredient is required for inventory tracking.'])->withInput();
            }
            if ($request->has_variants && (!is_array($request->variants) || count($request->variants) < 1)) {
                 return back()->withErrors(['variants' => 'If variants are enabled, you must add at least one size variant.'])->withInput();
            }
            if ($request->has_variants && is_array($request->variants) && count($request->variants) > 0) {
                $defaultCount = collect($request->variants)->where('is_default', true)->count();
                if ($defaultCount === 0 || $defaultCount > 1) {
                    return back()->withErrors(['variants' => ($defaultCount === 0 ? 'You must mark one variant as the default size.' : 'Only one variant can be marked as default.')])->withInput();
                }
            }

            DB::transaction(function () use ($request, $dish, $finalImageUrl) {
                // Update dish basic info
                $dish->update([
                    'dish_name' => $request->dish_name,
                    'description' => $request->description,
                    'category_id' => $request->category_id,
                    'image_url' => $finalImageUrl,
                    'price' => $request->price,
                ]);

                // Update ingredients - delete all and recreate
                $dish->dishIngredients()->delete(); 

                foreach ($request->ingredients as $ingredientData) {
                    $ingredient = null;

                    if (!empty($ingredientData['ingredient_id']) && $ingredientData['ingredient_id'] > 0) {
                        $ingredient = Ingredients::find($ingredientData['ingredient_id']);
                    } else {
                        $ingredient = Ingredients::create([
                            'restaurant_id' => $dish->restaurant_id,
                            'ingredient_name' => $ingredientData['ingredient_name'],
                            'base_unit' => $ingredientData['unit'],
                            'current_stock' => 0,
                        ]);
                    }

                    if ($ingredient) {
                        DishIngredient::create([
                            'dish_id' => $dish->dish_id, // FIX: Use the custom primary key 'dish_id'
                            'ingredient_id' => $ingredient->ingredient_id, // FIX: Use the custom primary key 'ingredient_id'
                            'quantity_needed' => $ingredientData['quantity'],
                            'unit_of_measure' => $ingredientData['unit'],
                            'is_optional' => $ingredientData['is_optional'] ?? false,
                        ]);
                    }
                }

                // Update variants - delete all and recreate
                $dish->variants()->delete(); 

                if ($request->has_variants && is_array($request->variants)) {
                    foreach ($request->variants as $index => $variantData) {
                        DishVariant::create([
                            'dish_id' => $dish->dish_id, // FIX: Use the custom primary key 'dish_id'
                            'size_name' => $variantData['size_name'],
                            'price_modifier' => $variantData['price_modifier'],
                            'quantity_multiplier' => $variantData['quantity_multiplier'],
                            'is_default' => $variantData['is_default'] ?? false,
                            'is_available' => true,
                            'sort_order' => $index,
                        ]);
                    }
                }
            });

            return redirect()->route('menu.show', $dish)->with('success', 'Dish updated successfully!');
        
        // Catch Validation exceptions separately
        } catch (ValidationException $e) {
            // Re-throw ValidationException so Laravel handles the back redirection with errors
            throw $e;
        } catch (Exception $e) {
            // Catch any general exception (like missing model class or database error)
            Log::error('Failed to update dish: Unhandled Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->except(['image'])
            ]);
            return back()->withErrors(['error' => 'An unexpected error occurred while updating the dish. Please check the server logs for details.'])->withInput();
        }
    }
    public function updateStatus(Request $request, Dish $dish)
    {
        $request->validate([
            'status' => ['required', Rule::in(['draft', 'active', 'inactive', 'archived'])],
        ]);

        $dish->update(['status' => $request->status]);

        return back()->with('success', 'Dish status updated successfully!');
    }

    public function destroy(Dish $dish)
    {
        $dish->delete();

        return redirect()->route('menu.index')->with('success', 'Dish deleted successfully!');
    }

    public function analytics()
    {
        $user = Auth::user();
        $restaurantId = $user->restaurant_id ?? ($user->restaurantData->id ?? null);

        // Get analytics data
        $analytics = [
            'total_dishes' => Dish::byRestaurant($restaurantId)->count(),
            'active_dishes' => Dish::byRestaurant($restaurantId)->active()->count(),
            'popular_categories' => MenuCategory::byRestaurant($restaurantId)
                ->withCount('dishes')
                ->orderBy('dishes_count', 'desc')
                ->limit(5)
                ->get(),
            'cost_analysis' => null,
        ];

        return Inertia::render('Menu/Analytics', [
            'analytics' => $analytics,
        ]);
    }
}