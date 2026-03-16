@php
    $categories = \App\Models\Category::where('is_active', true)->get();
    $categoryId = $this->category_id ?? null;
    
    $products = \App\Models\Product::with(['variants', 'stockTransactions'])
        ->where('is_active', true)
        ->when($categoryId, fn($query) => $query->where('category_id', $categoryId))
        ->get();

    // Color palette for categories
    $catColors = ['#6366f1','#f59e0b','#10b981','#ef4444','#8b5cf6','#ec4899','#14b8a6','#f97316'];
@endphp

<div x-data="{ search: '' }" style="display:flex; flex-direction:column; gap:1.25rem;">
    {{-- Search Bar --}}
    <div style="position:relative;">
        <input 
            type="text" 
            x-model="search"
            placeholder="🔍  Cari menu..." 
            style="width:100%; padding:0.65rem 1rem 0.65rem 2.5rem; background:#f8fafc; border:2px solid #e2e8f0; border-radius:12px; font-size:0.875rem; outline:none; transition:border-color 0.2s;"
            onfocus="this.style.borderColor='#6366f1'" 
            onblur="this.style.borderColor='#e2e8f0'"
        >
        <div style="position:absolute; top:50%; left:0.75rem; transform:translateY(-50%); pointer-events:none;">
            <svg style="width:1.1rem; height:1.1rem; color:#94a3b8;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
        </div>
    </div>

    {{-- Categories Header (Horizontal Scroll) --}}
    <div class="pos-cat-scroll">
        <div 
            class="pos-cat-chip {{ !$categoryId ? 'pos-cat-active' : '' }}"
            style="{{ !$categoryId ? 'background:linear-gradient(135deg,#6366f1,#818cf8); color:#fff; border-color:#6366f1; box-shadow:0 4px 12px rgba(99,102,241,0.3);' : 'background:#f1f5f9; color:#64748b; border-color:#e2e8f0;' }}"
            wire:click="$set('category_id', null)"
        >
            <span style="font-size:1rem;">🍽️</span>
            <span style="font-size:0.65rem; font-weight:700; text-transform:uppercase; letter-spacing:0.03em;">All Items</span>
        </div>
        
        @foreach($categories as $idx => $category)
            @php $color = $catColors[$idx % count($catColors)]; @endphp
            <div 
                class="pos-cat-chip {{ $categoryId == $category->id ? 'pos-cat-active' : '' }}"
                style="{{ $categoryId == $category->id ? "background:linear-gradient(135deg,{$color},{$color}cc); color:#fff; border-color:{$color}; box-shadow:0 4px 12px {$color}4D;" : 'background:#f1f5f9; color:#64748b; border-color:#e2e8f0;' }}"
                wire:click="$set('category_id', '{{ $category->id }}')"
            >
                <span style="font-size:1rem; font-weight:800; {{ $categoryId == $category->id ? 'color:#fff;' : "color:{$color};" }}">
                    {{ strtoupper(substr($category->name, 0, 2)) }}
                </span>
                <span style="font-size:0.6rem; font-weight:700; text-transform:uppercase; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:100%; text-align:center;">
                    {{ $category->name }}
                </span>
            </div>
        @endforeach
    </div>

    {{-- Product Grid --}}
    <div class="pos-product-grid">
        @foreach($products as $product)
            @php
                $in = $product->stockTransactions->where('type', 'in')->sum('quantity');
                $out = $product->stockTransactions->where('type', 'out')->sum('quantity');
                $stock = $in - $out;
                $isLowStock = $stock > 0 && $stock <= 5;
                $isOutOfStock = $stock <= 0;
            @endphp
            <div 
                x-show="!search || '{{ strtolower(addslashes($product->name)) }}'.includes(search.toLowerCase())"
                class="pos-product-card {{ $isOutOfStock ? 'pos-card-disabled' : '' }}"
                @if(!$isOutOfStock)
                    wire:click="addItemToOrder('{{ $product->id }}', '{{ addslashes($product->name) }}', {{ $product->price }}, null, {{ $stock }})"
                @endif
            >
                {{-- Image Area --}}
                <div class="pos-card-img">
                    @if($product->image)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($product->image) }}" alt="{{ $product->name }}" style="width:100%; height:100%; object-fit:cover; transition:transform 0.4s ease;">
                    @else
                        <div style="display:flex; align-items:center; justify-content:center; width:100%; height:100%; background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);">
                            <svg style="width:2.5rem; height:2.5rem; color:#cbd5e1; opacity:0.5;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z"/></svg>
                        </div>
                    @endif
                    
                    {{-- Stock Warning Overlays --}}
                    @if($isOutOfStock)
                        <div style="position:absolute; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,0.7); backdrop-filter:blur(3px); z-index:10;">
                            <span style="padding:0.25rem 0.75rem; font-size:0.7rem; font-weight:800; color:#fff; background:linear-gradient(135deg,#ef4444,#dc2626); border-radius:6px; text-transform:uppercase; letter-spacing:0.05em;">Habis</span>
                        </div>
                    @elseif($isLowStock)
                        <div style="position:absolute; top:0.4rem; left:0.4rem; z-index:10;">
                            <span style="padding:0.15rem 0.5rem; font-size:0.6rem; font-weight:800; color:#92400e; background:#fbbf24; border-radius:4px; box-shadow:0 2px 4px rgba(0,0,0,0.1);">⚠ Sisa {{ $stock }}</span>
                        </div>
                    @else
                        <div class="pos-stock-hover" style="position:absolute; top:0.4rem; left:0.4rem; z-index:10; opacity:0; transition:opacity 0.2s;">
                            <span style="padding:0.15rem 0.5rem; font-size:0.6rem; font-weight:700; color:#334155; background:rgba(255,255,255,0.9); border-radius:4px; backdrop-filter:blur(4px); box-shadow:0 1px 3px rgba(0,0,0,0.1);">📦 {{ $stock }}</span>
                        </div>
                    @endif

                    {{-- Price Badge --}}
                    <div style="position:absolute; bottom:0.4rem; right:0.4rem; z-index:10;">
                        <span style="padding:0.2rem 0.5rem; font-size:0.65rem; font-weight:800; color:#fff; background:linear-gradient(135deg,#6366f1,#8b5cf6); border-radius:6px; box-shadow:0 2px 6px rgba(99,102,241,0.4);">
                            Rp {{ number_format($product->price, 0, ',', '.') }}
                        </span>
                    </div>
                </div>

                {{-- Name at Bottom --}}
                <div style="padding:0.5rem; text-align:center; border-top:1px solid #f1f5f9;">
                    <h4 style="margin:0; font-size:0.7rem; font-weight:700; color:#1e293b; line-height:1.2; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                        {{ $product->name }}
                    </h4>
                </div>

                @if($product->variants->isNotEmpty())
                    <div style="display:flex; flex-wrap:wrap; justify-content:center; gap:0.25rem; padding:0 0.35rem 0.4rem;">
                        @foreach($product->variants as $variant)
                            <button 
                                type="button"
                                style="font-size:0.55rem; font-weight:600; padding:0.15rem 0.4rem; background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; cursor:pointer; transition:all 0.15s; color:#475569;"
                                onmouseover="this.style.background='#6366f1'; this.style.color='#fff'; this.style.borderColor='#6366f1';"
                                onmouseout="this.style.background='#f1f5f9'; this.style.color='#475569'; this.style.borderColor='#e2e8f0';"
                                @if(!$isOutOfStock)
                                    wire:click.stop="addItemToOrder('{{ $product->id }}', '{{ addslashes($product->name . ' - ' . $variant->name) }}', {{ $product->price + $variant->additional_price }}, '{{ $variant->id }}', {{ $stock }})"
                                @else
                                    disabled
                                @endif
                            >
                                {{ $variant->name }}
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<style>
    /* Full-width form container */
    .filament-main { max-width: 100% !important; }

    /* ---- Category Scroll ---- */
    .pos-cat-scroll {
        display: flex;
        flex-wrap: nowrap;
        gap: 0.6rem;
        overflow-x: auto;
        padding-bottom: 0.5rem;
        scrollbar-width: none;
    }
    .pos-cat-scroll::-webkit-scrollbar { display: none; }
    
    .pos-cat-chip {
        flex-shrink: 0;
        min-width: 5.5rem;
        width: 5.5rem;
        height: 4.2rem;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid transparent;
        gap: 0.15rem;
    }
    .pos-cat-chip:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    /* ---- Product Grid ---- */
    .pos-product-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
    }
    @media (min-width: 640px)  { .pos-product-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
    @media (min-width: 768px)  { .pos-product-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
    @media (min-width: 1024px) { .pos-product-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); } }
    @media (min-width: 1280px) { .pos-product-grid { grid-template-columns: repeat(6, minmax(0, 1fr)); } }

    .pos-product-card {
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .pos-product-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
        border-color: #a5b4fc;
    }
    .pos-product-card:hover img {
        transform: scale(1.08);
    }
    .pos-product-card:hover .pos-stock-hover {
        opacity: 1 !important;
    }
    .pos-product-card:active {
        transform: translateY(0px) scale(0.97);
    }

    .pos-card-disabled {
        opacity: 0.55;
        cursor: not-allowed;
        filter: grayscale(0.4);
    }
    .pos-card-disabled:hover {
        transform: none;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        border-color: #e2e8f0;
    }

    .pos-card-img {
        aspect-ratio: 1 / 1;
        position: relative;
        overflow: hidden;
        width: 100%;
        border-bottom: 1px solid #f1f5f9;
    }
    .pos-card-img img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>