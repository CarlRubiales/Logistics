<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    public const PLACEHOLDER_IMAGE_PATH = 'images/product-placeholder.png';
    public const PUBLIC_PRODUCT_IMAGE_DIRECTORY = 'products';

    protected $fillable = [
        'seller_id',
        'name',
        'category',
        'description',
        'price',
        'discount_percent',
        'sales_count',
        'rating',
        'stock',
        'variant_type',
        'image',
        'images',
        'status',
        'category',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_percent' => 'decimal:1',
        'stock' => 'integer',
        'sales_count' => 'integer',
        'rating' => 'decimal:2',
        'images' => 'array',
    ];

    public static function placeholderImageUrl(): string
    {
        return asset(self::PLACEHOLDER_IMAGE_PATH);
    }

    public static function normalizeImagePath(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $path = trim(str_replace('\\', '/', $value));

        if (Str::startsWith($path, ['http://', 'https://'])) {
            if (! self::isAppUrl($path)) {
                return null;
            }

            $path = parse_url($path, PHP_URL_PATH) ?: '';
        }

        $path = ltrim($path, '/');

        foreach (['storage/app/public/', 'public/storage/', 'public/', 'storage/'] as $prefix) {
            $position = stripos($path, $prefix);

            if ($position !== false) {
                $path = substr($path, $position + strlen($prefix));
                break;
            }
        }

        if (preg_match('/^[A-Za-z]:\//', $path) || Str::startsWith($path, ['//', '../'])) {
            return null;
        }

        return blank($path) ? null : $path;
    }

    public static function publicProductImagePath(?string $value): ?string
    {
        $path = self::normalizeImagePath($value);

        if (! $path) {
            return null;
        }

        $filename = basename($path);

        return blank($filename) ? null : self::PUBLIC_PRODUCT_IMAGE_DIRECTORY.'/'.$filename;
    }

    public static function isPublicProductImagePath(?string $value): bool
    {
        $path = self::normalizeImagePath($value);

        return (bool) $path
            && Str::startsWith($path, self::PUBLIC_PRODUCT_IMAGE_DIRECTORY.'/')
            && basename($path) !== basename(self::PLACEHOLDER_IMAGE_PATH);
    }

    public static function resolveImageUrl(?string $rawImage): string
    {
        if (blank($rawImage)) {
            return self::placeholderImageUrl();
        }

        $value = trim((string) $rawImage);

        if (Str::startsWith($value, 'data:image/')) {
            return $value;
        }

        if (Str::startsWith($value, ['http://', 'https://']) && ! self::isAppUrl($value)) {
            return $value;
        }

        $path = self::normalizeImagePath($value);

        $publicCandidates = array_filter(array_unique([
            ltrim($value, '/'),
            $path,
            self::publicProductImagePath($value),
            $path ? 'storage/'.$path : null,
        ]));

        foreach ($publicCandidates as $publicPath) {
            if (file_exists(public_path($publicPath))) {
                return asset($publicPath);
            }
        }

        if ($path && Storage::disk('public')->exists($path)) {
            $url = Storage::disk('public')->url($path);

            return Str::startsWith($url, ['http://', 'https://'])
                ? $url
                : asset($url);
        }

        // Restore from database backup if container disk was reset on deployment
        $canonicalPath = self::publicProductImagePath($value) ?: $path;
        if ($canonicalPath) {
            try {
                if (\Illuminate\Support\Facades\Schema::hasTable('uploaded_files')) {
                    $record = \Illuminate\Support\Facades\DB::table('uploaded_files')
                        ->whereIn('path', array_filter([$canonicalPath, $path]))
                        ->first();

                    if ($record && ! empty($record->contents)) {
                        $binary = base64_decode($record->contents, true);
                        if ($binary !== false) {
                            $targetFullPath = public_path($record->path);
                            $targetDir = dirname($targetFullPath);
                            if (! is_dir($targetDir)) {
                                @mkdir($targetDir, 0755, true);
                            }
                            if (@file_put_contents($targetFullPath, $binary) !== false) {
                                return asset($record->path);
                            }
                        }

                        return 'data:'.($record->mime_type ?: 'image/jpeg').';base64,'.$record->contents;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore DB lookup errors and fall back to placeholder
            }
        }

        return self::placeholderImageUrl();
    }

    public function getImageUrlAttribute(): string
    {
        return self::resolveImageUrl($this->image);
    }

    public function getImageUrlsAttribute(): array
    {
        $rawList = is_array($this->images) ? array_values(array_filter($this->images)) : [];

        if (empty($rawList) && filled($this->image)) {
            $rawList = [$this->image];
        }

        if (empty($rawList)) {
            return [self::placeholderImageUrl()];
        }

        return array_values(array_map(fn ($img) => self::resolveImageUrl((string) $img), array_slice($rawList, 0, 4)));
    }

    public function imageDiskPath(): ?string
    {
        $path = self::normalizeImagePath($this->image);

        return $path ? Storage::disk('public')->path($path) : null;
    }

    public function imageExists(): bool
    {
        $path = self::normalizeImagePath($this->image);
        $publicPath = self::publicProductImagePath($this->image);

        return (bool) $path
            && (
                Storage::disk('public')->exists($path)
                || file_exists(public_path('storage/'.$path))
                || ($publicPath && file_exists(public_path($publicPath)))
            );
    }

    private static function isAppUrl(string $url): bool
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);
        $requestHost = request()?->getHost();

        return in_array($host, array_filter([
            'localhost',
            '127.0.0.1',
            '::1',
            $appHost,
            $requestHost,
        ]), true);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function getAverageRatingAttribute(): float
    {
        $rating = $this->reviews_avg_rating ?? $this->reviews()->avg('rating');

        return round((float) $rating, 1);
    }

    public function getReviewCountAttribute(): int
    {
        return (int) ($this->reviews_count ?? $this->reviews()->count());
    }
}
