@extends('layouts.client')

@section('title', 'Trang Chủ')

@section('content')
@php extract(require resource_path('views/partials/ui-product-data.php')); @endphp
<style>
    html {
        scroll-behavior: smooth;
    }

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

    .home-trust__item:last-child {
        border-right: 0;
    }

    .home-trust__item:hover {
        background: var(--c-primary-light);
    }

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
        .home-discover__banner-grid {
            grid-template-columns: 1fr 1fr;
        }
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
        .home-discover {
            padding-top: 2rem;
        }

        .home-discover__banner {
            min-height: 180px;
        }
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

    /* ─── Vouchers ─── */
    .home-vouchers {
        padding: 2rem 0 3.5rem;
        background: #fff;
    }

    .voucher-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1.25rem;
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
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .voucher-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 22px 45px rgba(15, 78, 62, 0.12);
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
        padding: 0.35rem 0.85rem;
        border-radius: 999px;
        background: rgba(255, 137, 73, 0.16);
        color: #ff6d2d;
        font-size: 0.72rem;
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
        background: rgba(255, 137, 73, 0.28);
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
        color: var(--drink-primary-dark);
        background: rgba(13, 147, 115, 0.06);
        font-size: 0.78rem;
        font-weight: 800;
        line-height: 1.25;
    }

    @media (max-width: 767.98px) {
        .voucher-grid {
            grid-template-columns: 1fr;
        }

        .voucher-card {
            grid-template-columns: 120px minmax(0, 1fr) auto;
            background: linear-gradient(90deg, #2fb9a0 0 120px, #ffffff 120px 100%);
            column-gap: 1rem;
        }

        .voucher-card__label {
            width: 90px;
            font-size: 0.76rem;
        }
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
        scroll-margin-top: 4.5rem;
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

    .home-quick-modal .modal-dialog {
        max-width: 620px;
    }

    .home-quick-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 24px;
        box-shadow: 0 26px 70px rgba(8, 42, 38, .24);
    }

    .home-quick-modal .modal-header {
        padding: 1.2rem 1.4rem .7rem;
    }

    .home-quick-modal .modal-body {
        padding: .8rem 1.4rem 1.1rem;
    }

    .home-quick-thumb {
        width: 76px;
        height: 76px;
        border: 1px solid var(--c-border);
        border-radius: 18px;
        padding: .35rem;
        object-fit: contain;
        background: #fff;
    }

    .home-quick-product {
        padding: .75rem;
        border: 1px solid var(--c-border);
        border-radius: 18px;
        background: var(--c-bg-warm);
    }

    .home-quick-section {
        padding-top: .9rem;
        margin-top: .9rem;
        border-top: 1px solid var(--c-border-light);
    }

    .home-quick-label {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-bottom: .65rem;
        color: var(--c-ink);
        font-size: .82rem;
        font-weight: 800;
    }

    .home-quick-label i {
        color: var(--c-primary);
    }

    .home-quick-choice {
        min-width: 64px;
        min-height: 46px;
        border: 1.5px solid var(--c-border);
        border-radius: 12px;
        padding: .48rem .8rem;
        color: var(--c-ink);
        background: #fff;
        font-weight: 800;
    }

    .home-quick-choice:hover,
    .home-quick-choice.active {
        border-color: var(--c-primary);
        color: var(--c-primary-dark);
        background: var(--c-primary-light);
        box-shadow: 0 0 0 3px rgba(13, 147, 115, .12);
    }

    .home-quick-size {
        flex: 1 1 0;
    }

    .home-quick-choice small {
        display: block;
        margin-top: .08rem;
        color: var(--c-muted);
        font-size: .68rem;
        font-weight: 700;
    }

    .home-quick-topping {
        min-width: 0;
        flex: 1 1 30%;
        text-align: left;
    }

    .home-quick-topping small {
        display: block;
        font-size: .72rem;
        opacity: .8;
    }

    .home-quick-footer {
        display: flex;
        align-items: center;
        gap: .8rem;
        width: 100%;
    }

    .home-quick-qty {
        display: inline-flex;
        align-items: center;
        border: 1px solid var(--c-border);
        border-radius: 999px;
        background: #fff;
    }

    .home-quick-qty button {
        width: 36px;
        height: 42px;
        border: 0;
        color: var(--c-primary);
        background: transparent;
        font-size: 1.15rem;
    }

    .home-quick-qty strong {
        min-width: 24px;
        text-align: center;
    }

    @media (max-width: 575.98px) {
        .home-quick-topping {
            flex-basis: 46%;
        }

        .home-quick-modal .modal-body {
            padding-inline: 1rem;
        }
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
        padding: 1.25rem;
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

    .home-product__name a:hover {
        color: var(--c-primary);
    }

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

    .home-story__content .section-kicker {
        margin-bottom: 0.75rem;
    }

    .home-story__content h2 {
        font-size: clamp(1.75rem, 3.5vw, 2.5rem);
        font-weight: 800;
        letter-spacing: -0.03em;
        margin-bottom: 1.25rem;
        line-height: 1.15;
    }

    .home-story__content>p {
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

    .home-why .section-kicker {
        color: rgba(255, 255, 255, 0.75);
    }

    .home-why .section-title {
        color: #fff;
    }

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

    .home-cta__perk i {
        color: var(--c-accent);
    }

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
        .home-product-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    @media (max-width: 991.98px) {
        .home-trust__inner {
            grid-template-columns: repeat(2, 1fr);
        }

        .home-trust__item:nth-child(2) {
            border-right: 0;
        }

        .home-trust__item:nth-child(1),
        .home-trust__item:nth-child(2) {
            border-bottom: 1px solid var(--c-border-light);
        }

        .home-cat-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .home-product-grid {
            grid-template-columns: repeat(2, 1fr);
        }

        .home-story__grid {
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }

        .home-story__visual {
            aspect-ratio: 16/10;
            max-height: 400px;
        }

        .home-why__grid {
            grid-template-columns: 1fr;
        }

        .home-reviews__grid {
            grid-template-columns: 1fr;
        }

        .home-cta__card {
            grid-template-columns: 1fr;
        }

        .home-cta__visual {
            min-height: 220px;
            order: -1;
        }

        .home-cta__visual::after {
            background: linear-gradient(180deg, transparent 40%, var(--c-ink) 100%);
        }
    }

    @media (max-width: 767.98px) {
        .home-trust {
            margin-top: -2rem;
        }

        .home-trust__inner {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .home-trust__item {
            border-right: 1px solid var(--c-border-light);
            border-bottom: 1px solid var(--c-border-light);
        }

        .home-trust__item:nth-child(2n) {
            border-right: 0;
        }

        .home-trust__item:nth-last-child(-n + 2) {
            border-bottom: 0;
        }

        .home-section-head {
            flex-direction: column;
            align-items: flex-start;
        }

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

        .home-cat-grid::-webkit-scrollbar {
            display: none;
        }

        .home-cat-grid>* {
            flex: 0 0 72%;
            scroll-snap-align: center;
        }

        .home-product-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (min-width: 576px) and (max-width: 991.98px) {
        .home-page {
            --home-section-py: 3rem;
            --home-radius: 18px;
        }

        .home-discover {
            padding-top: 2rem;
        }

        .home-discover__top,
        .home-section-head {
            gap: .75rem;
            margin-bottom: 1.25rem;
        }

        .home-discover__types {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .home-discover__products {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .home-discover__banner-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .75rem;
        }

        .home-discover__type {
            min-height: 0;
            padding: 1rem;
            border-radius: 14px;
        }

        .home-discover__type-icon {
            width: 40px;
            height: 40px;
            margin-bottom: .55rem;
            border-radius: 11px;
        }

        .home-discover__type-title {
            font-size: .9rem;
        }

        .home-discover__type p {
            display: none;
        }

        .home-discover-card {
            border-radius: 14px;
        }

        .home-discover-card__media {
            aspect-ratio: 1;
        }

        .home-discover-card__body {
            gap: .35rem;
            padding: .7rem;
        }

        .home-discover-card__title {
            min-height: 2.1rem;
            font-size: .88rem;
        }

        .home-discover-card__price {
            font-size: .95rem;
        }

        .home-discover-card__button {
            font-size: .7rem;
        }

        .home-discover__banner {
            min-height: 155px;
            padding: 1.25rem;
            border-radius: 16px;
        }

        .home-discover__banner-title {
            font-size: 1.45rem;
        }

        .home-discover__banner-text {
            margin-bottom: .75rem;
            font-size: .8rem;
        }

        .home-cat-grid {
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: .75rem;
        }

        .home-product-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .home-story__grid {
            grid-template-columns: .9fr 1.1fr;
            gap: 1.5rem;
        }

        .home-story__visual {
            max-height: 330px;
        }

        .home-why__grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .8rem;
        }

        .home-reviews__grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: .75rem;
        }

        .home-review-card {
            padding: 1rem;
        }

        .home-cta__card {
            grid-template-columns: 1.15fr .85fr;
        }

        .home-cta__visual {
            min-height: 260px;
            order: 0;
        }
    }

    @media (max-width: 575.98px) {
        .home-page {
            --home-section-py: 2.25rem;
            --home-radius: 14px;
        }

        .home-trust {
            margin-top: -1.25rem;
            padding-bottom: 0.5rem;
        }

        .home-trust__item {
            gap: 0.55rem;
            padding: 0.75rem 0.6rem;
        }

        .home-trust__icon {
            width: 34px;
            height: 34px;
            font-size: 0.9rem;
        }

        .home-trust__value {
            font-size: 0.78rem;
        }

        .home-trust__label {
            font-size: 0.64rem;
        }

        .home-discover {
            padding-top: 1.5rem;
        }

        .home-discover__top,
        .home-section-head {
            gap: 0.45rem;
            margin-bottom: 0.9rem;
        }

        .home-discover__types,
        .home-discover__products {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.55rem;
        }

        .home-discover__type {
            min-height: 0;
            padding: 0.75rem;
            border-radius: 12px;
        }

        .home-discover__type::after {
            top: 0.55rem;
            right: 0.55rem;
            font-size: 0.75rem;
        }

        .home-discover__type-icon {
            width: 34px;
            height: 34px;
            margin-bottom: 0.45rem;
            border-radius: 10px;
            font-size: 0.95rem;
        }

        .home-discover__type-title {
            font-size: 0.82rem;
        }

        .home-discover__type p {
            display: none;
        }

        .home-discover-card {
            border-radius: 12px;
        }

        .home-discover-card__media {
            aspect-ratio: 1;
        }

        .home-discover-card__body {
            gap: 0.3rem;
            padding: 0.55rem;
        }

        .home-discover-card__tag {
            top: 0.4rem;
            left: 0.4rem;
            padding: 0.18rem 0.4rem;
            font-size: 0.52rem;
        }

        .home-discover-card__title {
            min-height: 2rem;
            font-size: 0.78rem;
            line-height: 1.25;
        }

        .home-discover-card__meta {
            gap: 0.3rem;
        }

        .home-discover-card__price {
            font-size: 0.84rem;
        }

        .home-discover-card__button {
            font-size: 0.62rem;
        }

        .home-discover__banner-grid {
            gap: 0.65rem;
            margin-top: 1rem;
        }

        .home-discover__banner {
            min-height: 140px;
            padding: 1rem;
            border-radius: 14px;
        }

        .home-discover__banner-title {
            font-size: 1.3rem;
        }

        .home-discover__banner-text {
            margin-bottom: 0.7rem;
            font-size: 0.78rem;
            line-height: 1.4;
        }

        .home-discover__banner-button {
            padding: 0.55rem 0.85rem;
            font-size: 0.75rem;
        }

        .home-cat-grid {
            gap: 0.65rem;
        }

        .home-cat-grid>* {
            flex-basis: 58%;
        }

        .home-cat-card__body {
            padding: 0.65rem;
        }

        .home-cat-card__title {
            font-size: 0.86rem;
        }

        .home-cat-card__meta {
            font-size: 0.68rem;
        }

        .home-product-grid {
            gap: 0.6rem;
        }

        .home-product {
            border-radius: 12px;
        }

        .home-product__tag {
            top: 0.4rem;
            left: 0.4rem;
            padding: 0.16rem 0.4rem;
            font-size: 0.5rem;
        }

        .home-product__favorite-form {
            top: 0.4rem;
            right: 0.4rem;
        }

        .home-product__favorite {
            width: 32px;
            height: 32px;
        }

        .home-product__favorite i {
            font-size: 0.82rem;
        }

        .home-product__body {
            padding: 0.6rem;
        }

        .home-product__rating,
        .home-product__sku {
            display: none;
        }

        .home-product__name {
            min-height: 2rem;
            font-size: 0.8rem;
            line-height: 1.25;
        }

        .home-product__footer {
            padding-top: 0.45rem;
        }

        .home-product__price {
            font-size: 0.9rem;
        }

        .home-featured__cta {
            margin-top: 1.25rem;
        }

        .home-story__grid {
            gap: 1rem;
        }

        .home-story__visual {
            max-height: 260px;
        }

        .home-story__points {
            gap: 0.55rem;
        }

        .home-story__point,
        .home-why__card,
        .home-review {
            padding: 0.85rem;
            border-radius: 12px;
        }

        .home-why__grid,
        .home-reviews__grid {
            gap: 0.65rem;
        }

        .home-why__icon {
            width: 38px;
            height: 38px;
        }

        .home-cta__visual {
            min-height: 150px;
        }

        .home-cta__content {
            padding: 1rem;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        html {
            scroll-behavior: auto;
        }
    }
</style>

<div class="home-page">

    {{-- Slideshow — giữ nguyên --}}
    <x-animated-slider :slides="$slides" />

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

    + @php
    $homeRecommendations = $recommendationResult['recommendations'] ?? collect();
    $homeRecommendationMode = $recommendationResult['mode'] ?? 'empty';
    $homeRecommendationWeather = $recommendationResult['weather'] ?? null;
    $homeRecommendationIcon = 'bi-fire';
    $homeRecommendationTemperature = null;
    if ($homeRecommendationMode === 'weather' && $homeRecommendationWeather) {
    $temperature = round($homeRecommendationWeather->temperatureC, 1);
    $temperatureDecimals = $temperature === (float) (int) $temperature ? 0 : 1;
    $homeRecommendationTemperature = number_format($temperature, $temperatureDecimals, '.', '');
    $homeRecommendationIcon = match (true) {
    $homeRecommendationWeather->isRaining => 'bi-cloud-rain',
    $temperature >= 35 => 'bi-brightness-high',
    $temperature < 20=> 'bi-cloud',
        default => 'bi-cloud-sun',
        };
        }
        @endphp
        @if($homeRecommendations->isNotEmpty())
        {{-- Featured Products --}}
        <section id="featured-products" class="home-featured">
            <div class="container">
                <div class="home-section-head home-section-head--center">
                    <div>
                        <p class="section-kicker mb-2">
                            {{ $homeRecommendationMode === 'weather' ? 'Theo thời tiết tại chi nhánh' : 'Đang được yêu thích' }}
                        </p>
                        <h2 class="section-title h1 mb-0">
                            <i class="bi {{ $homeRecommendationIcon }}" aria-hidden="true"></i>
                            Gợi ý hôm nay
                        </h2>
                        <p class="home-section-head__desc">
                            @if($homeRecommendationMode === 'weather' && $homeRecommendationWeather)
                            <strong>{{ $homeRecommendationTemperature }}°C</strong>
                            · {{ $recommendationResult['message'] }}
                            @else
                            {{ $recommendationResult['message'] }}
                            @endif
                        </p>
                    </div>
                </div>

                <div class="home-product-grid">
                    @foreach($homeRecommendations as $recommendation)
                    @php
                    $product = $recommendation['product'];
                    $reviewCount = (int) ($product->reviews_count ?? 0);
                    $rating = $reviewCount > 0 ? round((float) ($product->reviews_avg_rating ?? 0), 1) : 0;
                    $isAvailableAtCurrentBranch = $product->availabilityAt($branch) === true;
                    @endphp
                    <article class="home-product">
                        <div class="home-product__img">
                            <span class="home-product__tag">{{ $product->category?->name ?? 'Đồ uống' }}</span>
                            @auth
                            @php
                            $isFavorite = $favoriteProductIds->contains($product->id);
                            @endphp
                            <form class="home-product__favorite-form" method="POST" action="{{ route('favorites.toggle', $product) }}" data-home-favorite-form>
                                @csrf
                                <button type="submit" class="home-product__favorite {{ $isFavorite ? 'is-active' : '' }}" aria-label="{{ $isFavorite ? 'Bỏ yêu thích' : 'Thêm vào yêu thích' }}" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" title="{{ $isFavorite ? 'Bỏ yêu thích' : 'Yêu thích' }}" data-home-favorite-button>
                                    <i class="bi {{ $isFavorite ? 'bi-heart-fill' : 'bi-heart' }}" aria-hidden="true"></i>
                                </button>
                            </form>
                            @else
                            <a class="home-product__favorite-form home-product__favorite" href="{{ route('login') }}" aria-label="Đăng nhập để yêu thích" title="Đăng nhập để yêu thích">
                                <i class="bi bi-heart" aria-hidden="true"></i>
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
                                    aria-label="Chọn tùy chọn cho {{ $product->name }}"
                                    data-home-quick-add
                                    data-action="{{ route('cart.add', $product->id) }}"
                                    data-name="{{ $product->name }}"
                                    data-price="{{ number_format($product->price, 0, ',', '.') }}đ"
                                    data-base-price="{{ (float) $product->price }}"
                                    data-sizes='@json($product->relationLoaded("sizes") ? $product->sizes->pluck("pivot.price", "name") : [])'
                                    data-image="{{ $product->image_url }}"
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
                                <span class="home-product__price">{{ number_format($product->price, 0, ',', '.') }}đ</span>
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                <div class="home-featured__cta">
                    <a href="{{ route('products.index') }}" class="btn btn-primary btn-lg px-5 rounded-pill">
                        Xem toàn bộ menu <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>
            </div>
        </section>
        @endif

        {{-- Vouchers Section --}}
        @if(($featuredVouchers ?? collect())->isNotEmpty())
        <section class="home-vouchers">
            <div class="container">
                <div class="home-section-head mb-4">
                    <div>
                        <p class="section-kicker mb-2">Ưu Đãi Độc Quyền</p>
                        <h2 class="section-title h1 mb-0">Mã Giảm Giá Phổ Biến</h2>
                    </div>
                    <a href="{{ route('products.index') }}" class="home-link-arrow d-none d-md-inline-flex">
                        Xem toàn bộ menu <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="voucher-grid">
                    @foreach($featuredVouchers as $voucher)
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
                    @endforeach
                </div>
            </div>
        </section>
        @endif

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
                    ['Sinh Tố', asset('images/products/sinh-to-dau.png')],
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
                        <p class="home-review__text">"Trà sữa matcha rất chuẩn vị Nhật, lớp bọt sữa mịn. Đặt trực tuyến tiện lắm, giao đúng giờ hẹn mỗi lần."</p>
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
                        <p>Đăng ký ngay để nhận phiếu giảm 20% cho đơn đầu tiên và tích điểm đổi quà hấp dẫn.</p>
                        <div class="home-cta__perks">
                            <span class="home-cta__perk"><i class="bi bi-gift"></i> Ưu đãi 20%</span>
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

<div class="modal fade home-quick-modal" id="homeQuickAddModal" tabindex="-1" aria-labelledby="homeQuickAddTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="homeQuickAddForm" method="POST" data-ajax-cart>
                @csrf
                <input type="hidden" name="size" value="S" data-home-quick-input="size">
                <input type="hidden" name="sugar_level" value="50" data-home-quick-input="sugar">
                <input type="hidden" name="ice_level" value="100" data-home-quick-input="ice">
                <input type="hidden" name="toppings" value="[]" data-home-quick-input="toppings">
                <input type="hidden" name="quantity" value="1" data-home-quick-input="quantity">
                <div class="modal-header border-0 pb-0">
                    <h2 class="modal-title h4 fw-bold" id="homeQuickAddTitle">Tùy chọn đồ uống</h2>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
                </div>
                <div class="modal-body">
                    <div class="home-quick-product d-flex gap-3 align-items-center">
                        <img class="home-quick-thumb" src="{{ $uiPlaceholderImage('Sản phẩm', 'Đồ uống') }}" alt="Ảnh sản phẩm" data-home-quick-image>
                        <div>
                            <div class="fw-bold fs-5" data-home-quick-name></div>
                            <div class="text-primary fw-bold" data-home-quick-price></div>
                        </div>
                    </div>
                    <div class="home-quick-section">
                        <div class="home-quick-label"><i class="bi bi-cup-straw"></i>Chọn kích cỡ</div>
                        <div class="d-flex gap-2" data-home-quick-group="size">
                            <button type="button" class="home-quick-choice home-quick-size active" data-value="S" data-extra="0">S<small>Giá gốc</small></button><button type="button" class="home-quick-choice home-quick-size" data-value="M" data-extra="5000">M<small>+5.000đ</small></button><button type="button" class="home-quick-choice home-quick-size" data-value="L" data-extra="10000">L<small>+10.000đ</small></button>
                        </div>
                    </div>
                    <div class="home-quick-section row g-3">
                        <div class="col-md-6">
                            <div class="home-quick-label"><i class="bi bi-droplet"></i>Mức đường</div>
                            <div class="d-flex flex-wrap gap-2" data-home-quick-group="sugar">
                                <button type="button" class="home-quick-choice" data-value="0">0%</button><button type="button" class="home-quick-choice" data-value="30">30%</button><button type="button" class="home-quick-choice" data-value="50">50%</button><button type="button" class="home-quick-choice" data-value="70">70%</button><button type="button" class="home-quick-choice active" data-value="100">100% (Tiêu chuẩn)</button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="home-quick-label"><i class="bi bi-snow"></i>Mức đá</div>
                            <div class="d-flex flex-wrap gap-2" data-home-quick-group="ice">
                                <button type="button" class="home-quick-choice" data-value="0">Không đá</button><button type="button" class="home-quick-choice" data-value="50">Ít đá</button><button type="button" class="home-quick-choice active" data-value="100">100% (Tiêu chuẩn)</button>
                            </div>
                        </div>
                    </div>
                    <div class="home-quick-section">
                        <div class="home-quick-label"><i class="bi bi-plus-circle"></i>Thêm món kèm <span class="text-secondary fw-normal">(có thể chọn nhiều)</span></div>
                        <div class="d-flex flex-wrap gap-2" data-home-toppings>
                            <button type="button" class="home-quick-choice home-quick-topping" data-name="Trân châu đen" data-price="5000">Trân châu đen<small>+5.000đ</small></button>
                            <button type="button" class="home-quick-choice home-quick-topping" data-name="Kem cheese" data-price="7000">Kem cheese<small>+7.000đ</small></button>
                            <button type="button" class="home-quick-choice home-quick-topping" data-name="Thạch nha đam" data-price="6000">Thạch nha đam<small>+6.000đ</small></button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 px-4 pb-4">
                    <div class="home-quick-footer">
                        <div class="home-quick-qty"><button type="button" data-home-qty-minus aria-label="Giảm số lượng">−</button><strong data-home-qty-label>1</strong><button type="button" data-home-qty-plus aria-label="Tăng số lượng">+</button></div>
                        <button type="submit" class="btn btn-primary flex-grow-1 rounded-pill py-3 fw-bold">Thêm vào giỏ · <span data-home-quick-total>0đ</span></button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const element = document.getElementById('homeQuickAddModal');
        const form = document.getElementById('homeQuickAddForm');
        if (!element || !form || !window.bootstrap) return;
        const modal = new bootstrap.Modal(element);
        const input = (name) => form.querySelector(`[data-home-quick-input="${name}"]`);
        let basePrice = 0;
        const updateTotal = () => {
            const sizeButton = element.querySelector('[data-home-quick-group="size"] .active');
            const sizeExtra = Number(sizeButton?.dataset.extra || 0);
            const toppings = JSON.parse(input('toppings').value || '[]');
            const toppingTotal = toppings.reduce((sum, item) => sum + Number(item.price || 0), 0);
            const quantity = Number(input('quantity').value || 1);
            element.querySelector('[data-home-quick-total]').textContent = ((basePrice + sizeExtra + toppingTotal) * quantity).toLocaleString('vi-VN') + 'đ';
        };
        const resetGroup = (name, value) => {
            input(name).value = value;
            element.querySelectorAll(`[data-home-quick-group="${name}"] .home-quick-choice`).forEach((button) => button.classList.toggle('active', button.dataset.value === value));
            updateTotal();
        };

        document.querySelectorAll('[data-home-quick-add]').forEach((button) => button.addEventListener('click', () => {
            form.action = button.dataset.action;
            element.querySelector('[data-home-quick-name]').textContent = button.dataset.name || '';
            element.querySelector('[data-home-quick-price]').textContent = button.dataset.price || '';
            basePrice = Number(button.dataset.basePrice || 0);
            const image = element.querySelector('[data-home-quick-image]');
            image.src = button.dataset.image || '';
            image.alt = button.dataset.name || 'Đồ uống';
            let sizesMap = {};
            try {
                sizesMap = JSON.parse(button.dataset.sizes || '{}');
            } catch (e) {}

            element.querySelectorAll('[data-home-quick-group="size"] .home-quick-size').forEach((sizeBtn) => {
                const sz = sizeBtn.dataset.value;
                if (sz === 'S') {
                    sizeBtn.dataset.extra = '0';
                } else if (sizesMap[sz] !== undefined) {
                    const extraPrice = Number(sizesMap[sz]);
                    sizeBtn.dataset.extra = extraPrice;
                    const small = sizeBtn.querySelector('small');
                    if (small) small.textContent = '+' + extraPrice.toLocaleString('vi-VN') + 'đ';
                }
            });

            resetGroup('size', 'S');
            resetGroup('sugar', '50');
            resetGroup('ice', '100');
            element.querySelectorAll('[data-home-toppings] .home-quick-choice').forEach((item) => item.classList.remove('active'));
            input('toppings').value = '[]';
            updateTotal();
            modal.show();
        }));

        element.querySelectorAll('[data-home-quick-group]').forEach((group) => group.addEventListener('click', (event) => {
            const button = event.target.closest('.home-quick-choice');
            if (button) resetGroup(group.dataset.homeQuickGroup, button.dataset.value);
        }));

        element.querySelector('[data-home-toppings]').addEventListener('click', (event) => {
            const button = event.target.closest('.home-quick-choice');
            if (!button) return;
            button.classList.toggle('active');
            input('toppings').value = JSON.stringify(Array.from(element.querySelectorAll('[data-home-toppings] .active')).map((item) => ({
                name: item.dataset.name,
                price: Number(item.dataset.price)
            })));
            updateTotal();
        });

        const changeQuantity = (amount) => {
            const quantity = Math.max(1, Math.min(20, Number(input('quantity').value || 1) + amount));
            input('quantity').value = String(quantity);
            element.querySelector('[data-home-qty-label]').textContent = String(quantity);
            updateTotal();
        };
        element.querySelector('[data-home-qty-minus]').addEventListener('click', () => changeQuantity(-1));
        element.querySelector('[data-home-qty-plus]').addEventListener('click', () => changeQuantity(1));

        document.addEventListener('cart:updated', () => {
            if (element.classList.contains('show')) modal.hide();
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
                        if (window.showToast) {
                            showToast(data.message || 'Lỗi khi nhận voucher', 'error');
                        } else {
                            alert(data.message || 'Lỗi khi nhận voucher');
                        }
                        return;
                    }

                    if (!guestIdentifier && data.guest_identifier) {
                        sessionStorage.setItem('guest_identifier', data.guest_identifier);
                    }

                    if (window.showToast) {
                        showToast(`Nhận voucher ${code} thành công! Đã lưu vào ví của bạn.`, 'success');
                    } else {
                        alert(`Nhận voucher ${code} thành công!`);
                    }
                    button.textContent = 'ĐÃ LƯU';
                    button.disabled = true;
                    button.style.background = '#e2e8f0';
                    button.style.color = '#64748b';
                    button.style.cursor = 'default';
                } catch (error) {
                    console.error('Network error:', error);
                    try {
                        await navigator.clipboard.writeText(code);
                        if (window.showToast) {
                            showToast(`Đã sao chép mã ${code}! Hãy nhập mã khi thanh toán.`, 'info');
                        } else {
                            alert(`Đã sao chép mã ${code}!`);
                        }
                    } catch (clipErr) {
                        if (window.showToast) {
                            showToast(`Mã giảm giá: ${code}`, 'info');
                        }
                    }
                }
            });
        });
    });
</script>

@auth
<script>
    document.querySelectorAll('[data-home-favorite-form]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const button = form.querySelector('[data-home-favorite-button]');
            if (!button || button.classList.contains('is-loading')) return;

            button.classList.add('is-loading');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) throw new Error('favorite_failed');

                const result = await response.json();
                const favorited = Boolean(result.favorited);
                const label = favorited ? 'Bỏ yêu thích' : 'Thêm vào yêu thích';

                button.classList.toggle('is-active', favorited);
                button.setAttribute('aria-pressed', favorited ? 'true' : 'false');
                button.setAttribute('aria-label', label);
                button.title = favorited ? 'Bỏ yêu thích' : 'Yêu thích';
                button.querySelector('i').className = `bi ${favorited ? 'bi-heart-fill' : 'bi-heart'}`;
            } catch (error) {
                form.submit();
            } finally {
                button.classList.remove('is-loading');
            }
        });
    });
</script>
@endauth
@endsection