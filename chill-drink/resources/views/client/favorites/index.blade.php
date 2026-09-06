@extends('layouts.client')

@section('title', 'Món yêu thích')

@section('content')
<style>
    .favorites-page { padding: clamp(2.5rem, 6vw, 5rem) 0; background: linear-gradient(180deg, #f0fdf9 0, #fff 22rem); }
    .favorites-heading { max-width: 620px; }
    .favorites-kicker { color: var(--c-primary); font-size: .75rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .favorite-card { position: relative; height: 100%; overflow: hidden; border: 1px solid var(--c-border); border-radius: 20px; background: #fff; box-shadow: var(--shadow-sm); transition: transform .22s ease, box-shadow .22s ease; }
    .favorite-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-xl); }
    .favorite-card__image { display: block; aspect-ratio: 1; overflow: hidden; background: var(--c-bg-warm); }
    .favorite-card__image img, .favorite-card__image .product-image { width: 100%; height: 100%; padding: .75rem; object-fit: contain; transition: transform .35s ease; }
    .favorite-card:hover .favorite-card__image img, .favorite-card:hover .favorite-card__image .product-image { transform: scale(1.05); }
    .favorite-card__body { padding: 1.15rem; }
    .favorite-card__category { color: var(--c-primary); font-size: .72rem; font-weight: 800; text-transform: uppercase; }
    .favorite-card__name { min-height: 2.8rem; font-size: 1.05rem; font-weight: 800; }
    .favorite-card__name a { color: var(--c-ink); text-decoration: none; }
    .favorite-card__price { color: var(--c-primary); font-size: 1.15rem; font-weight: 800; }
    .favorite-remove { display: grid; place-items: center; width: 42px; height: 42px; padding: 0; border: 0; border-radius: 50%; color: #fff; background: #e83e5b; }
    .favorite-remove:hover { background: #d92f4d; transform: scale(1.05); }
    .favorite-remove.is-loading { opacity: .55; pointer-events: none; }
    .favorites-empty { padding: clamp(3rem, 8vw, 6rem) 1.5rem; border: 1px dashed #a7d9cc; border-radius: 24px; background: rgba(255,255,255,.85); text-align: center; }
    .favorites-empty__icon { display: grid; place-items: center; width: 76px; height: 76px; margin: 0 auto 1.25rem; border-radius: 50%; color: #e83e5b; background: #fff0f3; font-size: 2rem; }
</style>

<main class="favorites-page">
    <div class="container">
        <header class="favorites-heading mb-5">
            <p class="favorites-kicker mb-2">Danh sách của bạn</p>
            <h1 class="display-6 fw-bold mb-2">Món yêu thích</h1>
            <p class="text-secondary mb-0">Lưu lại những món hợp gu để lần sau đặt nhanh hơn.</p>
        </header>

        <div class="row g-4" data-favorites-grid>
            @forelse($favorites as $favorite)
                @if($favorite->product)
                    @php($product = $favorite->product)
                    <div class="col-sm-6 col-lg-4 col-xl-3" data-favorite-card>
                        <article class="favorite-card">
                            <a class="favorite-card__image" href="{{ route('products.show', $product->slug) }}">
                                <x-product-image :src="$product->image_url" :sku="$product->sku ?? null" :name="$product->name" :category="$product->category?->name" />
                            </a>
                            <div class="favorite-card__body">
                                <div class="favorite-card__category mb-2">{{ $product->category?->name ?? 'Đồ uống' }}</div>
                                <h2 class="favorite-card__name mb-2"><a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a></h2>
                                <div class="d-flex align-items-center justify-content-between gap-3 mt-3">
                                    <span class="favorite-card__price">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-primary rounded-pill px-3" href="{{ route('products.show', $product->slug) }}">Đặt ngay</a>
                                        <form method="POST" action="{{ route('favorites.toggle', $product) }}" data-favorite-remove-form>
                                            @csrf
                                            <button type="submit" class="favorite-remove" aria-label="Bỏ {{ $product->name }} khỏi yêu thích" title="Bỏ yêu thích"><i class="bi bi-heart-fill"></i></button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </article>
                    </div>
                @endif
            @empty
                <div class="col-12" data-favorites-empty>
                    <div class="favorites-empty">
                        <span class="favorites-empty__icon"><i class="bi bi-heart"></i></span>
                        <h2 class="h3 fw-bold">Chưa có món yêu thích</h2>
                        <p class="text-secondary mb-4">Khám phá menu và bấm trái tim ở món bạn thích nhé.</p>
                        <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Khám phá sản phẩm</a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</main>

<template id="favorites-empty-template">
    <div class="col-12" data-favorites-empty>
        <div class="favorites-empty">
            <span class="favorites-empty__icon"><i class="bi bi-heart"></i></span>
            <h2 class="h3 fw-bold">Chưa có món yêu thích</h2>
            <p class="text-secondary mb-4">Khám phá menu và bấm trái tim ở món bạn thích nhé.</p>
            <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Khám phá sản phẩm</a>
        </div>
    </div>
</template>

<script>
    document.querySelectorAll('[data-favorite-remove-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button');
            const card = form.closest('[data-favorite-card]');
            if (!button || !card || button.classList.contains('is-loading')) return;
            button.classList.add('is-loading');

            try {
                const response = await fetch(form.action, {
                    method: 'POST', body: new FormData(form),
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (!response.ok) throw new Error('favorite_failed');
                const result = await response.json();
                if (result.favorited) throw new Error('favorite_not_removed');
                card.remove();

                const grid = document.querySelector('[data-favorites-grid]');
                if (grid && !grid.querySelector('[data-favorite-card]')) {
                    const empty = document.getElementById('favorites-empty-template');
                    grid.appendChild(empty.content.cloneNode(true));
                }
            } catch (error) {
                form.submit();
            } finally {
                button.classList.remove('is-loading');
            }
        });
    });
</script>
@endsection
