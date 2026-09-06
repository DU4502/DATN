<style>
    .drink-customizer .modal-dialog {
        width: min(680px, calc(100vw - 1.5rem));
        max-width: 680px;
        margin: .75rem auto;
    }

    .drink-customizer .modal-content {
        max-height: calc(100vh - 1.5rem);
        max-height: calc(100dvh - 1.5rem);
        overflow: hidden;
        border: 0;
        border-radius: 24px;
        box-shadow: 0 26px 70px rgba(8, 42, 38, .24);
    }

    .drink-customizer__form {
        display: flex;
        min-height: 0;
        max-height: calc(100vh - 1.5rem);
        max-height: calc(100dvh - 1.5rem);
        flex-direction: column;
    }

    .drink-customizer__header {
        flex: 0 0 auto;
        padding: 1.25rem 1.5rem .75rem !important;
    }

    .drink-customizer__title {
        margin: 0;
        color: var(--c-ink, #111827);
        font-size: clamp(1.45rem, 3vw, 1.85rem) !important;
        font-weight: 800;
        line-height: 1.2;
    }

    .drink-customizer__body {
        min-height: 0;
        overflow-y: auto;
        padding: 0 1.5rem 1rem !important;
        scrollbar-gutter: stable;
    }

    .drink-customizer__summary {
        display: flex;
        align-items: center;
        gap: .9rem;
        padding: .75rem .9rem;
        border: 1px solid #d6ebe5;
        border-radius: 16px;
        background: #effbf8;
    }

    .drink-customizer__thumb {
        width: 64px !important;
        height: 64px !important;
        flex: 0 0 64px;
        padding: .25rem;
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: 14px !important;
        background: #fff;
        object-fit: contain;
        object-position: center;
    }

    .drink-customizer__section {
        margin-top: .7rem;
        padding-top: .7rem;
        border-top: 1px solid #edf1f0;
    }

    .drink-customizer__section-title {
        display: flex;
        align-items: center;
        gap: .4rem;
        margin-bottom: .5rem;
        color: var(--c-ink, #111827);
        font-size: .92rem;
        font-weight: 800;
    }

    .drink-customizer__section-title i {
        color: var(--c-primary, #0d9373);
    }

    .drink-customizer__section-title small,
    .drink-customizer__section-title span {
        color: var(--c-muted, #6b7280);
        font-size: .78rem;
        font-weight: 500 !important;
    }

    .drink-customizer__sizes,
    .drink-customizer__sugar,
    .drink-customizer__ice,
    .drink-customizer__toppings {
        display: grid !important;
        gap: .5rem !important;
    }

    .drink-customizer__sizes,
    .drink-customizer__toppings {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .drink-customizer__levels {
        display: grid !important;
        grid-template-columns: minmax(0, 1.35fr) minmax(0, .9fr);
        gap: 1rem !important;
    }

    .drink-customizer__sugar {
        grid-template-columns: repeat(5, minmax(0, 1fr));
    }

    .drink-customizer__ice {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .drink-customizer__choice {
        display: flex;
        min-width: 0 !important;
        min-height: 48px;
        align-items: center;
        justify-content: center;
        padding: .45rem .55rem !important;
        border: 1.5px solid var(--c-border, #e5e7eb) !important;
        border-radius: 12px !important;
        background: #fff !important;
        color: var(--c-ink, #111827) !important;
        font-size: .9rem;
        font-weight: 750;
        line-height: 1.2;
        cursor: pointer;
        transition: background-color .16s ease, border-color .16s ease, color .16s ease, box-shadow .16s ease, transform .16s ease;
    }

    .drink-customizer__choice:hover,
    .drink-customizer__choice.active {
        border-color: var(--c-primary, #0d9373) !important;
        background: var(--c-primary-light, #e6f7f2) !important;
        color: var(--c-primary-dark, #067a5f) !important;
        box-shadow: 0 0 0 3px rgba(13, 147, 115, .12);
    }

    .drink-customizer__choice.active {
        transform: translateY(-1px);
    }

    .drink-customizer__size,
    .drink-customizer__topping {
        flex-direction: column;
    }

    .drink-customizer__choice small {
        display: block;
        margin-top: .18rem;
        color: var(--c-muted, #6b7280) !important;
        font-size: .72rem;
        font-weight: 700;
    }

    .drink-customizer__topping {
        min-height: 62px;
        align-items: flex-start;
        text-align: left;
    }

    .drink-customizer__footer {
        display: grid !important;
        grid-template-columns: auto minmax(0, 1fr);
        flex: 0 0 auto;
        align-items: center;
        gap: .8rem;
        padding: .85rem 1.5rem 1.1rem !important;
        border-top: 1px solid #edf1f0 !important;
        background: #fff;
        box-shadow: 0 -10px 24px rgba(8, 42, 38, .05);
    }

    .drink-customizer__quantity {
        display: inline-flex;
        min-height: 48px;
        align-items: center;
        border: 1px solid var(--c-border, #e5e7eb);
        border-radius: 999px;
        background: #fff;
    }

    .drink-customizer__quantity button {
        width: 40px !important;
        height: 46px !important;
        padding: 0;
        border: 0;
        background: transparent;
        color: var(--c-primary, #0d9373);
        font-size: 1.2rem;
    }

    .drink-customizer__quantity strong,
    .drink-customizer__quantity span {
        min-width: 22px;
        text-align: center;
    }

    .drink-customizer__submit {
        min-height: 50px;
        box-shadow: 0 6px 16px rgba(13, 147, 115, .2);
    }

    @media (max-width: 767.98px) {
        .drink-customizer__levels {
            grid-template-columns: 1fr;
            gap: .7rem !important;
        }

        .drink-customizer__toppings {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
    }

    @media (max-width: 575.98px) {
        .drink-customizer .modal-dialog {
            width: calc(100vw - 1rem);
            margin: .5rem auto;
        }

        .drink-customizer .modal-content,
        .drink-customizer__form {
            max-height: calc(100vh - 1rem);
            max-height: calc(100dvh - 1rem);
        }

        .drink-customizer__header {
            padding: 1rem 1rem .65rem !important;
        }

        .drink-customizer__body {
            padding: 0 1rem .8rem !important;
            scrollbar-gutter: auto;
        }

        .drink-customizer__footer {
            gap: .5rem;
            padding: .7rem 1rem .9rem !important;
        }

        .drink-customizer__choice {
            min-height: 44px;
            padding-inline: .35rem !important;
            font-size: .84rem;
        }

        .drink-customizer__topping {
            min-height: 58px;
        }
    }
</style>
