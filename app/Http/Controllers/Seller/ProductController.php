<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\CategoryCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->query('view', 'active');
        $query = Product::query()->where('seller_id', Auth::id());

        if ($view === 'archived') {
            $query->where('status', 'archived');
        } else {
            $query->where('status', '!=', 'archived');
        }

        $products = $query->orderByDesc('created_at')->get();
        $variants = DB::table('product_variants')
            ->whereIn('product_id', $products->pluck('id'))
            ->orderBy('id')
            ->get()
            ->groupBy('product_id');

        return view('seller.products', [
            'products' => $products,
            'view' => $view,
            'variants' => $variants,
            'categoryTree' => CategoryCatalog::tree(),
            'categoryLeaves' => CategoryCatalog::leaves(),
        ]);
    }

    private function ensureImagesColumnExists(): void
    {
        try {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('products', 'images')) {
                \Illuminate\Support\Facades\Schema::table('products', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->json('images')->nullable()->after('image');
                });
            }
        } catch (\Throwable $e) {
            // Ignore if schema cannot be altered dynamically.
        }
    }

    public function store(Request $request)
    {
        $this->ensureImagesColumnExists();
        $validated = $this->validateProduct($request);
        $uploadedSlots = [];

        for ($slot = 0; $slot < 4; $slot++) {
            if ($request->hasFile("images.$slot")) {
                $stored = $this->storeUploadedImage($request->file("images.$slot"));
                if ($stored) {
                    $uploadedSlots[] = $stored;
                }
            }
        }

        if (empty($uploadedSlots) && $request->hasFile('image')) {
            $single = $this->storeUploadedImage($request->file('image'));
            if ($single) {
                $uploadedSlots[] = $single;
            }
        }

        $imagePath = $uploadedSlots[0] ?? null;

        $variantRows = collect($request->input('variants', []))
            ->filter(fn ($v) => !empty($v['name']));
        $hasVariants = $variantRows->isNotEmpty();
        $totalStock = $hasVariants
            ? $variantRows->sum(fn ($v) => (int) ($v['stock'] ?? 0))
            : (int) ($request->stock ?? 0);

        $insertData = [
            'seller_id' => Auth::id(),
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'discount_percent' => $validated['discount_percent'] ?? null,
            'stock' => $totalStock,
            'variant_type' => $hasVariants ? ($validated['variant_type'] ?? null) : null,
            'image' => $imagePath,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'images')) {
            $insertData['images'] = !empty($uploadedSlots) ? json_encode(array_values($uploadedSlots)) : null;
        }

        $productId = DB::table('products')->insertGetId($insertData);

        $this->insertVariants($productId, $variantRows);
        return back()->with('success', 'Product added successfully!');
    }

    public function update(Request $request, $id)
    {
        $this->ensureImagesColumnExists();
        $product = DB::table('products')
            ->where('id', $id)
            ->where('seller_id', Auth::id())
            ->first();
        abort_unless($product, 403);

        $validated = $this->validateProduct($request);
        $variantRows = collect($request->input('variants', []))
            ->filter(fn ($v) => !empty($v['name']));
        $hasVariants = $variantRows->isNotEmpty();

        $data = [
            'name' => $validated['name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'price' => $validated['price'],
            'discount_percent' => $validated['discount_percent'] ?? null,
            'stock' => $hasVariants
                ? $variantRows->sum(fn ($v) => (int) ($v['stock'] ?? 0))
                : (int) ($request->stock ?? 0),
            'variant_type' => $hasVariants ? ($validated['variant_type'] ?? null) : null,
            'updated_at' => now(),
        ];

        $existingImages = [];
        if (property_exists($product, 'images') && !empty($product->images)) {
            $decoded = json_decode((string) $product->images, true);
            if (is_array($decoded)) {
                $existingImages = array_values(array_filter($decoded));
            }
        }
        if (empty($existingImages) && !empty($product->image)) {
            $existingImages = [$product->image];
        }

        $slots = [
            0 => $existingImages[0] ?? null,
            1 => $existingImages[1] ?? null,
            2 => $existingImages[2] ?? null,
            3 => $existingImages[3] ?? null,
        ];

        $slotsModified = false;

        for ($slot = 0; $slot < 4; $slot++) {
            if ((string) $request->input("remove_images.$slot") === '1') {
                $slots[$slot] = null;
                $slotsModified = true;
            }
            if ($request->hasFile("images.$slot")) {
                $stored = $this->storeUploadedImage($request->file("images.$slot"));
                if ($stored) {
                    $slots[$slot] = $stored;
                    $slotsModified = true;
                }
            }
        }

        if ($request->hasFile('image')) {
            $stored = $this->storeUploadedImage($request->file('image'));
            if ($stored) {
                $slots[0] = $stored;
                $slotsModified = true;
            }
        }

        if ($slotsModified) {
            $finalImages = array_values(array_filter($slots));
            $data['image'] = $finalImages[0] ?? null;

            if (\Illuminate\Support\Facades\Schema::hasColumn('products', 'images')) {
                $data['images'] = !empty($finalImages) ? json_encode($finalImages) : null;
            }
        }

        DB::table('products')->where('id', $id)->update($data);
        DB::table('product_variants')->where('product_id', $id)->delete();
        $this->insertVariants($id, $variantRows);

        return back()->with('success', 'Product updated.');
    }

    private function validateProduct(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', Rule::in(CategoryCatalog::slugs())],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:90'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_images' => ['nullable', 'array'],
            'variant_type' => ['nullable', 'string', 'max:100'],
            'variants' => ['nullable', 'array'],
            'variants.*.name' => ['nullable', 'string', 'max:255'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    private function insertVariants(int $productId, $variantRows): void
    {
        foreach ($variantRows as $variant) {
            DB::table('product_variants')->insert([
                'product_id' => $productId,
                'name' => $variant['name'],
                'stock' => (int) ($variant['stock'] ?? 0),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function storeUploadedImage($file): ?string
    {
        if (! $file) {
            return null;
        }

        $directory = public_path(Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $filename = Str::uuid()->toString().'.'.$extension;

        $file->move($directory, $filename);

        return Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY.'/'.$filename;
    }

    private function storeProductImage(Request $request): ?string
    {
        return $this->storeUploadedImage($request->file('image'));
    }

    private function deleteProductImageIfUnused(?string $imagePath, ?int $currentProductId = null): void
    {
        if (! Product::isPublicProductImagePath($imagePath)) {
            return;
        }

        $publicPath = Product::normalizeImagePath($imagePath);

        if (! $publicPath) {
            return;
        }

        $isShared = Product::query()
            ->select('id', 'image')
            ->when($currentProductId, fn ($query) => $query->where('id', '!=', $currentProductId))
            ->get()
            ->contains(fn (Product $product) => Product::normalizeImagePath($product->image) === $publicPath);

        if ($isShared) {
            return;
        }

        $fullPath = public_path($publicPath);
        $productsDirectory = realpath(public_path(Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY));
        $resolvedFile = file_exists($fullPath) ? realpath($fullPath) : false;

        if ($productsDirectory && $resolvedFile && Str::startsWith($resolvedFile, $productsDirectory)) {
            File::delete($resolvedFile);
        }
    }

    public function updateStock(Request $request, $id)
    {
        $request->validate(['stock' => 'required|integer|min:0']);
        $product = DB::table('products')->where('id', $id)->where('seller_id', Auth::id())->first();
        abort_unless($product, 403);
        DB::table('products')->where('id', $id)->update(['stock' => $request->stock, 'updated_at' => now()]);
        return back()->with('success', 'Stock updated.');
    }

    public function archive($id)
    {
        DB::table('products')->where('id', $id)->where('seller_id', Auth::id())->update(['status' => 'archived', 'updated_at' => now()]);
        return back()->with('success', 'Product archived.');
    }

    public function restore($id)
    {
        DB::table('products')->where('id', $id)->where('seller_id', Auth::id())->update(['status' => 'active', 'updated_at' => now()]);
        return back()->with('success', 'Product restored.');
    }

    public function destroy($id)
    {
        $product = DB::table('products')->where('id', $id)->where('seller_id', Auth::id())->first();
        $imagePath = Product::normalizeImagePath($product?->image);

        if ($imagePath) {
            $this->deleteProductImageIfUnused($imagePath, (int) $id);
        }

        DB::table('products')->where('id', $id)->where('seller_id', Auth::id())->delete();
        return back()->with('success', 'Product permanently deleted.');
    }
}
