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
            $stored = null;
            if ($request->hasFile("images.$slot")) {
                $stored = $this->storeUploadedImage($request->file("images.$slot"), $request->input("image_data.$slot"));
            } elseif ($request->filled("image_data.$slot")) {
                $stored = $this->storeDataUrlImage((string) $request->input("image_data.$slot"));
            }
            if ($stored) {
                $uploadedSlots[] = $stored;
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

        $placeholderUrl = Product::placeholderImageUrl();
        $slots = [0 => null, 1 => null, 2 => null, 3 => null];
        foreach (array_slice($existingImages, 0, 4) as $idx => $rawImg) {
            $resolved = Product::resolveImageUrl((string) $rawImg);
            if ($resolved && $resolved !== $placeholderUrl) {
                $slots[$idx] = $rawImg;
            }
        }

        $slotsModified = false;

        for ($slot = 0; $slot < 4; $slot++) {
            if ((string) $request->input("remove_images.$slot") === '1') {
                $slots[$slot] = null;
                $slotsModified = true;
            }
            if ($request->hasFile("images.$slot")) {
                $stored = $this->storeUploadedImage($request->file("images.$slot"), $request->input("image_data.$slot"));
                if ($stored) {
                    $slots[$slot] = $stored;
                    $slotsModified = true;
                }
            } elseif ($request->filled("image_data.$slot")) {
                $stored = $this->storeDataUrlImage((string) $request->input("image_data.$slot"));
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
            'image_data' => ['nullable', 'array', 'max:4'],
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

    private function persistToUploadedFiles(string $relativePath, string $mimeType, string $rawBytes): void
    {
        if ($rawBytes === '') {
            return;
        }

        try {
            if (! \Illuminate\Support\Facades\Schema::hasTable('uploaded_files')) {
                \Illuminate\Support\Facades\Schema::create('uploaded_files', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->id();
                    $table->string('path')->unique();
                    $table->string('mime_type', 100)->default('image/jpeg');
                    $table->longText('contents');
                    $table->timestamps();
                });
            }

            DB::table('uploaded_files')->updateOrInsert(
                ['path' => $relativePath],
                [
                    'mime_type' => $mimeType,
                    'contents' => base64_encode($rawBytes),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        } catch (\Throwable $e) {
            // Continue even if DB backup fails
        }
    }

    private function storeDataUrlImage(?string $dataUrl): ?string
    {
        if (! $dataUrl || ! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/s', trim($dataUrl), $matches)) {
            return null;
        }

        $mimeType = strtolower($matches[1]);
        $rawBytes = base64_decode($matches[2], true);
        if ($rawBytes === false || $rawBytes === '') {
            return null;
        }

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ];
        $extension = $extMap[$mimeType] ?? 'jpg';

        $directory = public_path(Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY);
        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filename = Str::uuid()->toString().'.'.$extension;
        $relativePath = Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY.'/'.$filename;

        @file_put_contents($directory.DIRECTORY_SEPARATOR.$filename, $rawBytes);
        $this->persistToUploadedFiles($relativePath, $mimeType, $rawBytes);

        return $relativePath;
    }

    private function storeUploadedImage($file, ?string $fallbackDataUrl = null): ?string
    {
        if (! $file || ! $file->isValid()) {
            return $this->storeDataUrlImage($fallbackDataUrl);
        }

        $directory = public_path(Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY);

        if (! File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $mimeType = $file->getMimeType() ?: 'image/jpeg';
        $rawBytes = @file_get_contents($file->getRealPath());

        $filename = Str::uuid()->toString().'.'.$extension;
        $relativePath = Product::PUBLIC_PRODUCT_IMAGE_DIRECTORY.'/'.$filename;

        $file->move($directory, $filename);

        if ($rawBytes !== false && $rawBytes !== '') {
            $this->persistToUploadedFiles($relativePath, $mimeType, $rawBytes);
        } elseif ($fallbackDataUrl && preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/s', trim($fallbackDataUrl), $m)) {
            $decoded = base64_decode($m[2], true);
            if ($decoded !== false && $decoded !== '') {
                $this->persistToUploadedFiles($relativePath, $m[1], $decoded);
            }
        }

        return $relativePath;
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

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('uploaded_files')) {
                DB::table('uploaded_files')->where('path', $publicPath)->delete();
            }
        } catch (\Throwable $e) {
            // Ignore DB cleanup errors
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
        abort_unless($product, 403);

        $allImages = [];
        if (property_exists($product, 'images') && !empty($product->images)) {
            $decoded = is_array($product->images) ? $product->images : json_decode((string) $product->images, true);
            if (is_array($decoded)) {
                $allImages = array_values(array_filter($decoded));
            }
        }
        if (!empty($product->image)) {
            $allImages[] = $product->image;
        }

        foreach (array_unique($allImages) as $rawPath) {
            $normalized = Product::normalizeImagePath((string) $rawPath);
            if ($normalized) {
                $this->deleteProductImageIfUnused($normalized, (int) $id);
            }
        }

        DB::table('product_variants')->where('product_id', $id)->delete();

        if (\Illuminate\Support\Facades\Schema::hasTable('product_reviews')) {
            DB::table('product_reviews')->where('product_id', $id)->delete();
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('conversations') && \Illuminate\Support\Facades\Schema::hasColumn('conversations', 'product_id')) {
            DB::table('conversations')->where('product_id', $id)->update(['product_id' => null]);
        }

        if (\Illuminate\Support\Facades\Schema::hasTable('order_items')) {
            DB::table('order_items')->where('product_id', $id)->delete();
        }

        DB::table('products')->where('id', $id)->where('seller_id', Auth::id())->delete();

        return back()->with('success', 'Product removed successfully.');
    }
}
