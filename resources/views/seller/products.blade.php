@extends('layouts.seller')

@section('title', 'My Products')

@section('content')
    @if (session('success'))
        <div class="alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div style="background:#fdece6;color:#b8451f;padding:12px 16px;border-radius:12px;margin-bottom:16px;font-size:.85rem;">
            <ul style="margin:0 0 0 18px;padding:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div style="display:flex;gap:8px;margin-bottom:16px;">
        <a href="{{ route('seller.products.index') }}" class="view-toggle-btn {{ $view === 'active' ? 'active' : '' }}">Active</a>
        <a href="{{ route('seller.products.index', ['view' => 'archived']) }}" class="view-toggle-btn {{ $view === 'archived' ? 'active' : '' }}">Archived</a>
    </div>

    <div class="products-grid">
        @if ($view === 'active')
            {{-- Add Product tile --}}
            <button type="button" class="add-product-tile" onclick="openAddModal()" style="cursor:pointer; font-family:inherit;">
                <div class="add-icon">+</div>
                <div class="add-label">Add Product</div>
            </button>
        @endif

        @forelse ($products as $product)
            @php
                $hasDiscount = $product->discount_percent && $product->discount_percent > 0;
                $discountedPrice = $hasDiscount ? $product->price * (1 - $product->discount_percent / 100) : $product->price;
                $productVariants = $variants->get($product->id, collect());
                $decodedImages = is_array($product->images)
                    ? $product->images
                    : (is_string($product->images) ? json_decode($product->images, true) : []);
                $rawImages = is_array($decodedImages) ? array_values(array_filter($decodedImages)) : [];
                if (empty($rawImages) && filled($product->image)) {
                    $rawImages = [$product->image];
                }
                $placeholderUrl = \App\Models\Product::placeholderImageUrl();
                $existingPhotoUrls = [];
                foreach (array_slice($rawImages, 0, 4) as $slotIdx => $img) {
                    $resolved = \App\Models\Product::resolveImageUrl((string) $img);
                    $existingPhotoUrls[$slotIdx] = ($resolved && $resolved !== $placeholderUrl) ? $resolved : '';
                }
            @endphp
            <div class="product-tile">
                <div class="product-image" style="position:relative;">
                    @if ($hasDiscount)
                        <span style="position:absolute;top:8px;left:8px;background:#B85C3B;color:#fff;font-size:11px;font-weight:700;padding:3px 8px;border-radius:20px;">{{ rtrim(rtrim(number_format($product->discount_percent, 1), '0'), '.') }}% OFF</span>
                    @endif
                    @if (count($existingPhotoUrls) > 1)
                        <span style="position:absolute;bottom:8px;right:8px;background:rgba(43,24,38,.72);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:999px;">{{ count($existingPhotoUrls) }}/4 photos</span>
                    @endif
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" loading="lazy" onerror="this.onerror=null;this.src='{{ asset('images/product-placeholder.png') }}';">
                </div>
                <div class="product-body">
                    <div class="product-name">{{ $product->name }}</div>
                    @if ($product->category)
                        <div style="font-size:.75rem;color:#8B7A80;margin-bottom:4px;">{{ $product->category }}</div>
                    @endif
                    <div class="product-price">
                        @if ($hasDiscount)
                            <span style="text-decoration:line-through;color:#A08D96;font-size:.85em;">₱{{ number_format($product->price, 2) }}</span>
                            ₱{{ number_format($discountedPrice, 2) }}
                        @else
                            ₱{{ number_format($product->price, 2) }}
                        @endif
                    </div>
                    <div class="product-stock">
                        Stock:
                        <span class="count {{ $product->stock <= 5 ? 'low' : '' }}">{{ $product->stock }}</span>
                        @if ($productVariants->isNotEmpty())
                            <span style="color:#8B7A80;font-weight:400;">&middot; {{ $productVariants->count() }} {{ Str::plural($product->variant_type ?: 'variant', $productVariants->count()) }}</span>
                        @endif
                    </div>

                    @if ($view === 'active')
                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px;">
                            <button type="button" class="edit-stock-btn" style="width:100%;"
                                onclick="openEditModal({{ $product->id }}, {{ json_encode($product->name) }}, {{ json_encode($product->category) }}, {{ json_encode($product->description) }}, {{ $product->price }}, {{ $product->discount_percent ?? 'null' }}, {{ $product->stock }}, {{ json_encode($product->variant_type) }}, {{ json_encode($productVariants->map(fn($v) => ['name' => $v->name, 'stock' => $v->stock])->values()) }}, {{ json_encode($existingPhotoUrls) }})">
                                Edit Product
                            </button>
                            <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this product permanently?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="remove-product-btn" style="width:100%;">Remove Product</button>
                            </form>
                        </div>
                    @else
                        <div style="display:flex;flex-direction:column;gap:6px;margin-top:8px;">
                            <form action="{{ route('seller.products.restore', $product->id) }}" method="POST" style="margin:0;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="edit-stock-btn" style="width:100%;">Restore Product</button>
                            </form>
                            <form action="{{ route('seller.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to remove this product permanently?');" style="margin:0;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="remove-product-btn" style="width:100%;">Remove Product</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            @if ($view === 'archived')
                <p style="color:#999;grid-column:1/-1;padding:20px 0;">No archived products.</p>
            @endif
        @endforelse
    </div>

    {{-- Add Product Modal --}}
    <div id="addProductModal" style="display:none; position:fixed; inset:0; background:rgba(43,28,34,.5); align-items:center; justify-content:center; z-index:50; padding:16px;">
        <div style="background:#fff; border-radius:18px; padding:28px; width:460px; max-width:95%; max-height:90vh; overflow-y:auto;">
            <h2 style="font-size:18px; font-weight:800; margin-bottom:18px;">Add Product</h2>
            <form action="{{ route('seller.products.store') }}" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px;">
                @csrf
                <input type="text" name="name" placeholder="Product name" required class="stock-input" style="width:100%;">
                <select name="category" required class="stock-input" style="width:100%;">
                    <option value="">Select category</option>
                    @foreach (($categoryLeaves ?? []) as $cat)
                        <option value="{{ $cat['slug'] }}">{{ $cat['label'] }}</option>
                    @endforeach
                </select>
                <textarea name="description" placeholder="Description (optional)" class="stock-input" style="width:100%; height:70px;"></textarea>
                <input type="number" name="price" placeholder="Price" step="0.01" min="0" required class="stock-input" style="width:100%;">
                <input type="number" name="discount_percent" placeholder="Discount % (optional)" step="0.1" min="0" max="90" class="stock-input" style="width:100%;">

                <div style="font-size:12px;color:#8B7A80;">Base stock is set per variant below, if you add any.</div>
                <input type="number" name="stock" id="add_base_stock" placeholder="Stock (used only if no variants added)" min="0" class="stock-input" style="width:100%;">

                <div style="border-top:1px solid var(--border);padding-top:12px;">
                    <div style="font-size:13px;font-weight:700;color:var(--maroon-dark);margin-bottom:2px;">Variants (optional)</div>
                    <div style="font-size:11.5px;color:#8B7A80;margin-bottom:10px;">e.g. different colors or sizes, each with its own stock.</div>
                    <input type="text" name="variant_type" id="add_variant_type" placeholder="Variant type — e.g. Color, Size" autocomplete="off" class="stock-input" style="width:100%;margin-bottom:10px;">
                    <div style="display:flex;gap:8px;margin-bottom:6px;">
                        <div style="flex:2;font-size:11.5px;font-weight:700;color:#8B7A80;">Variant name</div>
                        <div style="flex:1;font-size:11.5px;font-weight:700;color:#8B7A80;">Stock</div>
                    </div>
                    <div id="add_variant_rows"></div>
                    <button type="button" onclick="addVariantRow('add')" style="background:none;border:none;color:var(--maroon);font-size:12.5px;font-weight:700;cursor:pointer;padding:0;font-family:inherit;">+ Add variant</button>
                </div>

                <div style="border-top:1px solid var(--border);padding-top:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:13px;font-weight:700;color:var(--maroon-dark);">Product Photos (4 Slots)</span>
                        <span style="font-size:11px;color:#8B7A80;">Max 5MB each</span>
                    </div>
                    <div style="font-size:11.5px;color:#8B7A80;margin-bottom:10px;">Click a slot to add a photo. Slot 1 is used as the main cover photo.</div>
                    <div class="photo-slots-grid" data-photo-slots="add">
                        @for ($slot = 0; $slot < 4; $slot++)
                            <div class="photo-slot" data-slot-index="{{ $slot }}">
                                <input type="file" name="images[{{ $slot }}]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="photo-slot-input" onchange="handlePhotoSlotChange(this)">
                                <input type="hidden" name="image_data[{{ $slot }}]" value="" class="photo-slot-data">
                                <div class="photo-slot-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="photo-slot-icon"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="m21 15-5-5L5 19"/></svg>
                                    <span class="photo-slot-label">{{ $slot === 0 ? 'Cover Photo' : 'Slot ' . ($slot + 1) }}</span>
                                    <span class="photo-slot-plus">+ Add Photo</span>
                                </div>
                                <img src="" alt="Preview {{ $slot + 1 }}" class="photo-slot-preview" style="display:none;" onerror="handleSlotPreviewError(this)">
                                <button type="button" class="photo-slot-remove" style="display:none;" onclick="clearPhotoSlot(this, event)" aria-label="Remove photo">&times;</button>
                            </div>
                        @endfor
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" onclick="document.getElementById('addProductModal').style.display='none'" style="flex:1; padding:10px; border-radius:8px; border:1px solid var(--border); background:transparent; font-family:inherit; cursor:pointer;">Cancel</button>
                    <button type="submit" class="edit-stock-btn" style="flex:1; padding:10px;">Save Product</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit Product Modal --}}
    <div id="editProductModal" style="display:none; position:fixed; inset:0; background:rgba(43,28,34,.5); align-items:center; justify-content:center; z-index:50; padding:16px;">
        <div style="background:#fff; border-radius:18px; padding:28px; width:460px; max-width:95%; max-height:90vh; overflow-y:auto;">
            <h2 style="font-size:18px; font-weight:800; margin-bottom:18px;">Edit Product</h2>
            <form id="editProductForm" method="POST" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:12px;">
                @csrf
                @method('PATCH')
                <input type="text" name="name" id="edit_name" placeholder="Product name" required class="stock-input" style="width:100%;">
                <select name="category" id="edit_category" required class="stock-input" style="width:100%;">
                    <option value="">Select category</option>
                    @foreach (($categoryLeaves ?? []) as $cat)
                        <option value="{{ $cat['slug'] }}">{{ $cat['label'] }}</option>
                    @endforeach
                </select>
                <textarea name="description" id="edit_description" placeholder="Description (optional)" class="stock-input" style="width:100%; height:70px;"></textarea>
                <input type="number" name="price" id="edit_price" placeholder="Price" step="0.01" min="0" required class="stock-input" style="width:100%;">
                <input type="number" name="discount_percent" id="edit_discount" placeholder="Discount % (optional)" step="0.1" min="0" max="90" class="stock-input" style="width:100%;">

                <div style="font-size:12px;color:#8B7A80;">Base stock is set per variant below, if you add any.</div>
                <input type="number" name="stock" id="edit_stock" placeholder="Stock (used only if no variants added)" min="0" class="stock-input" style="width:100%;">

                <div style="border-top:1px solid var(--border);padding-top:12px;">
                    <div style="font-size:13px;font-weight:700;color:var(--maroon-dark);margin-bottom:2px;">Variants (optional)</div>
                    <div style="font-size:11.5px;color:#8B7A80;margin-bottom:10px;">e.g. different colors or sizes, each with its own stock.</div>
                    <input type="text" name="variant_type" id="edit_variant_type" placeholder="Variant type — e.g. Color, Size" autocomplete="off" class="stock-input" style="width:100%;margin-bottom:10px;">
                    <div style="display:flex;gap:8px;margin-bottom:6px;">
                        <div style="flex:2;font-size:11.5px;font-weight:700;color:#8B7A80;">Variant name</div>
                        <div style="flex:1;font-size:11.5px;font-weight:700;color:#8B7A80;">Stock</div>
                    </div>
                    <div id="edit_variant_rows"></div>
                    <button type="button" onclick="addVariantRow('edit')" style="background:none;border:none;color:var(--maroon);font-size:12.5px;font-weight:700;cursor:pointer;padding:0;font-family:inherit;">+ Add variant</button>
                </div>

                <div style="border-top:1px solid var(--border);padding-top:12px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
                        <span style="font-size:13px;font-weight:700;color:var(--maroon-dark);">Product Photos (4 Slots)</span>
                        <span style="font-size:11px;color:#8B7A80;">Max 5MB each</span>
                    </div>
                    <div style="font-size:11.5px;color:#8B7A80;margin-bottom:10px;">Click any slot to upload or replace a photo, or click &times; to remove a slot.</div>
                    <div class="photo-slots-grid" data-photo-slots="edit">
                        @for ($slot = 0; $slot < 4; $slot++)
                            <div class="photo-slot" data-slot-index="{{ $slot }}">
                                <input type="file" name="images[{{ $slot }}]" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" class="photo-slot-input" onchange="handlePhotoSlotChange(this)">
                                <input type="hidden" name="image_data[{{ $slot }}]" value="" class="photo-slot-data">
                                <input type="hidden" name="remove_images[{{ $slot }}]" value="0" class="photo-slot-remove-flag">
                                <div class="photo-slot-placeholder">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="photo-slot-icon"><rect x="3" y="5" width="18" height="14" rx="2"/><circle cx="8.5" cy="10.5" r="1.5"/><path d="m21 15-5-5L5 19"/></svg>
                                    <span class="photo-slot-label">{{ $slot === 0 ? 'Cover Photo' : 'Slot ' . ($slot + 1) }}</span>
                                    <span class="photo-slot-plus">+ Add Photo</span>
                                </div>
                                <img src="" alt="Preview {{ $slot + 1 }}" class="photo-slot-preview" style="display:none;" onerror="handleSlotPreviewError(this)">
                                <button type="button" class="photo-slot-remove" style="display:none;" onclick="clearPhotoSlot(this, event)" aria-label="Remove photo">&times;</button>
                            </div>
                        @endfor
                    </div>
                </div>

                <div style="display:flex; gap:10px; margin-top:8px;">
                    <button type="button" onclick="closeEditModal()" style="flex:1; padding:10px; border-radius:8px; border:1px solid var(--border); background:transparent; font-family:inherit; cursor:pointer;">Cancel</button>
                    <button type="submit" class="edit-stock-btn" style="flex:1; padding:10px;">Save Changes</button>
                </div>
            </form>

            <div style="display:flex; gap:10px; margin-top:10px;">
                <button type="button" id="archiveBtnTrigger" style="flex:1;padding:10px;border-radius:8px;border:1px solid #B85C3B;background:transparent;color:#B85C3B;font-family:inherit;font-weight:600;cursor:pointer;">
                    Archive Product
                </button>
                <button type="button" id="removeBtnTrigger" style="flex:1;padding:10px;border-radius:8px;border:1px solid #dc2626;background:#fef2f2;color:#dc2626;font-family:inherit;font-weight:600;cursor:pointer;">
                    Remove Product
                </button>
            </div>
            <form id="archiveProductForm" method="POST" style="display:none;">
                @csrf
                @method('PATCH')
            </form>
            <form id="removeProductForm" method="POST" style="display:none;">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>

    <style>
        .view-toggle-btn {
            padding: 8px 16px;
            border-radius: 20px;
            border: 1px solid var(--border, #F0E2DA);
            background: #fff;
            color: var(--text-muted, #8B7A80);
            font-size: .82rem;
            font-weight: 500;
            text-decoration: none;
        }
        .view-toggle-btn.active {
            background: var(--maroon, #5B1A35);
            color: #fff;
            border-color: var(--maroon, #5B1A35);
        }
        .remove-product-btn {
            padding: 7px 12px;
            border-radius: 999px;
            border: 1px solid #f3c4c4;
            background: #fff5f5;
            color: #c53030;
            font-family: inherit;
            font-size: .76rem;
            font-weight: 600;
            cursor: pointer;
            transition: background .18s ease, border-color .18s ease;
        }
        .remove-product-btn:hover {
            background: #fee2e2;
            border-color: #dc2626;
        }
        .photo-slots-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
        }
        .photo-slot {
            position: relative;
            aspect-ratio: 1 / 1;
            min-height: 92px;
            border: 1.5px dashed #D6C3BC;
            border-radius: 12px;
            background: #FBF6F3;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            cursor: pointer;
            transition: border-color .2s ease, background .2s ease, transform .15s ease;
        }
        .photo-slot:hover {
            border-color: var(--maroon, #5B1A35);
            background: #F7EDE8;
            transform: translateY(-1px);
        }
        .photo-slot.has-image {
            border-style: solid;
            border-color: #E2D4CE;
            background: #fff;
        }
        .photo-slot-input {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .photo-slot-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            padding: 6px;
            text-align: center;
            color: #8B7A80;
            pointer-events: none;
        }
        .photo-slot-icon {
            width: 22px;
            height: 22px;
            color: #B96562;
        }
        .photo-slot-label {
            font-size: 10px;
            font-weight: 700;
            color: #5B1A35;
            line-height: 1.1;
        }
        .photo-slot-plus {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 999px;
            background: #F2E2DA;
            font-size: 9.5px;
            font-weight: 700;
            color: #5B1A35;
        }
        .photo-slot-preview {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            pointer-events: none;
        }
        .photo-slot-remove {
            position: absolute;
            top: 5px;
            right: 5px;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            border: 0;
            background: rgba(43, 24, 38, .82);
            color: #fff;
            font-size: 14px;
            line-height: 1;
            display: grid;
            place-items: center;
            cursor: pointer;
            z-index: 4;
        }
        .photo-slot-remove:hover {
            background: #dc2626;
        }
    </style>

    <script>
        let variantIndex = { add: 0, edit: 0 };

        function addVariantRow(prefix, name = '', stock = '') {
            const idx = variantIndex[prefix]++;
            const container = document.getElementById(prefix + '_variant_rows');
            const row = document.createElement('div');
            row.style.cssText = 'display:flex;gap:8px;margin-bottom:8px;align-items:center;';
            row.innerHTML = `
                <input type="text" name="variants[${idx}][name]" placeholder="Variant name" value="${name.replace(/"/g, '&quot;')}" autocomplete="off" class="stock-input" style="flex:2;">
                <input type="number" name="variants[${idx}][stock]" placeholder="Stock" min="0" value="${stock}" autocomplete="off" class="stock-input" style="flex:1;">
                <button type="button" onclick="this.parentElement.remove()" style="background:none;border:none;color:#B85C3B;font-size:16px;cursor:pointer;padding:4px;">&times;</button>
            `;
            container.appendChild(row);
        }

        function clearVariantRows(prefix) {
            document.getElementById(prefix + '_variant_rows').innerHTML = '';
            variantIndex[prefix] = 0;
        }

        function compressImageToDataUrl(file, callback) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const rawDataUrl = e.target.result;
                const img = new Image();
                img.onload = function () {
                    try {
                        const maxDim = 1000;
                        let w = img.width;
                        let h = img.height;
                        if (w > maxDim || h > maxDim) {
                            if (w > h) {
                                h = Math.round((h * maxDim) / w);
                                w = maxDim;
                            } else {
                                w = Math.round((w * maxDim) / h);
                                h = maxDim;
                            }
                        }
                        const canvas = document.createElement('canvas');
                        canvas.width = w;
                        canvas.height = h;
                        const ctx = canvas.getContext('2d');
                        ctx.drawImage(img, 0, 0, w, h);
                        callback(canvas.toDataURL('image/jpeg', 0.82));
                    } catch (err) {
                        callback(rawDataUrl);
                    }
                };
                img.onerror = function () {
                    callback(rawDataUrl);
                };
                img.src = rawDataUrl;
            };
            reader.readAsDataURL(file);
        }

        function handlePhotoSlotChange(input) {
            const slotEl = input.closest('.photo-slot');
            if (!slotEl) return;
            const file = input.files && input.files[0];
            if (!file) return;

            if (file.size > 15 * 1024 * 1024) {
                alert('Photo size must not exceed 15MB.');
                input.value = '';
                return;
            }

            compressImageToDataUrl(file, function (dataUrl) {
                setPhotoSlotPreview(slotEl, dataUrl);
                const dataInput = slotEl.querySelector('.photo-slot-data');
                if (dataInput) dataInput.value = dataUrl;
                const removeFlag = slotEl.querySelector('.photo-slot-remove-flag');
                if (removeFlag) removeFlag.value = '0';
                // Clear the raw file input so we don't send multi-megabyte duplicates that exceed post_max_size
                input.value = '';
            });
        }

        function handleSlotPreviewError(imgEl) {
            const slotEl = imgEl.closest('.photo-slot');
            if (slotEl) {
                setPhotoSlotPreview(slotEl, '');
            }
        }

        function setPhotoSlotPreview(slotEl, src) {
            const placeholder = slotEl.querySelector('.photo-slot-placeholder');
            const preview = slotEl.querySelector('.photo-slot-preview');
            const removeBtn = slotEl.querySelector('.photo-slot-remove');

            if (src && !src.includes('product-placeholder.png')) {
                preview.src = src;
                preview.style.display = 'block';
                placeholder.style.display = 'none';
                removeBtn.style.display = 'grid';
                slotEl.classList.add('has-image');
            } else {
                preview.removeAttribute('src');
                preview.style.display = 'none';
                placeholder.style.display = 'flex';
                removeBtn.style.display = 'none';
                slotEl.classList.remove('has-image');
            }
        }

        function clearPhotoSlot(btn, event) {
            event.preventDefault();
            event.stopPropagation();
            const slotEl = btn.closest('.photo-slot');
            if (!slotEl) return;
            const input = slotEl.querySelector('.photo-slot-input');
            if (input) input.value = '';
            const dataInput = slotEl.querySelector('.photo-slot-data');
            if (dataInput) dataInput.value = '';
            const removeFlag = slotEl.querySelector('.photo-slot-remove-flag');
            if (removeFlag) removeFlag.value = '1';
            setPhotoSlotPreview(slotEl, '');
        }

        function resetPhotoSlots(groupName, existingUrls = []) {
            const container = document.querySelector(`[data-photo-slots="${groupName}"]`);
            if (!container) return;
            container.querySelectorAll('.photo-slot').forEach((slotEl, index) => {
                const input = slotEl.querySelector('.photo-slot-input');
                if (input) input.value = '';
                const dataInput = slotEl.querySelector('.photo-slot-data');
                if (dataInput) dataInput.value = '';
                const removeFlag = slotEl.querySelector('.photo-slot-remove-flag');
                if (removeFlag) removeFlag.value = '0';
                const url = existingUrls[index] || '';
                setPhotoSlotPreview(slotEl, url);
            });
        }

        function openAddModal() {
            clearVariantRows('add');
            resetPhotoSlots('add', []);
            document.getElementById('addProductModal').style.display = 'flex';
        }

        function openEditModal(id, name, category, description, price, discount, stock, variantType, variantsJson, photoUrls = []) {
            document.getElementById('editProductForm').action = `/seller/products/${id}`;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_category').value = category || '';
            document.getElementById('edit_description').value = description || '';
            document.getElementById('edit_price').value = price;
            document.getElementById('edit_discount').value = discount ?? '';
            document.getElementById('edit_stock').value = stock;
            document.getElementById('edit_variant_type').value = variantType || '';
            document.getElementById('archiveProductForm').action = `/seller/products/${id}/archive`;
            document.getElementById('removeProductForm').action = `/seller/products/${id}`;

            clearVariantRows('edit');
            (variantsJson || []).forEach(v => addVariantRow('edit', v.name, v.stock));
            resetPhotoSlots('edit', photoUrls || []);

            // Eagerly refresh CSRF token so it's always fresh when the form is submitted
            fetch('/csrf-token', { cache: 'no-store', credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (data && data.token) {
                        document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
                        document.querySelectorAll('input[name="_token"]').forEach(el => el.value = data.token);
                        console.log('[CSRF] Token refreshed on modal open:', data.token.substring(0, 10) + '...');
                    }
                })
                .catch(() => {});

            document.getElementById('editProductModal').style.display = 'flex';
        }

        function closeEditModal() {
            document.getElementById('editProductModal').style.display = 'none';
        }

        document.getElementById('archiveBtnTrigger').addEventListener('click', function () {
            if (confirm('Archive this product? It will be hidden from your active listings but can be restored later.')) {
                document.getElementById('archiveProductForm').submit();
            }
        });

        document.getElementById('removeBtnTrigger').addEventListener('click', function () {
            if (confirm('Are you sure you want to remove this product permanently?')) {
                document.getElementById('removeProductForm').submit();
            }
        });

        function setupAjaxFormSubmit(formId) {
            const form = document.getElementById(formId);
            if (!form) return;

            form.addEventListener('submit', async function (e) {
                e.preventDefault();
                const submitBtn = form.querySelector('button[type="submit"]');
                const origText = submitBtn ? submitBtn.innerHTML : '';
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = 'Saving...';
                }

                function getCsrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                        || form.querySelector('input[name="_token"]')?.value
                        || '';
                }

                async function refreshCsrfToken() {
                    try {
                        // Use dedicated lightweight endpoint instead of re-fetching the full page
                        const res = await fetch('/csrf-token', { cache: 'no-store', credentials: 'same-origin' });
                        if (res.ok) {
                            const data = await res.json();
                            if (data && data.token) {
                                document.querySelector('meta[name="csrf-token"]')?.setAttribute('content', data.token);
                                document.querySelectorAll('input[name="_token"]').forEach(el => el.value = data.token);
                                return data.token;
                            }
                        }
                    } catch (err) {
                        console.error('Failed to refresh CSRF token:', err);
                    }
                    return null;
                }

                // Build FormData manually — skip file inputs to avoid exceeding post_max_size.
                // Images are already compressed and stored in hidden image_data inputs.
                function buildFormData(token) {
                    const fd = new FormData();
                    for (const el of form.elements) {
                        if (!el.name) continue;
                        // Skip file inputs — base64 data is in hidden image_data inputs
                        if (el.type === 'file') continue;
                        if (el.type === 'checkbox' || el.type === 'radio') {
                            if (el.checked) fd.append(el.name, el.value);
                        } else {
                            fd.append(el.name, el.value ?? '');
                        }
                    }
                    if (token) fd.set('_token', token);
                    return fd;
                }

                try {
                    let token = getCsrfToken();
                    let formData = buildFormData(token);

                    // Calculate payload size for debugging
                    let payloadSize = 0;
                    for (const [k, v] of formData.entries()) {
                        payloadSize += (typeof v === 'string' ? v.length : v.size || 0);
                    }
                    console.log('[CSRF Debug] Submitting form:', form.id, '| Token:', token ? token.substring(0,10)+'...' : 'MISSING', '| Payload ~', Math.round(payloadSize/1024), 'KB', '| Action:', form.action);

                    let response = await fetch(form.action, {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json, text/html, */*'
                        }
                    });

                    // If 419 Page Expired: auto-refresh token and retry ONCE seamlessly
                    if (response.status === 419) {
                        console.warn('[CSRF Debug] Got 419 on first attempt. Trying to refresh token...');
                        const freshToken = await refreshCsrfToken();
                        console.log('[CSRF Debug] Fresh token:', freshToken ? freshToken.substring(0,10)+'...' : 'FAILED TO GET');
                        if (freshToken) {
                            formData = buildFormData(freshToken);
                            response = await fetch(form.action, {
                                method: 'POST',
                                body: formData,
                                credentials: 'same-origin',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': freshToken,
                                    'Accept': 'application/json, text/html, */*'
                                }
                            });
                        }
                    }

                    if (response.ok || response.redirected) {
                        console.log('[CSRF Debug] Success! Status:', response.status);
                        window.location.reload();
                        return;
                    }

                    console.error('[CSRF Debug] Final response status:', response.status, '| URL:', response.url);
                    if (response.status === 419) {
                        alert('Your session has expired. Please refresh the page and try again.');
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origText;
                        }
                        return;
                    }

                    if (response.status === 422) {
                        const resData = await response.json().catch(() => null);
                        if (resData && resData.errors) {
                            const errMsgs = Object.values(resData.errors).flat().join('\n');
                            alert('Please fix the following:\n' + errMsgs);
                        } else {
                            alert('Validation error. Please check form inputs.');
                        }
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = origText;
                        }
                        return;
                    }

                    window.location.reload();
                } catch (netErr) {
                    console.error('Submit error:', netErr);
                    // Last resort: native form submit (may still 419 if session is truly dead)
                    form.submit();
                }
            });
        }

        setupAjaxFormSubmit('editProductForm');
        setupAjaxFormSubmit('addProductForm');
    </script>
@endsection
