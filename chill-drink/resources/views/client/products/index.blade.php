@extends('layouts.client')

@section('title', 'Sản Phẩm')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
@php
$currentSort = request('sort', 'popular');
$sortOptions = [
'popular' => 'Phổ biến nhất',
'newest' => 'Mới nhất',
'price_asc' => 'Giá thấp đến cao',
'price_desc' => 'Giá cao đến thấp',
];
$currentSortLabel = $sortOptions[$currentSort] ?? $sortOptions['popular'];
@endphp
<style>
    .shop-page {
        padding-top: 2rem;
        padding-bottom: 5rem;
    }

    .shop-heading {
        max-width: 720px;
    }

    .shop-main-top {
        display: grid;
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .shop-hero {
        position: relative;
        display: grid;
        grid-template-columns: 1.35fr 1fr;
        gap: 2rem;
        overflow: hidden;
        border-radius: 28px;
        padding: 2.25rem 2.5rem;
        background: linear-gradient(135deg, #094838 0%, #10634f 50%, #187d66 100%);
        box-shadow: 0 20px 45px rgba(9, 72, 56, 0.22);
        color: #ffffff;
        min-height: 310px;
        align-items: center;
        isolation: isolate;
    }

    .shop-hero__ambient {
        position: absolute;
        top: -15%;
        right: -10%;
        width: 440px;
        height: 440px;
        border-radius: 50%;
        background-size: cover;
        background-position: center;
        filter: blur(55px);
        opacity: 0.32;
        pointer-events: none;
        z-index: 0;
        animation: heroAmbientPulse 7s ease-in-out infinite alternate;
    }

    .shop-hero__mesh {
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 55%),
            radial-gradient(circle at 15% 85%, rgba(0, 0, 0, 0.16) 0%, transparent 50%);
        pointer-events: none;
        z-index: 0;
    }

    .shop-hero__content {
        position: relative;
        z-index: 1;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: flex-start;
    }

    .shop-hero__badge {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        padding: 0.45rem 1rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(255, 255, 255, 0.28);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        color: #ffffff;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.07em;
    }

    .shop-hero__badge i {
        color: #ffd166;
        font-size: 0.95rem;
    }

    .shop-hero__title {
        font-size: clamp(1.85rem, 3.2vw, 2.65rem);
        line-height: 1.15;
        font-weight: 800;
        margin: 0.9rem 0 0.65rem;
        letter-spacing: -0.02em;
        color: #ffffff;
    }

    .shop-hero__text {
        max-width: 38rem;
        font-size: 0.98rem;
        line-height: 1.65;
        margin-bottom: 1.5rem;
        color: rgba(255, 255, 255, 0.88);
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .shop-hero__actions {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        flex-wrap: wrap;
    }

    .shop-hero__button {
        display: inline-flex;
        align-items: center;
        gap: 0.6rem;
        padding: 0.78rem 1.85rem;
        border-radius: 999px;
        font-weight: 700;
        font-size: 0.95rem;
        background: #ffffff;
        color: #0e5e48;
        border: none;
        box-shadow: 0 8px 22px rgba(0, 0, 0, 0.16);
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
    }

    .shop-hero__button:hover {
        background: #f0fdf9;
        color: #084c3c;
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.22);
    }

    .shop-hero__button i {
        transition: transform 0.25s ease;
    }

    .shop-hero__button:hover i {
        transform: translateX(4px);
    }

    .shop-hero__visual {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .shop-hero__card {
        position: relative;
        width: 100%;
        max-width: 290px;
        aspect-ratio: 1 / 1;
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.12);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        padding: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    .shop-hero:hover .shop-hero__card {
        transform: translateY(-4px) scale(1.02);
    }

    .shop-hero__card img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 18px;
        transition: transform 0.4s ease;
    }

    .shop-hero:hover .shop-hero__card img {
        transform: scale(1.05);
    }

    .shop-hero__price-tag {
        position: absolute;
        bottom: 1.25rem;
        right: 1.25rem;
        background: rgba(9, 72, 56, 0.88);
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        border: 1px solid rgba(255, 255, 255, 0.35);
        color: #ffffff;
        font-weight: 800;
        font-size: 0.92rem;
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
    }

    @keyframes heroAmbientPulse {
        0% {
            transform: scale(1) rotate(0deg);
            opacity: 0.24;
        }

        100% {
            transform: scale(1.22) rotate(12deg);
            opacity: 0.38;
        }
    }

    @media (max-width: 991px) {
        .shop-hero {
            grid-template-columns: 1fr;
            text-align: center;
            padding: 2rem 1.5rem;
            gap: 1.5rem;
        }

        .shop-hero__content {
            align-items: center;
        }

        .shop-hero__actions {
            justify-content: center;
        }

        .shop-hero__card {
            max-width: 240px;
        }
    }

    .shop-vouchers {
        display: grid;
        gap: 1rem;
    }

    .shop-vouchers__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .shop-vouchers__header h3 {
        margin: 0;
        font-weight: 700;
    }

    .shop-vouchers__header a {
        color: var(--c-primary);
        font-weight: 700;
        text-decoration: none;
    }

    .voucher-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        align-items: stretch;
    }

    .voucher-card {
        position: relative;
        background: linear-gradient(90deg, #2fb9a0 0 156px, #ffffff 156px 100%);
        border: 1px solid rgba(47, 185, 160, 0.24);
        border-radius: 18px;
        padding: 1.05rem 1.05rem 1.05rem 1rem;
        box-shadow: 0 18px 40px rgba(15, 78, 62, 0.08);
        min-height: 132px;
        display: grid;
        grid-template-columns: 156px minmax(0, 1fr) auto;
        grid-template-areas:
            "left code action"
            "left info action";
        column-gap: 1.45rem;
        row-gap: 0.35rem;
        align-items: center;
        overflow: hidden;
    }

    .voucher-card::before,
    .voucher-card::after {
        content: "";
        position: absolute;
        top: 50%;
        width: 18px;
        height: 18px;
        border-radius: 50%;
        background: #f6faf9;
        border: 1px solid rgba(15, 78, 62, 0.08);
        transform: translateY(-50%);
    }

    .voucher-card::before {
        left: -9px;
    }

    .voucher-card::after {
        right: -9px;
    }

    .voucher-card__visual {
        grid-area: left;
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        justify-self: center;
        align-self: start;
        margin-top: 0.2rem;
        background: rgba(255, 255, 255, 0.18);
        color: #ffffff;
        font-size: 1.25rem;
    }

    .voucher-card__top {
        display: contents;
    }

    .voucher-card__code {
        grid-area: code;
        align-self: end;
        font-size: 1.04rem;
        font-weight: 800;
        letter-spacing: 0.01em;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        line-height: 1.25;
    }

    .voucher-card__tag {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        background: rgba(255, 137, 73, 0.16);
        color: #ff6d2d;
        font-size: 0.68rem;
        font-weight: 800;
        cursor: pointer;
        border: 1px solid rgba(255, 137, 73, 0.25);
        transition: background 0.2s ease, transform 0.2s ease;
        white-space: nowrap;
        grid-area: action;
        align-self: start;
        margin-top: 0.05rem;
    }

    .voucher-card__tag:hover {
        background: rgba(255, 137, 73, 0.24);
        transform: translateY(-1px);
    }

    .voucher-card__info {
        grid-area: info;
        align-self: start;
        margin: 0;
        font-size: 0.88rem;
        color: var(--c-muted);
        line-height: 1.45;
        min-width: 0;
    }

    .voucher-card__label {
        grid-area: left;
        align-self: end;
        justify-self: center;
        width: 112px;
        color: #ffffff;
        font-size: 0.86rem;
        font-weight: 900;
        line-height: 1.15;
        text-align: center;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 0.15rem;
    }

    .voucher-card__highlight {
        display: inline-flex;
        width: fit-content;
        max-width: 100%;
        margin-top: 0.55rem;
        padding: 0.28rem 0.55rem;
        border: 1px solid rgba(13, 147, 115, 0.28);
        border-radius: 6px;
        color: var(--drink-primary-dark, var(--c-primary-dark, #067a5f));
        background: rgba(13, 147, 115, 0.06);
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.25;
    }

    .shop-sidebar {
        position: -webkit-sticky;
        position: sticky;
        top: 96px;
        z-index: 20;
    }

    .btn-new-products {
        background: linear-gradient(135deg, #ff9f1c 0%, #f77f00 100%);
        border: none;
        color: #ffffff !important;
        font-weight: 700;
        font-size: 0.95rem;
        letter-spacing: 0.02em;
        box-shadow: 0 6px 18px rgba(247, 127, 0, 0.28);
        transition: all 0.22s ease;
    }

    .btn-new-products:hover {
        background: linear-gradient(135deg, #f77f00 0%, #e06c00 100%);
        box-shadow: 0 8px 24px rgba(247, 127, 0, 0.38);
        transform: translateY(-2px);
    }

    .shop-grid-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1.25rem;
        flex-wrap: wrap;
    }

    .shop-sort {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        white-space: nowrap;
    }

    .shop-sort .form-select {
        width: auto;
        min-width: 170px;
        min-height: 38px;
        padding-top: 0.4rem;
        padding-right: 2.4rem;
        padding-bottom: 0.4rem;
        font-size: 0.82rem;
        border-radius: var(--radius-sm);
        border: 1.5px solid var(--c-border, #e5e7eb);
        background-color: var(--c-surface, #fff) !important;
        background-image:
            linear-gradient(45deg, transparent 50%, var(--c-muted, #6b7280) 50%),
            linear-gradient(135deg, var(--c-muted, #6b7280) 50%, transparent 50%);
        background-position:
            calc(100% - 18px) calc(50% - 2px),
            calc(100% - 12px) calc(50% - 2px);
        background-size: 6px 6px, 6px 6px;
        background-repeat: no-repeat !important;
        appearance: none !important;
        -webkit-appearance: none !important;
    }

    .shop-sort .form-select:focus {
        border-color: var(--c-primary, #0d9373);
        box-shadow: 0 0 0 3px var(--c-primary-glow, rgba(13, 147, 115, 0.15));
    }

    .sort-dropdown {
        position: relative;
        min-width: 220px;
        z-index: 20;
    }

    .sort-dropdown.open,
    .sort-dropdown:focus-within {
        z-index: 120;
    }

    .sort-dropdown--full {
        width: 100%;
        min-width: 0;
    }

    .sort-dropdown-toggle {
        width: 100%;
        min-height: 46px;
        border: 1.5px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        padding: 0.62rem 0.85rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        background: #fff;
        color: var(--c-ink, #111827);
        font-weight: 800;
        cursor: pointer;
        transition: border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
    }

    .sort-dropdown-toggle span {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .sort-dropdown.open .sort-dropdown-toggle,
    .sort-dropdown:focus-within .sort-dropdown-toggle,
    .sort-dropdown-toggle:focus {
        background: var(--c-primary-light, #e6f7f2);
        border-color: var(--c-primary, #0d9373);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.13);
    }

    .sort-dropdown-toggle i {
        color: var(--c-muted, #6b7280);
        transition: transform 0.16s ease;
    }

    .sort-dropdown.open .sort-dropdown-toggle i,
    .sort-dropdown:focus-within .sort-dropdown-toggle i {
        transform: rotate(180deg);
    }

    .sort-dropdown-menu {
        position: absolute;
        top: calc(100% + 0.35rem);
        left: 0;
        right: 0;
        z-index: 80;
        display: none;
        overflow: hidden;
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: var(--radius-sm, 8px);
        background: #fff;
        box-shadow: var(--shadow-lg);
        padding: 0.3rem;
    }

    .sort-dropdown.open .sort-dropdown-menu,
    .sort-dropdown:focus-within .sort-dropdown-menu {
        display: block;
    }

    .sort-dropdown-option {
        display: block !important;
        width: 100%;
        border: 0;
        border-radius: 7px;
        background: transparent;
        color: var(--c-ink, #111827);
        text-align: left;
        padding: 0.75rem 0.85rem;
        font-weight: 800;
        transition: background 0.16s ease, color 0.16s ease, transform 0.16s ease;
    }

    .sort-dropdown-option:hover,
    .sort-dropdown-option.active {
        background: var(--c-primary-light, #e6f7f2);
        color: var(--c-primary-dark, #067a5f);
    }

    .sort-dropdown-option:hover {
        transform: translateX(2px);
    }

    .promo-panel,
    .shop-product-card {
        position: relative;
        border: 1px solid var(--drink-border);
        border-radius: var(--radius-md);
        background: rgba(255, 255, 255, 0.84);
        box-shadow: var(--shadow-sm);
    }

    .filter-panel {
        position: relative;
        border: 1px solid rgba(13, 147, 115, 0.12);
        border-radius: 20px;
        background: #ffffff;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        overflow: visible;
    }

    .filter-panel__mobile-toggle {
        display: none;
    }

    .filter-title {
        color: var(--drink-primary);
        font-size: 0.78rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
    }

    .category-chip {
        min-height: 34px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        width: 100%;
        border: 0;
        border-radius: 10px;
        background: transparent;
        color: #4b5563;
        font-size: 0.88rem;
        font-weight: 600;
        padding: 0.42rem 0.65rem;
        text-align: left;
        text-decoration: none;
        transition: all 0.18s ease;
    }

    .category-radio {
        width: 18px;
        height: 18px;
        flex: 0 0 auto;
        border-radius: 50%;
        border: 1.5px solid var(--c-subtle, #9ca3af);
        background: #ffffff;
        transition: border-color 0.2s ease, background 0.2s ease, box-shadow 0.2s ease;
    }

    .category-list {
        gap: 0.15rem !important;
        margin-top: 0;
    }

    .category-chip:hover {
        background: transparent;
        color: var(--drink-primary-dark);
        box-shadow: none;
        transform: translateX(3px);
    }

    .category-chip.active {
        background: transparent !important;
        color: var(--drink-primary) !important;
        box-shadow: none;
    }

    .category-chip.active:hover {
        background: transparent !important;
        color: var(--drink-primary-dark) !important;
    }

    .category-chip.active .category-radio {
        border-color: var(--drink-primary, #0d9373);
        background: var(--drink-primary, #0d9373);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, 0.16);
    }

    .category-chip.active .category-radio::after {
        content: "";
        display: block;
        width: 6px;
        height: 6px;
        margin: 4.5px auto 0;
        border-radius: 50%;
        background: #ffffff;
    }

    .range-control {
        accent-color: var(--drink-primary);
    }

    .promo-panel {
        position: relative;
        min-height: 250px;
        overflow: hidden;
    }

    .promo-panel img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.7s ease;
    }

    .promo-panel:hover img {
        transform: scale(1.03);
    }

    /* ─── Product Card (Same as Home Weather Section) ─── */
    .home-product {
        display: flex;
        flex-direction: column;
        border-radius: 20px;
        overflow: hidden;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        height: 100%;
        position: relative;
    }

    .home-product:hover {
        transform: translateY(-5px);
        border-color: rgba(13, 147, 115, 0.3);
        box-shadow: 0 12px 32px rgba(13, 147, 115, 0.15);
    }

    .home-product__img {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        background: linear-gradient(145deg, #f9fafb, #fff);
    }

    .home-product__img img,
    .home-product__img .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover !important;
        padding: 0 !important;
        display: block;
        transition: transform 0.5s ease;
    }

    .home-product:hover .home-product__img img,
    .home-product:hover .home-product__img .product-image {
        transform: scale(1.05);
    }

    .home-product .product-image-cart-form {
        position: absolute;
        inset: 0;
        z-index: 3;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
        opacity: 0;
        background: rgba(7, 58, 53, 0.12);
        transition: opacity 0.22s ease;
    }

    .home-product__img:hover .product-image-cart-form,
    .home-product__img:focus-within .product-image-cart-form {
        opacity: 1;
    }

    .home-product .product-cart-btn {
        width: 54px;
        height: 54px;
        border: 0;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #0d9373;
        color: #ffffff;
        box-shadow: 0 18px 36px rgba(13, 147, 115, 0.28);
        pointer-events: auto;
        transform: translateY(8px) scale(0.94);
        transition: transform 0.22s ease, background 0.22s ease, box-shadow 0.22s ease;
    }

    .home-product .product-cart-btn i {
        font-size: 1.25rem !important;
        line-height: 1;
    }

    .home-product__img:hover .product-cart-btn,
    .home-product__img:focus-within .product-cart-btn {
        transform: translateY(0) scale(1);
    }

    .home-product .product-cart-btn:hover {
        background: #0a7a5f;
        box-shadow: 0 20px 40px rgba(13, 147, 115, 0.34);
    }

    .home-product__tag {
        position: absolute;
        top: 0.875rem;
        left: 0.875rem;
        padding: 0.3rem 0.75rem;
        border-radius: 50px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        color: #0a7a5f;
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        z-index: 2;
    }

    .home-product__favorite-form {
        position: absolute;
        top: 0.75rem;
        right: 0.75rem;
        z-index: 5;
        margin: 0;
    }

    .home-product__favorite {
        display: inline-grid;
        place-items: center;
        width: 42px;
        height: 42px;
        padding: 0;
        border: 1px solid rgba(255, 255, 255, 0.9);
        border-radius: 50%;
        color: #687875;
        background: rgba(255, 255, 255, 0.95);
        box-shadow: 0 8px 22px rgba(16, 63, 55, 0.15);
        text-decoration: none;
        backdrop-filter: blur(8px);
        transition: transform 0.18s ease, color 0.18s ease, background 0.18s ease;
    }

    .home-product__favorite:hover {
        color: #e83e5b;
        transform: scale(1.08);
    }

    .home-product__favorite.is-active {
        color: #fff;
        border-color: #e83e5b;
        background: #e83e5b;
    }

    .home-product__favorite.is-loading {
        opacity: 0.6;
        pointer-events: none;
    }

    .home-product__favorite i {
        font-size: 1.1rem;
        line-height: 1;
    }

    .home-product__body {
        display: flex;
        flex-direction: column;
        flex: 1;
        padding: 1.15rem 1.25rem 1.25rem;
    }

    .home-product__body [data-availability-badge] {
        display: flex !important;
        align-items: center;
        justify-content: center;
        width: 100% !important;
        text-align: center;
        padding: 0.32rem 0.6rem;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.78rem;
        line-height: 1.3;
        white-space: normal;
        margin-bottom: 0.55rem;
    }

    .home-product__rating {
        display: flex;
        align-items: center;
        gap: 4px;
        margin-bottom: 0.45rem;
        font-size: 0.78rem;
        color: #F59E0B;
    }

    .home-product__rating span {
        color: #6B7280;
        margin-left: 2px;
    }

    .home-product__name {
        font-size: 1.1rem;
        font-weight: 700;
        margin: 0 0 0.25rem;
        letter-spacing: -0.02em;
        line-height: 1.35;
    }

    .home-product__name a {
        color: #111827;
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .home-product__name a:hover {
        color: #0D9373;
    }

    .home-product__sku {
        font-size: 0.78rem;
        color: #9CA3AF;
        font-family: ui-monospace, monospace;
        margin: 0 0 0.85rem;
    }

    .home-product__footer {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: auto;
        padding-top: 0.85rem;
        border-top: 1px solid #F3F4F6;
        text-align: center;
    }

    .home-product__price {
        font-size: 1.25rem;
        font-weight: 800;
        color: #0D9373;
        letter-spacing: -0.02em;
    }

    .product-detail-btn {
        min-height: 38px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 1 1 auto;
        border-radius: var(--radius-sm);
        background: var(--c-border-light, #f3f4f6);
        color: var(--c-ink, #111827);
        font-size: 0.78rem;
        font-weight: 800;
        text-decoration: none;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .product-detail-btn:hover {
        background: var(--drink-primary-soft);
        color: var(--drink-primary-dark);
    }

    .shop-empty-state {
        border: 1px solid var(--drink-border);
        border-radius: 24px;
        background: rgba(255, 255, 255, 0.86);
        box-shadow: 0 18px 42px rgba(79, 183, 168, 0.10);
    }

    .pager-dot {
        width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--drink-border);
        border-radius: 50%;
        color: var(--drink-muted);
        font-weight: 800;
        text-decoration: none;
    }

    .pager-dot.active,
    .pager-dot:hover {
        background: var(--drink-primary);
        color: #ffffff;
        border-color: var(--drink-primary);
    }

    @media (max-width: 1199.98px) {
        .shop-hero {
            grid-template-columns: 1fr;
            min-height: auto;
        }

        .shop-hero__visual img {
            width: min(100%, 280px);
            max-height: 250px;
        }

        .voucher-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 767.98px) {
        .shop-sidebar {
            position: static;
        }

        .shop-main-top,
        .shop-hero,
        .shop-vouchers {
            grid-template-columns: 1fr;
        }

        .voucher-grid {
            grid-template-columns: 1fr;
        }

        .voucher-card {
            min-height: 108px;
            background: linear-gradient(90deg, #2fb9a0 0 128px, #ffffff 128px 100%);
            grid-template-columns: 128px minmax(0, 1fr);
            grid-template-areas:
                "left code"
                "left info"
                "left action";
            column-gap: 1rem;
        }

        .voucher-card__tag {
            justify-self: start;
            margin-top: 0.25rem;
        }

        .voucher-card__label {
            width: 90px;
        }

        .filter-panel {
            padding: 0.75rem !important;
            overflow: hidden;
        }

        .filter-panel__heading {
            display: none;
        }

        .filter-panel__mobile-toggle {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            min-height: 44px;
            padding: 0.55rem 0.75rem;
            border: 1px solid var(--drink-border);
            border-radius: 12px;
            color: var(--drink-primary-dark);
            background: var(--c-primary-light, #e6f7f2);
            font-size: 0.9rem;
            font-weight: 800;
        }

        .filter-panel__mobile-toggle i {
            transition: transform 0.2s ease;
        }

        .filter-panel__body {
            display: none;
            padding-top: 0.8rem;
        }

        .filter-panel.is-open .filter-panel__body {
            display: block;
        }

        .filter-panel.is-open .filter-panel__mobile-toggle>i {
            transform: rotate(180deg);
        }

        .category-list {
            display: flex !important;
            gap: 0.65rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
            scrollbar-width: thin;
        }

        .category-chip {
            flex: 0 0 auto;
            width: auto;
            white-space: nowrap;
            border: 1px solid var(--drink-border);
            padding: 0.55rem 0.75rem;
        }

        .category-chip.active {
            border-color: var(--drink-primary);
            background: var(--c-primary-light, #e6f7f2) !important;
        }

        .category-radio {
            display: none;
        }

        .shop-grid-head {
            align-items: flex-start;
            flex-direction: column;
        }
    }

    @media (max-width: 575.98px) {
        .shop-page {
            padding-top: 1rem;
            padding-bottom: 2.5rem;
        }

        .shop-main-top {
            gap: 0.8rem;
            margin-bottom: 1rem;
        }

        .shop-hero {
            min-height: 0;
            padding: 1.1rem;
            gap: 0.8rem;
            border-radius: 18px;
        }

        .shop-vouchers,
        .filter-panel,
        .promo-panel {
            border-radius: 14px;
        }

        .shop-sidebar {
            gap: 0.75rem !important;
        }

        .shop-sidebar>.d-grid {
            margin-bottom: 0 !important;
        }

        .shop-sidebar>.d-grid .btn {
            min-height: 42px;
            padding-block: 0.48rem;
            font-size: 0.9rem;
        }

        .filter-panel__body {
            padding-top: 0.65rem;
        }

        .filter-panel .filter-title {
            margin-bottom: 0.45rem !important;
            font-size: 0.68rem;
        }

        .filter-panel__body>.border-top {
            margin-top: 0.75rem !important;
            padding-top: 0.75rem !important;
        }

        .category-list {
            gap: 0.4rem !important;
        }

        .category-chip {
            min-height: 34px;
            padding: 0.38rem 0.65rem;
            font-size: 0.76rem;
        }

        .shop-grid-head {
            gap: 0.55rem;
            margin-bottom: 0.8rem;
        }

        .shop-products-grid {
            --bs-gutter-x: 0.6rem;
            --bs-gutter-y: 0.6rem;
        }

    }

    @media (min-width: 576px) and (max-width: 991.98px) {
        .shop-page {
            padding-block: 1.25rem 2.5rem;
        }

        .shop-products-grid {
            --bs-gutter-x: .8rem;
            --bs-gutter-y: .8rem;
        }

        .shop-products-grid>.col-sm-6 {
            width: 33.333333%;
        }

        .shop-product-card {
            padding: .7rem;
            border-radius: 14px;
        }

        .shop-product-image {
            aspect-ratio: 1;
            border-radius: 11px;
        }

        .shop-product-card h3 {
            font-size: .92rem !important;
        }

        .shop-product-card .product-desc {
            display: none;
        }

        .shop-product-card .product-cart-btn {
            min-height: 38px;
            padding-inline: .55rem;
            font-size: .72rem;
        }

        .shop-product-card .add-round {
            width: 36px !important;
            height: 36px !important;
        }
    }
</style>
@include('client.partials.drink-customizer-styles')

<section class="shop-page">
    <div class="container">
        <div class="row g-4">
            <aside class="col-lg-3">
                <div class="shop-sidebar d-flex flex-column gap-3">
                    <div class="filter-panel p-4">
                        <div class="d-flex align-items-center justify-content-between mb-3 pb-2 border-bottom">
                            <h2 class="filter-panel__heading h5 fw-bold mb-0 d-flex align-items-center gap-2">
                                <i class="bi bi-funnel-fill text-primary" style="font-size: 1rem;"></i>
                                <span>Bộ lọc</span>
                            </h2>
                            @if(request('category') || request('sort') || request('min_price') || request('max_price'))
                            <a href="{{ route('products.index') }}" class="text-secondary small text-decoration-none fw-semibold d-inline-flex align-items-center gap-1" title="Xóa tất cả bộ lọc">
                                <i class="bi bi-arrow-counterclockwise"></i>Làm mới
                            </a>
                            @endif
                        </div>

                        <button type="button" class="filter-panel__mobile-toggle" aria-expanded="false" aria-controls="productFilterBody">
                            <span><i class="bi bi-sliders me-2"></i>Bộ lọc sản phẩm</span>
                            <i class="bi bi-chevron-down" aria-hidden="true"></i>
                        </button>

                        <div class="filter-panel__body" id="productFilterBody">

                            <!-- SẮP XẾP (ĐÃ ĐƯỢC CHUYỂN LÊN TRÊN ĐẦU) -->
                            <div class="mb-4">
                                <h3 class="filter-title mb-2.5 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-arrow-down-up text-primary"></i>
                                    <span>Sắp xếp theo</span>
                                </h3>
                                <form method="GET" action="{{ route('products.index') }}">
                                    @foreach(request()->except(['sort', 'page']) as $key => $value)
                                    @if(is_array($value))
                                    @foreach($value as $nestedValue)
                                    <input type="hidden" name="{{ $key }}[]" value="{{ $nestedValue }}">
                                    @endforeach
                                    @else
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                    @endif
                                    @endforeach
                                    <input type="hidden" name="sort" value="{{ $currentSort }}" data-sort-input>
                                    <div class="sort-dropdown sort-dropdown--full" data-sort-dropdown>
                                        <button type="button" class="sort-dropdown-toggle" aria-expanded="false">
                                            <span data-sort-label>{{ $currentSortLabel }}</span>
                                            <i class="bi bi-chevron-down"></i>
                                        </button>
                                        <div class="sort-dropdown-menu shadow-lg">
                                            @foreach($sortOptions as $value => $label)
                                            <button type="button" class="sort-dropdown-option {{ $currentSort === $value ? 'active' : '' }}" data-sort-value="{{ $value }}">
                                                {{ $label }}
                                            </button>
                                            @endforeach
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <div class="border-top my-3.5"></div>

                            <!-- DANH MỤC -->
                            <div class="mb-4">
                                <h3 class="filter-title mb-2.5 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-grid-fill text-primary"></i>
                                    <span>Danh mục</span>
                                </h3>
                                <div class="category-list d-grid gap-1">
                                    <a href="{{ route('products.index') }}" class="category-chip {{ !request('category') && empty($searchQuery) ? 'active' : '' }}">
                                        <span>Tất cả</span>
                                        <span class="category-radio" aria-hidden="true"></span>
                                    </a>
                                    @forelse($categories as $category)
                                    <a href="{{ route('products.index', ['category' => $category->id]) }}" class="category-chip {{ request('category') == $category->id ? 'active' : '' }}">
                                        <span>{{ $category->name }}</span>
                                        <span class="category-radio" aria-hidden="true"></span>
                                    </a>
                                    @empty
                                    <a href="{{ route('products.index') }}" class="category-chip"><span>Trà sữa</span><span class="category-radio" aria-hidden="true"></span></a>
                                    <a href="{{ route('products.index') }}" class="category-chip"><span>Cà phê</span><span class="category-radio" aria-hidden="true"></span></a>
                                    <a href="{{ route('products.index') }}" class="category-chip"><span>Nước ép</span><span class="category-radio" aria-hidden="true"></span></a>
                                    <a href="{{ route('products.index') }}" class="category-chip"><span>Sinh tố</span><span class="category-radio" aria-hidden="true"></span></a>
                                    @endforelse
                                </div>
                            </div>

                            <div class="border-top my-3.5"></div>

                            <!-- LỌC THEO GIÁ -->
                            <div>
                                <h3 class="filter-title mb-2.5 d-flex align-items-center gap-1.5">
                                    <i class="bi bi-tag-fill text-primary"></i>
                                    <span>Khoảng giá</span>
                                </h3>
                                <form action="{{ route('products.index') }}" method="GET" id="priceFilterForm">
                                    @if(request('category'))
                                    <input type="hidden" name="category" value="{{ request('category') }}">
                                    @endif
                                    @if(!empty($searchQuery))
                                    <input type="hidden" name="search" value="{{ $searchQuery }}">
                                    @endif
                                    @if(request('sort'))
                                    <input type="hidden" name="sort" value="{{ request('sort') }}">
                                    @endif

                                    <input type="hidden" name="min_price" id="minPriceInput" value="{{ request('min_price', 0) }}">
                                    <input type="hidden" name="max_price" id="maxPriceInput" value="{{ request('max_price', 100000) }}">

                                    <input
                                        class="range-control w-100"
                                        type="range"
                                        min="0"
                                        max="100000"
                                        step="5000"
                                        value="{{ request('max_price', 100000) }}"
                                        id="priceRange">
                                    <div class="d-flex justify-content-between text-secondary small fw-semibold mt-2">
                                        <span id="minPriceLabel">{{ number_format(request('min_price', 0), 0, ',', '.') }}đ</span>
                                        <span id="maxPriceLabel">{{ number_format(request('max_price', 100000), 0, ',', '.') }}đ</span>
                                    </div>

                                    @if(request('min_price') || request('max_price'))
                                    <div class="mt-3">
                                        <a href="{{ route('products.index', request()->except(['min_price', 'max_price'])) }}" class="btn btn-outline-secondary w-100 fw-bold btn-sm rounded-pill">Xóa lọc giá</a>
                                    </div>
                                    @endif
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            </aside>

            <div class="col-lg-9">
                <div class="shop-main-top">
                    @php
                    $heroProduct = $latestProduct ?? null;
                    $heroName = $heroProduct?->name ?? 'Matcha Dừa Mây';
                    $heroDesc = $heroProduct?->display_description ?? 'Sự kết hợp hoàn hảo giữa vị đắng thanh của Matcha và vị béo của cốt dừa.';
                    $heroImage = $heroProduct?->image_url ?? asset('images/matcha.png');
                    $heroUrl = $heroProduct ? route('products.show', $heroProduct->slug) : route('products.index');
                    $heroPrice = $heroProduct?->price ? number_format($heroProduct->price, 0, ',', '.') . 'đ' : null;
                    @endphp

                    <div class="shop-hero">
                        <div class="shop-hero__ambient" style="background-image: url('{{ $heroImage }}');"></div>
                        <div class="shop-hero__mesh"></div>

                        <div class="shop-hero__content">
                            <span class="shop-hero__badge">
                                <i class="bi bi-stars"></i> Món mới nhất
                            </span>
                            <h2 class="shop-hero__title">{{ $heroName }}</h2>
                            <p class="shop-hero__text">{{ $heroDesc }}</p>
                            <div class="shop-hero__actions">
                                <a href="{{ $heroUrl }}" class="shop-hero__button">
                                    <span>Thử ngay</span>
                                    <i class="bi bi-arrow-right"></i>
                                </a>
                            </div>
                        </div>

                        <div class="shop-hero__visual">
                            <div class="shop-hero__card">
                                <img src="{{ $heroImage }}" alt="{{ $heroName }}" loading="lazy">
                                @if($heroPrice)
                                <span class="shop-hero__price-tag">{{ $heroPrice }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="shop-vouchers">
                        <div class="shop-vouchers__header">
                            <h3>Mã Giảm Giá Phổ Biến</h3>
                            <a href="{{ route('products.index') }}">Xem tất cả</a>
                        </div>
                        <div class="voucher-grid">
                            @forelse($featuredVouchers ?? [] as $voucher)
                            <div class="voucher-card">
                                <div class="voucher-card__top">
                                    <span class="voucher-card__visual">
                                        @if($voucher->type === 'percent')
                                        <i class="bi bi-percent"></i>
                                        @elseif(str_contains(strtolower($voucher->code), 'ship') || str_contains(strtolower($voucher->description ?? ''), 'ship'))
                                        <i class="bi bi-truck"></i>
                                        @else
                                        <i class="bi bi-ticket-perforated"></i>
                                        @endif
                                    </span>
                                    <span class="voucher-card__label">{{ $voucher->code }}</span>
                                    <div class="voucher-card__code">{{ $voucher->code }}</div>
                                    <button type="button"
                                        class="voucher-card__tag"
                                        data-receive-code="{{ $voucher->code }}"
                                        data-voucher-id="{{ $voucher->id }}">
                                        NHẬN
                                    </button>
                                </div>
                                <p class="voucher-card__info">
                                    {{ $voucher->description ?? 'Phiếu giảm giá' }}
                                    <span class="voucher-card__highlight">
                                        @if($voucher->type === 'percent')
                                        Giảm {{ $voucher->value }}%
                                        @if($voucher->max_discount > 0)
                                        (Tối đa {{ number_format($voucher->max_discount, 0, ',', '.') }}đ)
                                        @endif
                                        @else
                                        Giảm {{ number_format($voucher->value, 0, ',', '.') }}đ
                                        @endif
                                        @if($voucher->min_order > 0)
                                        - Đơn từ {{ number_format($voucher->min_order, 0, ',', '.') }}đ
                                        @endif
                                    </span>
                                </p>
                            </div>
                            @empty
                            <div class="col-span-2 text-center py-4 text-secondary">
                                <i class="bi bi-ticket-perforated fs-2 d-block mb-2"></i>
                                <div style="font-size: 0.85rem;">Chưa có mã giảm giá nào</div>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="shop-grid-head mb-4">
                    <div>
                        <h2 class="h4 fw-bold mb-1">Danh sách đồ uống</h2>
                        <p class="text-secondary mb-0">{{ $products->total() }} sản phẩm phù hợp</p>
                    </div>
                </div>

                @if(!empty($searchQuery))
                <div class="search-results-banner">
                    Kết quả tìm kiếm cho <strong>"{{ $searchQuery }}"</strong>
                    — {{ $products->total() }} sản phẩm
                    <a href="{{ route('products.index', request()->only('category')) }}" class="ms-2 text-decoration-none">Xóa tìm kiếm</a>
                </div>
                @endif

                <div class="row g-4 shop-products-grid">
                    @forelse($products as $product)
                    @php
                    $reviewCount = (int) ($product->reviews_count ?? 0);
                    $rating = $reviewCount > 0 ? round((float) ($product->reviews_avg_rating ?? 0), 1) : 0;
                    $isAvailableAtCurrentBranch = $product->availabilityAt($branch) === true;
                    @endphp
                    <div class="col-sm-6 col-xl-4">
                        <article class="home-product">
                            <div class="home-product__img">
                                <span class="home-product__tag">{{ $product->category?->name ?? 'Đồ uống' }}</span>
                                @auth
                                    @php
                                        $isFavorite = $favoriteProductIds->contains($product->id);
                                    @endphp
                                    <form class="home-product__favorite-form" method="POST" action="{{ route('favorites.toggle', $product) }}" data-favorite-form>
                                        @csrf
                                        <button type="submit" class="home-product__favorite {{ $isFavorite ? 'is-active' : '' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" title="{{ $isFavorite ? 'Bỏ yêu thích' : 'Yêu thích' }}" data-favorite-button>
                                            <i class="bi {{ $isFavorite ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @else
                                <a class="home-product__favorite-form home-product__favorite" href="{{ route('login') }}" aria-label="Đăng nhập để yêu thích" title="Đăng nhập để yêu thích">
                                    <i class="bi bi-heart"></i>
                                </a>
                                @endauth
                                <a href="{{ route('products.show', $product->slug) }}">
                                    <x-product-image
                                        :src="$product->image_url"
                                        :sku="$product->sku ?? null"
                                        :name="$product->name"
                                        :alt="$product->name"
                                        :category="$product->category?->name" />
                                </a>
                                <div class="product-image-cart-form">
                                    <button
                                        type="button"
                                        class="product-cart-btn {{ $isAvailableAtCurrentBranch ? '' : 'disabled' }}"
                                        aria-label="Chọn size và thêm {{ $product->name }}"
                                        data-quick-add
                                        data-action="{{ route('cart.add', $product->id) }}"
                                        data-name="{{ $product->name }}"
                                        data-price="{{ number_format($product->price ?? 0, 0, ',', '.') }}đ"
                                        data-base-price="{{ (int) ($product->price ?? 0) }}"
                                        data-sizes='@json($product->relationLoaded("sizes") ? $product->sizes->pluck("pivot.price", "name") : [])'
                                        data-toppings='@json($product->relationLoaded("toppings") ? $product->toppings->map(fn($t) => ["name" => $t->name, "price" => (int) $t->price]) : [])'
                                        data-image="{{ $product->image_url }}"
                                        data-category="{{ $product->category?->name }}"
                                        data-product-availability="{{ $product->id }}"
                                        data-branch-id="{{ $branch?->id }}"
                                        data-product-action
                                        @disabled(! $isAvailableAtCurrentBranch)>
                                        <i class="bi {{ $isAvailableAtCurrentBranch ? 'bi-cart-plus' : 'bi-cart-x' }}" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="home-product__body">
                                <x-product-availability-badge :product="$product" :branch="$branch" class="mb-2" />
                                <div class="home-product__rating">
                                    @if($reviewCount > 0)
                                    @for($star = 1; $star <= 5; $star++)
                                        <i class="bi {{ $rating >= $star ? 'bi-star-fill' : ($rating >= $star - 0.5 ? 'bi-star-half' : 'bi-star') }}"></i>
                                        @endfor
                                        <span>({{ number_format($rating, 1) }} · {{ $reviewCount }})</span>
                                        @else
                                        <i class="bi bi-star text-secondary"></i>
                                        <span>Chưa có đánh giá</span>
                                        @endif
                                </div>
                                <h3 class="home-product__name">
                                    <a href="{{ route('products.show', $product->slug) }}">{{ $product->name }}</a>
                                </h3>
                                <div class="home-product__footer">
                                    <span class="home-product__price">{{ number_format($product->price ?? 0, 0, ',', '.') }}đ</span>
                                </div>
                            </div>
                        </article>
                    </div>
                    @empty
                    @if(($demoProducts ?? collect())->isNotEmpty())
                    @foreach($demoProducts->map(fn ($item) => [
                    $item['name'],
                    $item['description'],
                    number_format($item['price'], 0, ',', '.') . 'đ',
                    $item['image'],
                    $item['category'] === request('category') ? 'Đang chọn' : '',
                    $item['slug'],
                    ]) as $item)
                    <div class="col-sm-6 col-xl-4">
                        <article class="home-product">
                            <div class="home-product__img">
                                @if($item[4])
                                <span class="home-product__tag">{{ $item[4] }}</span>
                                @endif
                                <a href="{{ isset($item[5]) ? route('products.show', $item[5]) : route('products.index') }}">
                                    <img src="{{ $item[3] }}" alt="{{ $item[0] }}">
                                </a>
                                <div class="product-image-cart-form">
                                    <button
                                        type="button"
                                        class="product-cart-btn"
                                        aria-label="Chọn size và thêm {{ $item[0] }}"
                                        data-quick-add
                                        data-action="{{ route('cart.add', 'demo-' . $item[5]) }}"
                                        data-name="{{ $item[0] }}"
                                        data-price="{{ $item[2] }}"
                                        data-base-price="{{ (int) preg_replace('/\D/', '', $item[2]) }}"
                                        data-image="{{ $item[3] }}">
                                        <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="home-product__body">
                                <div class="home-product__rating">
                                    <i class="bi bi-star text-secondary"></i>
                                    <span>Chưa có đánh giá</span>
                                </div>
                                <h3 class="home-product__name">
                                    <a href="{{ isset($item[5]) ? route('products.show', $item[5]) : route('products.index') }}">{{ $item[0] }}</a>
                                </h3>
                                <div class="home-product__footer">
                                    <span class="home-product__price">{{ $item[2] }}</span>
                                </div>
                            </div>
                        </article>
                    </div>
                    @endforeach
                    @else
                    <div class="col-12">
                        <div class="shop-empty-state text-center p-5">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width:52px;height:52px;background:var(--drink-primary-soft);color:var(--drink-primary);">
                                <i class="bi bi-cup-straw fs-4"></i>
                            </span>
                            <h2 class="h4 fw-bold mb-2">Chưa có sản phẩm trong mục này</h2>
                            <p class="text-secondary mb-4">Bạn chọn danh mục khác hoặc quay lại tất cả đồ uống nhé.</p>
                            <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">Xem tất cả</a>
                        </div>
                    </div>
                    @endif
                    @endforelse
                </div>

                @if($products->count() > 0)
                <div class="mt-4">
                    {{ $products->links('pagination::bootstrap-5') }}
                </div>
                @endif
            </div>
        </div>
    </div>
</section>

<div class="modal fade quick-add-modal drink-customizer" id="quickAddModal" tabindex="-1" aria-labelledby="quickAddTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="quickAddForm" class="drink-customizer__form" method="POST" data-ajax-cart>
                @csrf
                <input type="hidden" name="size" value="S" data-quick-size-input>
                <input type="hidden" name="sugar_level" value="50" data-quick-sugar-input>
                <input type="hidden" name="ice_level" value="100" data-quick-ice-input>
                <input type="hidden" name="toppings" value="[]" data-quick-toppings-input>
                <input type="hidden" name="quantity" value="1">

                <div class="modal-header drink-customizer__header border-0 pb-1">
                    <h2 class="modal-title drink-customizer__title" id="quickAddTitle">Tùy chọn đồ uống</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>

                <div class="modal-body drink-customizer__body">
                    <div class="quick-product-summary drink-customizer__summary">
                        <img src="{{ $uiPlaceholderImage('Sản phẩm', 'Đồ uống') }}" alt="Ảnh sản phẩm" class="quick-add-thumb drink-customizer__thumb" data-quick-image>
                        <div>
                            <div class="fw-bold fs-5" data-quick-name></div>
                            <div class="text-primary fw-bold fs-5" data-quick-price></div>
                        </div>
                    </div>

                    <div class="quick-section drink-customizer__section">
                        <div class="quick-section-label drink-customizer__section-title"><i class="bi bi-cup-straw"></i> Chọn kích cỡ</div>
                        <div class="quick-size-grid drink-customizer__sizes" data-quick-group="size">
                            <button type="button" class="quick-choice drink-customizer__choice drink-customizer__size active" data-value="S" data-extra-price="0">S<small class="d-block text-secondary">Giá gốc</small></button>
                            <button type="button" class="quick-choice drink-customizer__choice drink-customizer__size" data-value="M" data-extra-price="5000">M<small class="d-block text-secondary">+5.000đ</small></button>
                            <button type="button" class="quick-choice drink-customizer__choice drink-customizer__size" data-value="L" data-extra-price="10000">L<small class="d-block text-secondary">+10.000đ</small></button>
                        </div>
                    </div>

                    <div class="quick-section quick-levels-grid drink-customizer__section drink-customizer__levels">
                        <div>
                            <div class="quick-section-label drink-customizer__section-title"><i class="bi bi-droplet"></i> Mức đường</div>
                            <div class="quick-sugar-grid drink-customizer__sugar" data-quick-group="sugar">
                                <button type="button" class="quick-choice drink-customizer__choice" data-value="0">0%</button>
                                <button type="button" class="quick-choice drink-customizer__choice" data-value="30">30%</button>
                                <button type="button" class="quick-choice drink-customizer__choice" data-value="50">50%</button>
                                <button type="button" class="quick-choice drink-customizer__choice" data-value="70">70%</button>
                                <button type="button" class="quick-choice drink-customizer__choice active" data-value="100" title="100% Tiêu chuẩn">100%</button>
                            </div>
                        </div>
                        <div>
                            <div class="quick-section-label drink-customizer__section-title"><i class="bi bi-snow"></i> Mức đá</div>
                            <div class="quick-ice-grid drink-customizer__ice" data-quick-group="ice">
                                <button type="button" class="quick-choice drink-customizer__choice" data-value="0">Không đá</button>
                                <button type="button" class="quick-choice drink-customizer__choice" data-value="50">Ít đá</button>
                                <button type="button" class="quick-choice drink-customizer__choice active" data-value="100" title="100% Tiêu chuẩn">100%</button>
                            </div>
                        </div>
                    </div>

                    <div class="quick-section drink-customizer__section">
                        <div class="quick-section-label drink-customizer__section-title"><i class="bi bi-plus-circle"></i> Thêm món kèm <small>(tối đa 3 món)</small></div>
                        <div class="quick-topping-grid drink-customizer__toppings" data-quick-topping-group></div>
                    </div>

                </div>

                <div class="quick-actions quick-actions-footer drink-customizer__footer">
                    <div class="quick-quantity drink-customizer__quantity" aria-label="Số lượng">
                        <button type="button" data-quick-qty-minus aria-label="Giảm số lượng">−</button>
                        <span class="fw-bold fs-5" data-quick-qty-display>1</span>
                        <button type="button" data-quick-qty-plus aria-label="Tăng số lượng">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary rounded-pill fw-bold quick-submit drink-customizer__submit">
                        Thêm vào giỏ · <span data-quick-total>0đ</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.filter-panel').forEach((panel) => {
            const toggle = panel.querySelector('.filter-panel__mobile-toggle');
            if (!toggle) return;

            toggle.addEventListener('click', () => {
                const isOpen = panel.classList.toggle('is-open');
                toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            });
        });

        document.querySelectorAll('[data-favorite-form]').forEach((favoriteForm) => {
            favoriteForm.addEventListener('submit', async (event) => {
                event.preventDefault();
                const favoriteButton = favoriteForm.querySelector('[data-favorite-button]');
                if (!favoriteButton || favoriteButton.classList.contains('is-loading')) return;
                favoriteButton.classList.add('is-loading');

                try {
                    const response = await fetch(favoriteForm.action, {
                        method: 'POST',
                        body: new FormData(favoriteForm),
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'same-origin',
                    });
                    if (!response.ok) throw new Error('favorite_failed');
                    const result = await response.json();
                    favoriteButton.classList.toggle('is-active', result.favorited);
                    favoriteButton.setAttribute('aria-pressed', result.favorited ? 'true' : 'false');
                    favoriteButton.setAttribute('aria-label', result.favorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích');
                    favoriteButton.title = result.favorited ? 'Bỏ yêu thích' : 'Yêu thích';
                    favoriteButton.querySelector('i').className = 'bi ' + (result.favorited ? 'bi-heart-fill' : 'bi-heart');
                } catch (error) {
                    favoriteForm.submit();
                } finally {
                    favoriteButton.classList.remove('is-loading');
                }
            });
        });

        document.querySelectorAll('[data-sort-dropdown]').forEach((dropdown) => {
            const form = dropdown.closest('form');
            const input = form?.querySelector('[data-sort-input]');
            const label = dropdown.querySelector('[data-sort-label]');
            const toggle = dropdown.querySelector('.sort-dropdown-toggle');
            const options = dropdown.querySelectorAll('.sort-dropdown-option');

            options.forEach((item) => item.classList.remove('d-none'));

            toggle?.addEventListener('click', (event) => {
                event.stopPropagation();
                options.forEach((item) => item.classList.remove('d-none'));
                document.querySelectorAll('.sort-dropdown.open').forEach((item) => {
                    if (item !== dropdown) {
                        item.classList.remove('open');
                        item.querySelector('.sort-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
                    }
                });
                dropdown.classList.toggle('open');
                toggle.setAttribute('aria-expanded', dropdown.classList.contains('open') ? 'true' : 'false');
            });

            options.forEach((option) => {
                option.addEventListener('click', () => {
                    options.forEach((item) => item.classList.remove('active'));
                    option.classList.add('active');
                    if (input) {
                        input.value = option.dataset.sortValue || '';
                    }
                    if (label) {
                        label.textContent = option.textContent.trim();
                    }
                    dropdown.classList.remove('open');
                    toggle?.setAttribute('aria-expanded', 'false');
                    form?.submit();
                });
            });
        });

        document.addEventListener('click', (event) => {
            if (!event.target.closest('[data-sort-dropdown]')) {
                document.querySelectorAll('.sort-dropdown.open').forEach((item) => {
                    item.classList.remove('open');
                    item.querySelector('.sort-dropdown-toggle')?.setAttribute('aria-expanded', 'false');
                });
            }
        });

        const modalElement = document.getElementById('quickAddModal');
        const form = document.getElementById('quickAddForm');

        if (!modalElement || !form || !window.bootstrap) {
            return;
        }

        const modal = new bootstrap.Modal(modalElement);
        const fields = {
            name: modalElement.querySelector('[data-quick-name]'),
            price: modalElement.querySelector('[data-quick-price]'),
            image: modalElement.querySelector('[data-quick-image]'),
            size: modalElement.querySelector('[data-quick-size-input]'),
            sugar: modalElement.querySelector('[data-quick-sugar-input]'),
            ice: modalElement.querySelector('[data-quick-ice-input]'),
            toppings: modalElement.querySelector('[data-quick-toppings-input]'),
            toppingGroup: modalElement.querySelector('[data-quick-topping-group]'),
            qtyInput: modalElement.querySelector('input[name="quantity"]'),
            qtyDisplay: modalElement.querySelector('[data-quick-qty-display]'),
            qtyMinus: modalElement.querySelector('[data-quick-qty-minus]'),
            qtyPlus: modalElement.querySelector('[data-quick-qty-plus]'),
            total: modalElement.querySelector('[data-quick-total]'),
        };
        let currentBasePrice = 0;

        function updateQuickTotal() {
            const sizeExtra = Number(modalElement.querySelector('[data-quick-group="size"] .quick-choice.active')?.dataset.extraPrice || 0);
            const toppingTotal = Array.from(fields.toppingGroup?.querySelectorAll('.quick-topping-choice.active') || [])
                .reduce((sum, button) => sum + Number(button.dataset.toppingPrice || 0), 0);
            const quantity = Math.max(1, Number(fields.qtyInput?.value || 1));
            const total = (currentBasePrice + sizeExtra + toppingTotal) * quantity;
            if (fields.total) fields.total.textContent = `${total.toLocaleString('vi-VN')}đ`;
        }

        function setGroupValue(group, value) {
            modalElement.querySelectorAll(`[data-quick-group="${group}"] .quick-choice`).forEach((button) => {
                button.classList.toggle('active', button.dataset.value === value);
            });
        }

        function normalizeText(value) {
            return String(value || '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd');
        }

        function toppingOptionsFor(name, category) {
            const text = normalizeText(`${name} ${category}`);

            if (text.includes('matcha')) {
                return [
                    ['Trân châu đen', 5000],
                    ['Kem cheese', 7000],
                    ['Thạch matcha', 6000]
                ];
            }

            if (text.includes('tra sua')) {
                return [
                    ['Trân châu đen', 5000],
                    ['Pudding trứng', 7000],
                    ['Thạch phô mai', 8000]
                ];
            }

            if (text.includes('ca phe')) {
                return [
                    ['Kem mặn', 7000],
                    ['Shot espresso', 10000],
                    ['Caramel', 6000]
                ];
            }

            if (text.includes('sinh to')) {
                return [
                    ['Hạt chia', 5000],
                    ['Sữa chua', 7000],
                    ['Nha đam', 6000]
                ];
            }

            if (text.includes('nuoc ep')) {
                return [
                    ['Nha đam', 6000],
                    ['Hạt chia', 5000],
                    ['Soda', 7000]
                ];
            }

            if (text.includes('soda')) {
                return [
                    ['Thạch trái cây', 6000],
                    ['Nha đam', 6000],
                    ['Trân châu trắng', 7000]
                ];
            }

            return [
                ['Trân châu trắng', 7000],
                ['Thạch nha đam', 6000],
                ['Kem cheese', 7000]
            ];
        }

        function syncQuickToppings() {
            const toppings = Array.from(fields.toppingGroup?.querySelectorAll('.quick-topping-choice.active') || []).map((button) => ({
                name: button.dataset.toppingName || '',
                price: Number(button.dataset.toppingPrice || 0),
            }));

            if (fields.toppings) {
                fields.toppings.value = JSON.stringify(toppings);
            }
            updateQuickTotal();
        }

        function renderQuickToppings(name, category, customToppings = []) {
            if (!fields.toppingGroup) {
                return;
            }

            let options = [];
            if (Array.isArray(customToppings) && customToppings.length > 0) {
                options = customToppings.map(t => [t.name, Number(t.price || 0)]);
            } else {
                options = toppingOptionsFor(name, category);
            }

            fields.toppingGroup.replaceChildren(...options.map(([toppingName, price]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'quick-choice quick-topping-choice drink-customizer__choice drink-customizer__topping';
                button.dataset.toppingName = String(toppingName || 'Món kèm');
                button.dataset.toppingPrice = String(Number(price || 0));
                button.setAttribute('aria-pressed', 'false');
                button.append(document.createTextNode(button.dataset.toppingName));

                const priceLabel = document.createElement('small');
                priceLabel.textContent = `+${Number(price || 0).toLocaleString('vi-VN')}đ`;
                button.append(priceLabel);

                return button;
            }));

            syncQuickToppings();
        }

        document.querySelectorAll('[data-quick-add]').forEach((button) => {
            button.addEventListener('click', () => {
                form.action = button.dataset.action || '#';
                fields.name.textContent = button.dataset.name || 'Đồ uống';
                fields.price.textContent = button.dataset.price || '';
                fields.image.src = button.dataset.image || '';
                fields.image.alt = button.dataset.name || 'Đồ uống';
                currentBasePrice = Number(button.dataset.basePrice || 0);
                fields.size.value = 'S';
                fields.sugar.value = '50';
                fields.ice.value = '100';
                if (fields.qtyInput) fields.qtyInput.value = '1';
                if (fields.qtyDisplay) fields.qtyDisplay.textContent = '1';

                let sizesMap = {};
                try {
                    sizesMap = JSON.parse(button.dataset.sizes || '{}');
                } catch (e) {}

                modalElement.querySelectorAll('.quick-size-grid .quick-choice').forEach((sizeBtn) => {
                    const sz = sizeBtn.dataset.value;
                    if (sz === 'S') {
                        sizeBtn.dataset.extraPrice = '0';
                        const small = sizeBtn.querySelector('small');
                        if (small) small.textContent = 'Giá gốc';
                        return;
                    }

                    const fallbackExtra = sz === 'M' ? 5000 : 10000;
                    const rawPrice = sizesMap[sz] !== undefined ? Number(sizesMap[sz]) : null;
                    const extraPrice = Number.isFinite(rawPrice) ?
                        (rawPrice >= currentBasePrice ? Math.max(0, rawPrice - currentBasePrice) : Math.max(0, rawPrice)) :
                        fallbackExtra;

                    sizeBtn.dataset.extraPrice = String(extraPrice);
                    const small = sizeBtn.querySelector('small');
                    if (small) {
                        small.textContent = '+' + extraPrice.toLocaleString('vi-VN') + 'đ';
                    }
                });

                setGroupValue('size', 'S');
                setGroupValue('sugar', '50');
                setGroupValue('ice', '100');

                let toppingsList = [];
                try {
                    toppingsList = JSON.parse(button.dataset.toppings || '[]');
                } catch (e) {}

                renderQuickToppings(button.dataset.name || '', button.dataset.category || '', toppingsList);
                updateQuickTotal();
                modal.show();
            });
        });

        modalElement.querySelectorAll('[data-quick-group]').forEach((group) => {
            group.addEventListener('click', (event) => {
                const button = event.target.closest('.quick-choice');

                if (!button) {
                    return;
                }

                group.querySelectorAll('.quick-choice').forEach((item) => item.classList.remove('active'));
                button.classList.add('active');

                if (group.dataset.quickGroup === 'size') {
                    fields.size.value = button.dataset.value;
                }

                if (group.dataset.quickGroup === 'sugar') {
                    fields.sugar.value = button.dataset.value;
                }

                if (group.dataset.quickGroup === 'ice') {
                    fields.ice.value = button.dataset.value;
                }
                updateQuickTotal();
            });
        });

        if (fields.toppingGroup) {
            fields.toppingGroup.addEventListener('click', (event) => {
                const button = event.target.closest('.quick-topping-choice');
                if (!button) return;

                const isAlreadyActive = button.classList.contains('active');
                const activeCount = fields.toppingGroup.querySelectorAll('.quick-topping-choice.active').length;

                if (!isAlreadyActive && activeCount >= 3) {
                    if (typeof window.showToast === 'function') {
                        window.showToast('Mỗi ly tối đa 3 topping để đảm bảo hương vị và dung tích ly.', 'warning');
                    } else if (typeof showToast === 'function') {
                        showToast('Mỗi ly tối đa 3 topping để đảm bảo hương vị và dung tích ly.', 'warning');
                    } else {
                        alert('Mỗi ly tối đa 3 topping để đảm bảo hương vị và dung tích ly.');
                    }
                    return;
                }

                button.classList.toggle('active');
                button.setAttribute('aria-pressed', button.classList.contains('active') ? 'true' : 'false');
                syncQuickToppings();
            });
        }

        if (fields.qtyMinus && fields.qtyPlus && fields.qtyInput && fields.qtyDisplay) {
            fields.qtyMinus.addEventListener('click', () => {
                let val = parseInt(fields.qtyInput.value) || 1;
                if (val > 1) {
                    val--;
                    fields.qtyInput.value = val;
                    fields.qtyDisplay.textContent = val;
                    updateQuickTotal();
                }
            });
            fields.qtyPlus.addEventListener('click', () => {
                let val = parseInt(fields.qtyInput.value) || 1;
                val++;
                fields.qtyInput.value = val;
                fields.qtyDisplay.textContent = val;
                updateQuickTotal();
            });
        }

        document.addEventListener('cart:updated', () => {
            if (modalElement.classList.contains('show')) {
                modal.hide();
            }
        });

        document.querySelectorAll('[data-receive-code]').forEach((button) => {
            button.addEventListener('click', async () => {
                const code = button.dataset.receiveCode;
                const guestIdentifier = sessionStorage.getItem('guest_identifier') || null;

                try {
                    const response = await fetch('/api/vouchers/receive', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        },
                        body: JSON.stringify({
                            voucher_code: code,
                            guest_identifier: guestIdentifier,
                        }),
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        // Show error message with details
                        showToast(data.message || 'Lỗi khi nhận voucher', 'error');
                        console.error('Voucher error:', data);
                        return;
                    }

                    // Save guest identifier if this is first voucher claim
                    if (!guestIdentifier && data.guest_identifier) {
                        sessionStorage.setItem('guest_identifier', data.guest_identifier);
                    }

                    // Show success message
                    button.textContent = 'ĐÃ NHẬN';
                    button.setAttribute('disabled', 'true');
                    button.classList.add('btn-success');
                    button.classList.remove('btn-primary');

                    // Show toast notification
                    showToast(`Nhận voucher thành công: ${data.voucher.code}`, 'success');

                } catch (err) {
                    console.error('Exception when receiving voucher:', err);
                    showToast('Lỗi khi nhận voucher. Vui lòng thử lại.', 'error');
                }
            });
        });

        // Toast notification helper
        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: ${type === 'success' ? '#10b981' : '#3b82f6'};
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                z-index: 9999;
                animation: slideIn 0.3s ease;
            `;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 3000);
        }

        // Price range slider functionality with auto-submit
        const priceRange = document.getElementById('priceRange');
        const minPriceInput = document.getElementById('minPriceInput');
        const maxPriceInput = document.getElementById('maxPriceInput');
        const minPriceLabel = document.getElementById('minPriceLabel');
        const maxPriceLabel = document.getElementById('maxPriceLabel');
        const priceFilterForm = document.getElementById('priceFilterForm');

        if (priceRange && minPriceInput && maxPriceInput && minPriceLabel && maxPriceLabel && priceFilterForm) {
            let debounceTimer;

            priceRange.addEventListener('input', function() {
                const value = parseInt(this.value);
                maxPriceInput.value = value;
                maxPriceLabel.textContent = value.toLocaleString('vi-VN') + 'đ';

                // Clear previous timer
                clearTimeout(debounceTimer);

                // Auto-submit after 500ms of inactivity
                debounceTimer = setTimeout(() => {
                    priceFilterForm.submit();
                }, 500);
            });
        }
    });
</script>
@endsection
