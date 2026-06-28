@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    /* ─── Home Page ─── */
    .home-page {
        --home-section-py: clamp(4rem, 8vw, 6.5rem);
        --home-radius: 20px;
        --home-shadow: 0 4px 24px rgba(17, 24, 39, 0.06);
        --home-shadow-hover: 0 20px 48px rgba(13, 147, 115, 0.12);
        background: var(--c-bg);
    }

    .home-page section {
        position: relative;
    }

    /* ─── Trust Strip ─── */
    .home-trust {
        margin-top: -3.5rem;
        padding: 0 0 1rem;
        position: relative;
        z-index: 10;
    }

    .home-trust__inner {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0;
        background: var(--c-surface);
        border-radius: var(--home-radius);
        border: 1px solid var(--c-border);
        box-shadow: var(--shadow-xl);
        overflow: hidden;
    }

    .home-trust__item {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1.35rem 1.5rem;
        border-right: 1px solid var(--c-border-light);
        transition: background 0.25s ease;
    }

    .home-trust__item:last-child { border-right: 0; }
    .home-trust__item:hover { background: var(--c-primary-light); }

    .home-trust__icon {
        flex-shrink: 0;
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 14px;
        background: var(--c-primary-light);
        color: var(--c-primary);
        font-size: 1.25rem;
    }

    .home-trust__value {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--c-ink);
        letter-spacing: -0.02em;
        line-height: 1.2;
    }

    .home-trust__label {
        font-size: 0.8125rem;
        color: var(--c-muted);
        margin: 0;
        line-height: 1.4;
    }

    /* ─── Discover Section ─── */
    .home-discover {
        padding: clamp(2.75rem, 6vw, 4.75rem) 0 0;
        background:
            linear-gradient(180deg, rgba(247, 250, 252, 0.96) 0%, var(--c-bg) 100%);
    }

    .home-discover__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1.25rem;
        margin-bottom: 1.65rem;
    }

    .home-discover__top .section-title {
        margin-bottom: 0.35rem;
    }

    .home-discover__types {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .home-discover__type {
        position: relative;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        justify-content: center;
        padding: 1.1rem 1.15rem 1.15rem;
        min-height: 138px;
        text-align: left;
        border-radius: 18px;
        border: 1px solid rgba(13, 147, 115, 0.13);
        background: linear-gradient(180deg, #ffffff 0%, #f8fffd 100%);
        color: var(--c-ink);
        text-decoration: none;
        box-shadow: 0 12px 30px rgba(15, 78, 62, 0.06);
        overflow: hidden;
        transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease, background 0.25s ease;
    }

    .home-discover__type::after {
        content: "\F138";
        position: absolute;
        right: 1rem;
        top: 1rem;
        font-family: "bootstrap-icons";
        color: rgba(13, 147, 115, 0.35);
        font-size: 1rem;
        transition: transform 0.25s ease, color 0.25s ease;
    }

    .home-discover__type:hover {
        transform: translateY(-4px);
        border-color: rgba(13, 147, 115, 0.34);
        background: #ffffff;
        box-shadow: var(--home-shadow-hover);
    }

    .home-discover__type:hover::after {
        color: var(--c-primary);
        transform: translateX(3px);
    }

    .home-discover__type-icon {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 46px;
        height: 46px;
        margin-bottom: 0.85rem;
        border-radius: 14px;
        background: rgba(13, 147, 115, 0.12);
        color: var(--c-primary);
        font-size: 1.25rem;
    }

    .home-discover__type-title {
        margin: 0 0 0.25rem;
        font-size: 1.05rem;
        font-weight: 800;
        letter-spacing: -0.02em;
    }

    .home-discover__type p {
        margin: 0;
        color: var(--c-muted);
        font-size: 0.86rem;
        line-height: 1.5;
    }

    .home-discover__products {
        margin-top: 1.2rem;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 1rem;
    }

    .home-discover-card {
        display: flex;
        flex-direction: column;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid rgba(13, 147, 115, 0.13);
        background: var(--c-surface);
        box-shadow: 0 14px 34px rgba(15, 78, 62, 0.07);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        text-decoration: none;
        color: inherit;
    }

    .home-discover-card:hover {
        transform: translateY(-5px);
        border-color: rgba(13, 147, 115, 0.3);
        box-shadow: var(--home-shadow-hover);
    }

    .home-discover-card__media {
        position: relative;
        aspect-ratio: 4 / 3;
        overflow: hidden;
        background: linear-gradient(145deg, #effbf7, #ffffff);
    }

    .home-discover-card__media img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        padding: 0.35rem;
        transition: transform 0.6s ease;
    }

    .home-discover-card:hover .home-discover-card__media img {
        transform: scale(1.05);
    }

    .home-discover-card__body {
        display: grid;
        gap: 0.65rem;
        padding: 0.95rem 1rem 1rem;
    }

    .home-discover-card__tag {
        position: absolute;
        left: 0.8rem;
        top: 0.8rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: fit-content;
        padding: 0.28rem 0.7rem;
        margin-bottom: 0;
        border-radius: 999px;
        font-size: 0.68rem;
        font-weight: 800;
        color: var(--c-primary);
        background: rgba(255, 255, 255, 0.92);
        box-shadow: 0 8px 20px rgba(15, 78, 62, 0.08);
    }

    .home-discover-card__title {
        font-size: 1rem;
        font-weight: 800;
        margin: 0;
        line-height: 1.35;
    }

    .home-discover-card__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .home-discover-card__price {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--c-primary);
    }

    .home-discover-card__button {
        color: var(--c-primary);
        text-decoration: none;
        font-size: 0.82rem;
        font-weight: 800;
        white-space: nowrap;
        transition: transform 0.25s ease;
    }

    .home-discover-card:hover .home-discover-card__button {
        transform: translateX(3px);
    }

    .home-discover__banner-grid {
        display: grid;
        grid-template-columns: 1.75fr 1fr;
        gap: 1rem;
        margin-top: 1.75rem;
    }

    .home-discover__banner {
        display: flex;
        flex-direction: column;
        justify-content: center;
        min-height: 220px;
        padding: 2rem;
        border-radius: var(--home-radius);
        color: #fff;
        position: relative;
        overflow: hidden;
    }

    .home-discover__banner--promo {
        background: linear-gradient(135deg, #22b573 0%, #2dd78e 100%);
    }

    .home-discover__banner--wellness {
        background: linear-gradient(135deg, #ff9444 0%, #ff6b00 100%);
    }

    .home-discover__banner-title {
        margin: 0 0 0.75rem;
        font-size: clamp(1.6rem, 2.8vw, 2.4rem);
        line-height: 1.05;
        font-weight: 800;
    }

    .home-discover__banner-text {
        margin: 0 0 1.25rem;
        max-width: 18rem;
        line-height: 1.6;
    }

    .home-discover__banner-button {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.85rem 1.5rem;
        border-radius: 999px;
        border: 1px solid rgba(255, 255, 255, 0.22);
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        text-decoration: none;
        font-weight: 700;
        transition: background 0.25s ease, transform 0.25s ease;
    }

    .home-discover__banner-button:hover {
        background: rgba(255, 255, 255, 0.28);
        transform: translateY(-1px);
    }

    @media (max-width: 1199.98px) {
        .home-discover__banner-grid { grid-template-columns: 1fr 1fr; }
    }

    @media (max-width: 991.98px) {
        .home-discover__types,
        .home-discover__products,
        .home-discover__banner-grid {
            grid-template-columns: 1fr;
        }

        .home-discover__top {
            flex-direction: column;
            align-items: flex-start;
        }
    }

    @media (max-width: 767.98px) {
        .home-discover { padding-top: 2rem; }
        .home-discover__banner { min-height: 180px; }
    }

    /* ─── Section Header ─── */
    .home-section-head {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1.5rem;
        margin-bottom: 2.75rem;
    }

    .home-section-head--center {
        flex-direction: column;
        align-items: center;
        text-align: center;
        max-width: 640px;
        margin-left: auto;
        margin-right: auto;
    }

    .home-section-head__desc {
        color: var(--c-muted);
        font-size: 1.05rem;
        max-width: 520px;
        margin: 0.75rem 0 0;
        line-height: 1.65;
    }

    .home-link-arrow {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 600;
        font-size: 0.9375rem;
        color: var(--c-primary);
        text-decoration: none;
        white-space: nowrap;
        transition: gap 0.2s ease;
    }

    .home-link-arrow:hover {
        color: var(--c-primary-dark);
        gap: 0.75rem;
    }

    /* ─── Categories ─── */
    .home-categories {
        padding: var(--home-section-py) 0;
        background: linear-gradient(180deg, var(--c-bg) 0%, #fff 50%, var(--c-bg) 100%);
    }

    .home-cat-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.25rem;
    }

    .home-cat-card {
        position: relative;
        display: flex;
        flex-direction: column;
        border-radius: var(--home-radius);
        overflow: hidden;
        text-decoration: none;
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        box-shadow: var(--home-shadow);
        transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.35s ease, border-color 0.35s ease;
    }

    .home-cat-card:hover {
        transform: translateY(-6px);
        border-color: rgba(13, 147, 115, 0.35);
        box-shadow: var(--home-shadow-hover);
    }

    .home-cat-card__media {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        background: var(--c-bg-warm);
    }

    .home-cat-card__media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .home-cat-card:hover .home-cat-card__media img {
        transform: scale(1.06);
    }

    .home-cat-card__badge {
        position: absolute;
        top: 0.875rem;
        right: 0.875rem;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.92);
        color: var(--c-primary);
        font-size: 0.875rem;
        box-shadow: var(--shadow-sm);
        transition: background 0.25s ease, color 0.25s ease, transform 0.25s ease;
    }

    .home-cat-card:hover .home-cat-card__badge {
        background: var(--c-primary);
        color: #fff;
        transform: rotate(-45deg);
    }

    .home-cat-card__body {
        padding: 1.125rem 1.25rem 1.25rem;
    }

    .home-cat-card__title {
        font-size: 1.0625rem;
        font-weight: 700;
        color: var(--c-ink);
        margin: 0 0 0.25rem;
        letter-spacing: -0.02em;
    }

    .home-cat-card__meta {
        font-size: 0.8125rem;
        color: var(--c-muted);
        margin: 0;
    }

    /* ─── Featured Products ─── */
    .home-featured {
        padding: var(--home-section-py) 0;
        background: #fff;
    }

    .home-product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }

    .home-product {
        display: flex;
        flex-direction: column;
        border-radius: var(--home-radius);
        overflow: hidden;
        background: var(--c-surface);
        border: 1px solid var(--c-border);
        box-shadow: var(--home-shadow);
        transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
    }

    .home-product:hover {
        transform: translateY(-5px);
        border-color: rgba(13, 147, 115, 0.3);
        box-shadow: var(--home-shadow-hover);
    }

    .home-product__img {
        position: relative;
        aspect-ratio: 1;
        overflow: hidden;
        background: linear-gradient(145deg, var(--c-bg-warm), #fff);
    }

    .home-product__img img,
    .home-product__img .product-image {
        width: 100%;
        height: 100%;
        object-fit: cover;
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
        background: var(--c-primary);
        color: #ffffff;
        box-shadow: 0 18px 36px rgba(13, 147, 115, 0.28);
        pointer-events: auto;
        transform: translateY(8px) scale(0.94);
        transition: transform 0.22s ease, background 0.22s ease, box-shadow 0.22s ease;
    }

    .home-product .product-cart-btn i {
        font-size: 1.25rem;
        line-height: 1;
    }

    .home-product__img:hover .product-cart-btn,
    .home-product__img:focus-within .product-cart-btn {
        transform: translateY(0) scale(1);
    }

    .home-product .product-cart-btn:hover {
        background: var(--c-primary-dark);
        box-shadow: 0 20px 40px rgba(13, 147, 115, 0.34);
    }

    .home-product__tag {
        position: absolute;
        top: 0.875rem;
        left: 0.875rem;
        padding: 0.3rem 0.75rem;
        border-radius: var(--radius-full);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(8px);
        color: var(--c-primary-dark);
        font-size: 0.6875rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        box-shadow: var(--shadow-sm);
        z-index: 2;
    }

    .home-product__body {
        display: flex;
        flex-direction: column;
        flex: 1;
        padding: 1.25rem;
    }

    .home-product__rating {
        display: flex;
        align-items: center;
        gap: 3px;
        margin-bottom: 0.5rem;
        font-size: 0.75rem;
        color: #F59E0B;
    }

    .home-product__rating span {
        color: var(--c-muted);
        margin-left: 4px;
    }

    .home-product__name {
        font-size: 1rem;
        font-weight: 700;
        margin: 0 0 0.25rem;
        letter-spacing: -0.02em;
        line-height: 1.35;
    }

    .home-product__name a {
        color: var(--c-ink);
        text-decoration: none;
        transition: color 0.2s ease;
    }

    .home-product__name a:hover { color: var(--c-primary); }

    .home-product__sku {
        font-size: 0.75rem;
        color: var(--c-subtle);
        font-family: ui-monospace, monospace;
        margin: 0 0 1rem;
    }

    .home-product__footer {
        display: flex;
        align-items: center;
        justify-content: center;
        margin-top: auto;
        padding-top: 0.75rem;
        border-top: 1px solid var(--c-border-light);
        text-align: center;
    }

    .home-product__price {
        font-size: 1.125rem;
        font-weight: 800;
        color: var(--c-primary);
        letter-spacing: -0.02em;
    }

    .home-featured__cta {
        text-align: center;
        margin-top: 3rem;
    }

    /* ─── Brand Story ─── */
    .home-story {
        padding: var(--home-section-py) 0;
        background: var(--c-bg);
    }

    .home-story__grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: center;
    }

    .home-story__visual {
        position: relative;
        border-radius: 28px;
        overflow: hidden;
        aspect-ratio: 4/5;
        box-shadow: var(--shadow-xl);
    }

    .home-story__visual img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .home-story__visual-badge {
        position: absolute;
        bottom: 1.5rem;
        left: 1.5rem;
        right: 1.5rem;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        box-shadow: var(--shadow-lg);
    }

    .home-story__visual-badge strong {
        display: block;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--c-primary);
        letter-spacing: -0.03em;
    }

    .home-story__visual-badge span {
        font-size: 0.875rem;
        color: var(--c-muted);
    }

    .home-story__content .section-kicker { margin-bottom: 0.75rem; }

    .home-story__content h2 {
        font-size: clamp(1.75rem, 3.5vw, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 1.25rem;
        line-height: 1.15;
    }

    .home-story__content > p {
        font-size: 1.05rem;
        margin-bottom: 2rem;
    }

    .home-story__points {
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
        margin-bottom: 2rem;
    }

    .home-story__point {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
    }

    .home-story__point-icon {
        flex-shrink: 0;
        width: 44px;
        height: 44px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        background: var(--c-primary-light);
        color: var(--c-primary);
        font-size: 1.125rem;
    }

    .home-story__point h4 {
        font-size: 0.9375rem;
        font-weight: 700;
        margin: 0 0 0.25rem;
    }

    .home-story__point p {
        font-size: 0.875rem;
        color: var(--c-muted);
        margin: 0;
        line-height: 1.55;
    }

    /* ─── Why Us ─── */
    .home-why {
        padding: var(--home-section-py) 0;
        background: linear-gradient(160deg, var(--c-primary-dark) 0%, var(--c-primary) 55%, #0a7a62 100%);
        color: #fff;
        overflow: hidden;
    }

    .home-why::before {
        content: '';
        position: absolute;
        inset: 0;
        background:
            radial-gradient(circle at 85% 15%, rgba(255, 255, 255, 0.12) 0%, transparent 40%),
            radial-gradient(circle at 10% 90%, rgba(255, 255, 255, 0.08) 0%, transparent 35%);
        pointer-events: none;
    }

    .home-why .section-kicker { color: rgba(255, 255, 255, 0.75); }
    .home-why .section-title { color: #fff; }

    .home-why__grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
        position: relative;
        z-index: 1;
    }

    .home-why__card {
        padding: 2rem 1.75rem;
        border-radius: var(--home-radius);
        background: rgba(255, 255, 255, 0.1);
        border: 1px solid rgba(255, 255, 255, 0.18);
        backdrop-filter: blur(8px);
        transition: transform 0.3s ease, background 0.3s ease;
    }

    .home-why__card:hover {
        transform: translateY(-4px);
        background: rgba(255, 255, 255, 0.15);
    }

    .home-why__icon {
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.15);
        font-size: 1.5rem;
        margin-bottom: 1.25rem;
    }

    .home-why__card h3 {
        font-size: 1.125rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.75rem;
    }

    .home-why__card p {
        color: rgba(255, 255, 255, 0.78);
        font-size: 0.9375rem;
        margin: 0;
        line-height: 1.6;
    }

    /* ─── Testimonials ─── */
    .home-reviews {
        padding: var(--home-section-py) 0;
        background: #fff;
    }

    .home-reviews__grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 1.5rem;
    }

    .home-review {
        padding: 1.75rem;
        border-radius: var(--home-radius);
        background: var(--c-bg);
        border: 1px solid var(--c-border-light);
        transition: box-shadow 0.3s ease, border-color 0.3s ease;
    }

    .home-review:hover {
        border-color: rgba(13, 147, 115, 0.25);
        box-shadow: var(--home-shadow);
    }

    .home-review__stars {
        color: #F59E0B;
        font-size: 0.875rem;
        margin-bottom: 1rem;
        letter-spacing: 2px;
    }

    .home-review__text {
        font-size: 0.9375rem;
        color: var(--c-ink-secondary);
        line-height: 1.65;
        margin-bottom: 1.25rem;
        font-style: italic;
    }

    .home-review__author {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .home-review__avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: var(--c-primary-light);
        color: var(--c-primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.875rem;
    }

    .home-review__name {
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--c-ink);
        margin: 0;
    }

    .home-review__role {
        font-size: 0.75rem;
        color: var(--c-muted);
        margin: 0;
    }

    /* ─── CTA ─── */
    .home-cta {
        padding: var(--home-section-py) 0 calc(var(--home-section-py) + 1rem);
        background: var(--c-bg);
    }

    .home-cta__card {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        border-radius: 28px;
        overflow: hidden;
        background: var(--c-ink);
        box-shadow: var(--shadow-xl);
        min-height: 380px;
    }

    .home-cta__content {
        padding: clamp(2rem, 5vw, 3.5rem);
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .home-cta__content .section-kicker {
        color: var(--c-accent);
    }

    .home-cta__content h2 {
        color: #fff;
        font-size: clamp(1.5rem, 3vw, 2.25rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 1rem;
        line-height: 1.2;
    }

    .home-cta__content p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.05rem;
        margin-bottom: 2rem;
        max-width: 420px;
    }

    .home-cta__perks {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 2rem;
    }

    .home-cta__perk {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.4rem 0.875rem;
        border-radius: var(--radius-full);
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.12);
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.8125rem;
        font-weight: 600;
    }

    .home-cta__perk i { color: var(--c-accent); }

    .home-cta__visual {
        position: relative;
        min-height: 280px;
    }

    .home-cta__visual img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .home-cta__visual::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, var(--c-ink) 0%, transparent 45%);
    }

    /* ─── Responsive ─── */
    @media (max-width: 1199.98px) {
        .home-product-grid { grid-template-columns: repeat(3, 1fr); }
    }

    @media (max-width: 991.98px) {
        .home-trust__inner { grid-template-columns: repeat(2, 1fr); }
        .home-trust__item:nth-child(2) { border-right: 0; }
        .home-trust__item:nth-child(1),
        .home-trust__item:nth-child(2) { border-bottom: 1px solid var(--c-border-light); }
        .home-cat-grid { grid-template-columns: repeat(2, 1fr); }
        .home-product-grid { grid-template-columns: repeat(2, 1fr); }
        .home-story__grid { grid-template-columns: 1fr; gap: 2.5rem; }
        .home-story__visual { aspect-ratio: 16/10; max-height: 400px; }
        .home-why__grid { grid-template-columns: 1fr; }
        .home-reviews__grid { grid-template-columns: 1fr; }
        .home-cta__card { grid-template-columns: 1fr; }
        .home-cta__visual { min-height: 220px; order: -1; }
        .home-cta__visual::after {
            background: linear-gradient(180deg, transparent 40%, var(--c-ink) 100%);
        }
    }

    @media (max-width: 767.98px) {
        .home-trust { margin-top: -2rem; }
        .home-trust__inner { grid-template-columns: 1fr; }
        .home-trust__item { border-right: 0 !important; border-bottom: 1px solid var(--c-border-light); }
        .home-trust__item:last-child { border-bottom: 0; }
        .home-section-head { flex-direction: column; align-items: flex-start; }
        .home-cat-grid {
            display: flex;
            flex-wrap: nowrap;
            overflow-x: auto;
            gap: 1rem;
            padding-bottom: 0.5rem;
            scroll-snap-type: x mandatory;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .home-cat-grid::-webkit-scrollbar { display: none; }
        .home-cat-grid > * { flex: 0 0 72%; scroll-snap-align: center; }
        .home-product-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="home-page">

    {{-- Slideshow — giữ nguyên --}}
    <x-animated-slider />

    {{-- Trust strip --}}
    <div class="home-trust">
        <div class="container">
            <div class="home-trust__inner">
                <div class="home-trust__item">
                    <div class="home-trust__icon"><i class="bi bi-lightning-charge-fill"></i></div>
                    <div>
                        <div class="home-trust__value">30 phút</div>
                        <p class="home-trust__label">Giao hàng nhanh</p>
                    </div>
                </div>
                <div class="home-trust__item">
                    <div class="home-trust__icon"><i class="bi bi-cup-hot-fill"></i></div>
                    <div>
                        <div class="home-trust__value">100+ món</div>
                        <p class="home-trust__label">Đa dạng thực đơn</p>
                    </div>
                </div>
                <div class="home-trust__item">
                    <div class="home-trust__icon"><i class="bi bi-star-fill"></i></div>
                    <div>
                        <div class="home-trust__value">4.8/5</div>
                        <p class="home-trust__label">Đánh giá khách hàng</p>
                    </div>
                </div>
                <div class="home-trust__item">
                    <div class="home-trust__icon"><i class="bi bi-shield-check"></i></div>
                    <div>
                        <div class="home-trust__value">An toàn</div>
                        <p class="home-trust__label">Thanh toán bảo mật</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Categories --}}
    <section class="home-discover">
        <div class="container">
            <div class="home-discover__top">
                <div>
                    <p class="section-kicker mb-2">Khám Phá Hương Vị</p>
                    <h2 class="section-title h1 mb-0">Đồ uống theo sở thích của bạn</h2>
                    <p class="home-section-head__desc">Trải nghiệm các loại đồ uống tươi mát, từ nước ép trái cây đến cà phê chuẩn vị.</p>
                </div>
                <a href="{{ route('products.index') }}" class="home-link-arrow d-none d-md-inline-flex">
                    Xem toàn bộ menu <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            @php
                $homeDiscoverCategories = [
                    ['title' => 'Juices', 'category_name' => 'Nước Ép', 'icon' => 'bi-droplet', 'description' => 'Trái cây tươi, thanh ngọt, giàu vitamin.'],
                    ['title' => 'Smoothies', 'category_name' => 'Sinh Tố', 'icon' => 'bi-cup-straw', 'description' => 'Mịn màng, lạ miệng, bổ dưỡng mỗi ngày.'],
                    ['title' => 'Teas', 'category_name' => 'Trà Trái Cây', 'icon' => 'bi-cup-hot', 'description' => 'Hương trà thanh nhẹ, thư thái cho mọi khoảnh khắc.'],
                    ['title' => 'Coffee', 'category_name' => 'Cà Phê', 'icon' => 'bi-cup-fill', 'description' => 'Đậm đà, sảng khoái, đánh thức mọi cảm xúc.'],
                ];
            @endphp

            <div class="home-discover__types">
                @foreach($homeDiscoverCategories as $item)
                    @php
                        $discoverCategory = $categories->firstWhere('name', $item['category_name']);
                    @endphp
                    <a href="{{ route('products.index', array_filter(['category' => optional($discoverCategory)->id])) }}" class="home-discover__type">
                        <span class="home-discover__type-icon"><i class="bi {{ $item['icon'] }}"></i></span>
                        <h3 class="home-discover__type-title">{{ $item['title'] }}</h3>
                        <p>{{ $item['description'] }}</p>
                    </a>
                @endforeach
            </div>

            <div class="home-discover__products">
                @foreach([
                    ['Cam Vắt Nguyên Chất', '45.000đ', asset('images/products/nuoc-ep-cam.png'), 'juices', 'cam-vat-nguyen-chat'],
                    ['Green Detox Smoothie', '65.000đ', asset('images/products/sinh-to-bo.png'), 'smoothies', 'green-detox-smoothie'],
                    ['Trà Đào Cam Sả', '50.000đ', asset('images/products/tra-dao-cam-sa.png'), 'teas', 'tra-dao-cam-sa'],
                    ['Cold Brew Trái Cây', '55.000đ', asset('images/products/ca-phe-u-lanh.png'), 'coffee', 'cold-brew-trai-cay'],
                ] as $item)
                    <a href="{{ route('products.show', $item[4]) }}" class="home-discover-card">
                        <div class="home-discover-card__media">
                            <img src="{{ $item[2] }}" alt="{{ $item[0] }}" loading="lazy">
                            <span class="home-discover-card__tag">{{ ucfirst($item[3]) }}</span>
                        </div>
                        <div class="home-discover-card__body">
                            <h3 class="home-discover-card__title">{{ $item[0] }}</h3>
                            <div class="home-discover-card__meta">
                                <span class="home-discover-card__price">{{ $item[1] }}</span>
                                <span class="home-discover-card__button">Xem chi tiết <i class="bi bi-arrow-right"></i></span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="home-discover__banner-grid">
                <div class="home-discover__banner home-discover__banner--promo">
                    <h3 class="home-discover__banner-title">Ưu đãi mùa hè</h3>
                    <p class="home-discover__banner-text">Giảm 20% cho đơn hàng đầu tiên của bạn. Thưởng thức ngay hương vị mát lạnh với giá ưu đãi.</p>
                    <a href="{{ route('products.index') }}" class="home-discover__banner-button">Nhận mã khuyến mãi</a>
                </div>
                <div class="home-discover__banner home-discover__banner--wellness">
                    <h3 class="home-discover__banner-title">Thanh Lọc Cơ Thể</h3>
                    <p class="home-discover__banner-text">Các thức uống detox tự nhiên, giải nhiệt và bù nước nhanh chóng cho ngày năng động.</p>
                    <a href="{{ route('products.index') }}" class="home-discover__banner-button">Xem thực đơn detox</a>
                </div>
            </div>
        </div>
    </section>

    <section class="home-categories">
        <div class="container">
            <div class="home-section-head">
                <div>
                    <p class="section-kicker mb-2">Thực đơn</p>
                    <h2 class="section-title h1 mb-0">Khám phá danh mục</h2>
                </div>
                <a href="{{ route('products.index') }}" class="home-link-arrow d-none d-md-inline-flex">
                    Xem tất cả <i class="bi bi-arrow-right"></i>
                </a>
            </div>

            <div class="home-cat-grid">
                @forelse($categories as $category)
                    <a href="{{ route('products.index', ['category' => $category->id]) }}" class="home-cat-card">
                        <div class="home-cat-card__media">
                            <img src="{{ $uiCategoryImages[$category->name] ?? $uiDefaultImage }}" alt="{{ $category->name }}" loading="lazy">
                            <span class="home-cat-card__badge"><i class="bi bi-arrow-up-right"></i></span>
                        </div>
                        <div class="home-cat-card__body">
                            <h3 class="home-cat-card__title">{{ $category->name }}</h3>
                            <p class="home-cat-card__meta">Khám phá ngay →</p>
                        </div>
                    </a>
                @empty
                    @foreach([
                        ['Trà Sữa', asset('images/products/tra-sua-tran-chau-duong-den.webp')],
                        ['Cà Phê', asset('images/products/ca-phe-sua-da.png')],
                        ['Nước Ép', asset('images/products/nuoc-ep-cam.png')],
                        ['Sinh Tố', asset('images/products/sinh-to-xoai.png')],
                    ] as $category)
                        <a href="{{ route('products.index') }}" class="home-cat-card">
                            <div class="home-cat-card__media">
                                <img src="{{ $category[1] }}" alt="{{ $category[0] }}" loading="lazy">
                                <span class="home-cat-card__badge"><i class="bi bi-arrow-up-right"></i></span>
                            </div>
                            <div class="home-cat-card__body">
                                <h3 class="home-cat-card__title">{{ $category[0] }}</h3>
                                <p class="home-cat-card__meta">Khám phá ngay →</p>
                            </div>
                        </a>
                    @endforeach
                @endforelse
            </div>
        </div>
    </section>

    {{-- Featured Products --}}
    <section id="featured-products" class="home-featured">
        <div class="container">
            <div class="home-section-head home-section-head--center">
                <div>
                    <p class="section-kicker mb-2">Bán chạy nhất</p>
                    <h2 class="section-title h1 mb-0">Gợi ý hôm nay</h2>
                    <p class="home-section-head__desc">Những món được yêu thích nhất — tươi mát, chuẩn vị, giao tận tay trong 30 phút.</p>
                </div>
            </div>

            <div class="home-product-grid">
                @php
                    $homeFeaturedSkus = $uiHomeFeaturedSkus ?? [
                        'CD-TS-001', 'CD-CF-001', 'CD-ST-001', 'CD-NE-001',
                        'CD-TC-001', 'CD-SD-001', 'CD-TS-002', 'CD-CF-002',
                    ];
                    $homeHasSkuColumn = \Illuminate\Support\Facades\Schema::hasColumn('products', 'sku');
                    $homeHasReviewsTable = \Illuminate\Support\Facades\Schema::hasTable('reviews');
                    $homeProductQuery = \App\Models\Product::with('category')
                        ->when($homeHasReviewsTable, fn ($query) => $query->withAvg('reviews', 'rating')->withCount('reviews'));
                    $homeFeaturedProducts = $homeHasSkuColumn
                        ? (clone $homeProductQuery)
                            ->whereIn('sku', $homeFeaturedSkus)
                            ->get()
                            ->sortBy(fn ($product) => array_search($product->sku, $homeFeaturedSkus, true))
                            ->values()
                        : (clone $homeProductQuery)
                            ->where('status', true)
                            ->latest()
                            ->limit(8)
                            ->get();
                @endphp
                @forelse($homeFeaturedProducts as $product)
                    @php
                        $reviewCount = (int) ($product->reviews_count ?? 0);
                        $rating = $reviewCount > 0 ? round((float) ($product->reviews_avg_rating ?? 0), 1) : 0;
                    @endphp
                    <article class="home-product">
                        <div class="home-product__img">
                            <span class="home-product__tag">{{ $product->category->name }}</span>
                            <a href="{{ route('products.show', $product->slug) }}">
                                <x-product-image
                                    :src="$product->image_url"
                                    :sku="$product->sku ?? null"
                                    :name="$product->name"
                                    :alt="$product->name"
                                    :category="$product->category?->name"
                                />
                            </a>
                            <div class="product-image-cart-form">
                                <button
                                    type="button"
                                    class="product-cart-btn"
                                    aria-label="Chọn size và topping cho {{ $product->name }}"
                                    data-quick-add
                                    data-action="{{ route('cart.add', $product->id) }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ number_format($product->price, 0, ',', '.') }}đ"
                                    data-image="{{ $product->image_url }}"
                                    data-category="{{ $product->category?->name }}"
                                >
                                    <i class="bi bi-cart-plus" aria-hidden="true"></i>
                                </button>
                            </div>
                        </div>
                        <div class="home-product__body">
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
                            @if(!empty($product->sku))
                                <p class="home-product__sku">{{ $product->sku }}</p>
                            @else
                                <p class="home-product__sku">&nbsp;</p>
                            @endif
                            <div class="home-product__footer">
                                <span class="home-product__price">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                            </div>
                        </div>
                    </article>
                @empty
                    @foreach([
                        ['Matcha Latte', '45.000đ', asset('images/matcha.png'), 'Trà', 'matcha-latte-da'],
                        ['Trà Dâu Dứa', '38.000đ', asset('images/products/tra-dau.jpg'), 'Trái cây', 'tropical-frost'],
                        ['Bạc Xỉu Đá', '29.000đ', asset('images/products/bac-xiu-da.jpg'), 'Cà phê', 'ca-phe-sua-da'],
                        ['Nước Chanh Bạc Hà', '35.000đ', asset('images/products/soda-chanh-day.jpg'), 'Giải khát', 'citrus-sunset'],
                    ] as $item)
                        <article class="home-product">
                            <div class="home-product__img">
                                <span class="home-product__tag">{{ $item[3] }}</span>
                                <a href="{{ route('products.show', $item[4]) }}">
                                    <img src="{{ $item[2] }}" alt="{{ $item[0] }}" loading="lazy">
                                </a>
                                <div class="product-image-cart-form">
                                    <button
                                        type="button"
                                        class="product-cart-btn"
                                        aria-label="Chọn size và topping cho {{ $item[0] }}"
                                        data-quick-add
                                        data-action="{{ route('cart.add', 'demo-' . $item[4]) }}"
                                        data-name="{{ $item[0] }}"
                                        data-price="{{ $item[1] }}"
                                        data-image="{{ $item[2] }}"
                                        data-category="{{ $item[3] }}"
                                    >
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
                                    <a href="{{ route('products.show', $item[4]) }}">{{ $item[0] }}</a>
                                </h3>
                                <p class="home-product__sku">&nbsp;</p>
                                <div class="home-product__footer">
                                    <span class="home-product__price">{{ $item[1] }}</span>
                                </div>
                            </div>
                        </article>
                    @endforeach
                @endforelse
            </div>

            <div class="home-featured__cta">
                <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg px-5 rounded-pill">
                    Xem toàn bộ menu <i class="bi bi-arrow-right ms-2"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- Brand Story --}}
    <section class="home-story">
        <div class="container">
            <div class="home-story__grid">
                <div class="home-story__visual">
                    <img src="{{ asset('images/chill-drink-promo.png') }}" alt="Chill Drink - đồ uống tươi mát" loading="lazy">
                    <div class="home-story__visual-badge">
                        <strong>5+ năm</strong>
                        <span>Phục vụ hơn 50.000 khách hàng</span>
                    </div>
                </div>
                <div class="home-story__content">
                    <p class="section-kicker">Về Chill Drink</p>
                    <h2>Đồ uống tươi mát,<br>chuẩn vị mỗi ngày</h2>
                    <p>Chúng tôi tin rằng mỗi ly nước đều xứng đáng được pha chế từ nguyên liệu tươi nhất — không shortcut, không compromise.</p>
                    <div class="home-story__points">
                        <div class="home-story__point">
                            <div class="home-story__point-icon"><i class="bi bi-flower1"></i></div>
                            <div>
                                <h4>Nguyên liệu chọn lọc</h4>
                                <p>Trái cây tươi mỗi ngày, trà và cà phê từ nguồn cung uy tín.</p>
                            </div>
                        </div>
                        <div class="home-story__point">
                            <div class="home-story__point-icon"><i class="bi bi-droplet-half"></i></div>
                            <div>
                                <h4>Quy trình chuẩn hóa</h4>
                                <p>Mỗi món đều được pha chế theo công thức riêng, đảm bảo vị ổn định.</p>
                            </div>
                        </div>
                        <div class="home-story__point">
                            <div class="home-story__point-icon"><i class="bi bi-recycle"></i></div>
                            <div>
                                <h4>Bao bì thân thiện</h4>
                                <p>Ưu tiên ly và ống hút có thể tái chế, góp phần bảo vệ môi trường.</p>
                            </div>
                        </div>
                    </div>
                    <a href="{{ route('products.index') }}" class="btn btn-primary rounded-pill px-4">
                        Khám phá thực đơn <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- Why Choose Us --}}
    <section class="home-why">
        <div class="container">
            <div class="home-section-head home-section-head--center mb-5">
                <div>
                    <p class="section-kicker mb-2">Tại sao chọn chúng tôi</p>
                    <h2 class="section-title h1 mb-0">Trải nghiệm đặt hàng tuyệt vời</h2>
                </div>
            </div>
            <div class="home-why__grid">
                <div class="home-why__card">
                    <div class="home-why__icon"><i class="bi bi-truck"></i></div>
                    <h3>Giao hàng siêu tốc</h3>
                    <p>Cam kết giao trong 30 phút. Đồ uống luôn tươi mát và chuẩn vị khi tới tay bạn.</p>
                </div>
                <div class="home-why__card">
                    <div class="home-why__icon"><i class="bi bi-heart-pulse"></i></div>
                    <h3>An toàn &amp; lành mạnh</h3>
                    <p>Không chất bảo quản, không phẩm màu nhân tạo. Minh bạch nguồn gốc nguyên liệu.</p>
                </div>
                <div class="home-why__card">
                    <div class="home-why__icon"><i class="bi bi-credit-card-2-front"></i></div>
                    <h3>Thanh toán linh hoạt</h3>
                    <p>Hỗ trợ COD, chuyển khoản, ví điện tử — nhanh chóng và bảo mật tuyệt đối.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Testimonials --}}
    <section class="home-reviews">
        <div class="container">
            <div class="home-section-head home-section-head--center mb-5">
                <div>
                    <p class="section-kicker mb-2">Khách hàng nói gì</p>
                    <h2 class="section-title h1 mb-0">Được yêu thích bởi cộng đồng</h2>
                </div>
            </div>
            <div class="home-reviews__grid">
                <div class="home-review">
                    <div class="home-review__stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="home-review__text">"Trà sữa ở đây đậm vị mà không quá ngọt, topping đầy ụ. Giao hàng nhanh, ly vẫn còn lạnh khi nhận."</p>
                    <div class="home-review__author">
                        <div class="home-review__avatar">MH</div>
                        <div>
                            <p class="home-review__name">Minh Hà</p>
                            <p class="home-review__role">Khách hàng thân thiết</p>
                        </div>
                    </div>
                </div>
                <div class="home-review">
                    <div class="home-review__stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i>
                    </div>
                    <p class="home-review__text">"Matcha latte rất chuẩn vị Nhật, foam mịn. Đặt online tiện lắm, giao đúng giờ hẹn mỗi lần."</p>
                    <div class="home-review__author">
                        <div class="home-review__avatar">TK</div>
                        <div>
                            <p class="home-review__name">Tuấn Kiệt</p>
                            <p class="home-review__role">Sinh viên</p>
                        </div>
                    </div>
                </div>
                <div class="home-review">
                    <div class="home-review__stars">
                        <i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-fill"></i><i class="bi bi-star-half"></i>
                    </div>
                    <p class="home-review__text">"Sinh tố xoài thơm nồng, vị tự nhiên không bị loãng. Giá hợp lý so với chất lượng, sẽ quay lại nhiều."</p>
                    <div class="home-review__author">
                        <div class="home-review__avatar">LN</div>
                        <div>
                            <p class="home-review__name">Lan Nguyễn</p>
                            <p class="home-review__role">Văn phòng</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA Membership --}}
    <section class="home-cta">
        <div class="container">
            <div class="home-cta__card">
                <div class="home-cta__content">
                    <p class="section-kicker mb-2">Ưu đãi thành viên</p>
                    <h2>Tham gia cộng đồng Chill Drink</h2>
                    <p>Đăng ký ngay để nhận voucher giảm 20% cho đơn đầu tiên và tích điểm đổi quà hấp dẫn.</p>
                    <div class="home-cta__perks">
                        <span class="home-cta__perk"><i class="bi bi-gift"></i> Voucher 20%</span>
                        <span class="home-cta__perk"><i class="bi bi-coin"></i> Tích điểm</span>
                        <span class="home-cta__perk"><i class="bi bi-bell"></i> Ưu đãi riêng</span>
                    </div>
                    @guest
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg rounded-pill px-5 align-self-start">
                            Đăng ký miễn phí <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    @else
                        <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg rounded-pill px-5 align-self-start">
                            Đặt hàng ngay <i class="bi bi-arrow-right ms-2"></i>
                        </a>
                    @endguest
                </div>
                <div class="home-cta__visual">
                    <img src="{{ asset('images/products/soda-blue-curacao.png') }}" alt="Đồ uống mùa hè" loading="lazy">
                </div>
            </div>
        </div>
    </section>

</div>
@endsection
