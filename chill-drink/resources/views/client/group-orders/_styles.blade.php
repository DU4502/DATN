<style>
    .group-page { min-height: 72vh; padding: 3.5rem 0 5rem; background: linear-gradient(180deg, #f6fbfa 0, #fff 420px); }
    .group-shell { max-width: 1180px; }
    .group-eyebrow { color: #07866f; font-size: .78rem; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
    .group-title { color: #10201d; font-size: clamp(2rem, 4vw, 3.15rem); font-weight: 850; letter-spacing: -.045em; }
    .group-card { background: #fff; border: 1px solid #dfece9; border-radius: 24px; box-shadow: 0 18px 45px rgba(16, 94, 80, .08); }
    .group-btn { border-radius: 999px; min-height: 46px; padding: .65rem 1.25rem; font-weight: 750; }
    .group-status { display: inline-flex; align-items: center; gap: .45rem; border-radius: 999px; padding: .42rem .78rem; font-size: .76rem; font-weight: 800; }
    .group-status.is-open { color: #04725e; background: #dff7f0; }
    .group-status.is-closed { color: #667085; background: #eef1f3; }
    .group-status-dot { width: 7px; height: 7px; border-radius: 50%; background: currentColor; }
    .group-countdown { display: inline-flex; align-items: center; gap: .55rem; width: fit-content; padding: .55rem .8rem; border: 1px solid #cdebe3; border-radius: 13px; color: #087560; background: #f0fbf8; font-weight: 850; font-variant-numeric: tabular-nums; transition: .25s ease; }
    .group-countdown-time { min-width: 3.35rem; font-size: 1rem; letter-spacing: .04em; }
    .group-countdown.is-urgent { color: #c2410c; border-color: #fed7aa; background: #fff7ed; animation: group-countdown-pulse 1s ease-in-out infinite; }
    .group-countdown.is-finished { color: #b42318; border-color: #fecaca; background: #fff1f2; animation: group-expire-pop .55s ease both; }
    .group-card.has-just-expired { border-color: #fecaca; box-shadow: 0 18px 45px rgba(180, 35, 24, .13); }
    .group-stat { padding: .8rem 1rem; border-radius: 16px; background: #f3faf8; color: #52615e; }
    .group-stat strong { display: block; color: #10201d; font-size: 1.08rem; }
    .group-form-label { margin-bottom: .55rem; color: #23332f; font-size: .88rem; font-weight: 750; }
    .group-input { min-height: 50px; border: 1px solid #d9e5e2; border-radius: 14px; background: #fbfdfc; }
    .group-input:focus { border-color: #0a9b80; box-shadow: 0 0 0 .22rem rgba(10, 155, 128, .12); background: #fff; }
    .group-hero { position: relative; overflow: hidden; padding: 2rem; background: linear-gradient(135deg, #063d34, #087e69); color: #fff; }
    .group-hero::after { content: ''; position: absolute; width: 260px; height: 260px; right: -85px; top: -120px; border-radius: 50%; background: rgba(255,255,255,.08); }
    .group-code { display: inline-flex; padding: .45rem .75rem; border: 1px solid rgba(255,255,255,.25); border-radius: 10px; background: rgba(255,255,255,.12); font-weight: 800; letter-spacing: .12em; }
    .group-share { position: relative; z-index: 1; min-width: min(100%, 360px); padding: .8rem; border-radius: 16px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); }
    .group-share-row { display: flex; gap: .55rem; }
    .group-share input { min-width: 0; color: #17332d; background: #fff; }
    .group-section-title { font-size: 1.22rem; font-weight: 850; color: #172622; }
    .group-option-box { padding: 1rem; border: 1px solid #dce8e5; border-radius: 16px; background: #fbfefd; }
    .group-order-form { overflow: hidden; padding: 0 !important; }
    .group-order-form-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.35rem 1.5rem; border-bottom: 1px solid #e5efed; background: linear-gradient(135deg, #f1fbf8, #fff); }
    .group-order-form-body { padding: 1.5rem; }
    .group-field-panel { height: 100%; padding: 1rem; border: 1px solid #deebe8; border-radius: 18px; background: #fbfefd; }
    .group-product-picker { position: relative; }
    .group-product-search { position: relative; }
    .group-product-search > .bi-search { position: absolute; top: 50%; left: 1rem; z-index: 2; color: #7b8c88; transform: translateY(-50%); pointer-events: none; }
    .group-product-search > .bi-chevron-down { position: absolute; top: 50%; right: 1rem; z-index: 2; color: #07866f; transform: translateY(-50%); pointer-events: none; transition: transform .2s ease; }
    .group-product-picker.is-open .bi-chevron-down { transform: translateY(-50%) rotate(180deg); }
    .group-product-search .group-input { padding-right: 2.75rem; padding-left: 2.75rem; background: #fff; font-weight: 700; }
    .group-product-menu { position: absolute; top: calc(100% + .5rem); right: 0; left: 0; z-index: 20; display: none; max-height: 280px; padding: .45rem; overflow-y: auto; border: 1px solid #d6e8e3; border-radius: 15px; background: #fff; box-shadow: 0 18px 40px rgba(13, 75, 64, .16); }
    .group-product-picker.is-open .group-product-menu { display: block; animation: group-menu-in .18s ease both; }
    .group-product-option { display: flex; align-items: center; gap: .85rem; width: 100%; padding: .65rem .75rem; border: 0; border-radius: 13px; color: #21332f; background: transparent; text-align: left; }
    .group-product-option:hover, .group-product-option:focus { color: #06735f; background: #edf9f6; outline: 0; }
    .group-product-option strong { display: block; }
    .group-product-option small { color: #07866f; font-weight: 800; white-space: nowrap; }
    .group-product-option-image { width: 58px !important; height: 58px !important; flex: 0 0 58px; padding: .2rem !important; border: 1px solid #dcece8; border-radius: 12px; object-fit: contain !important; background: #f2faf8 !important; }
    .group-product-option-copy { min-width: 0; flex: 1 1 auto; }
    .group-product-option-copy strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .group-product-option-copy .small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .group-product-option-price { margin-left: auto; }
    .group-search-empty { display: none; margin-top: .55rem; color: #b42318; font-size: .8rem; font-weight: 650; }
    .group-search-empty.is-visible { display: block; }
    .group-custom-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .group-submit-row { display: flex; align-items: end; gap: .85rem; }
    .group-submit-row .group-note { flex: 1 1 auto; }
    .group-add-button { min-width: 210px; box-shadow: 0 10px 25px rgba(10, 155, 128, .2); }
    .group-topping { display: inline-flex; align-items: center; gap: .6rem; margin: 0; padding: .72rem .9rem; border: 1px solid #dbe8e5; border-radius: 13px; background: #fff; cursor: pointer; transition: border-color .18s ease, background .18s ease, transform .18s ease; }
    .group-topping:hover { border-color: #8fd2c3; transform: translateY(-1px); }
    .group-topping:has(input:checked) { border-color: #0a9b80; color: #06735f; background: #eaf9f5; box-shadow: 0 7px 16px rgba(10, 155, 128, .1); }
    .member-card { height: 100%; overflow: hidden; }
    .member-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; background: #f4faf8; border-bottom: 1px solid #e1ece9; }
    .member-avatar { display: grid; place-items: center; width: 38px; height: 38px; flex: 0 0 auto; border-radius: 50%; color: #087560; background: #d9f4ed; font-weight: 850; }
    .member-item { display: flex; gap: .85rem; align-items: center; padding: 1rem 1.15rem; border-bottom: 1px solid #edf2f1; }
    .member-item:last-child { border-bottom: 0; }
    .group-item-actions { display: flex; align-items: center; justify-content: flex-end; gap: .45rem; margin-top: .35rem; }
    .group-item-action { display: inline-grid; place-items: center; width: 30px; height: 30px; padding: 0; border: 1px solid #d7e8e4; border-radius: 50%; background: #fff; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
    .group-item-action:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(8, 117, 96, .14); }
    .group-item-action.is-add { color: #07866f; border-color: #a9ddd1; background: #edfaf6; }
    .group-item-action.is-remove { color: #dc3545; border-color: #f3c5ca; background: #fff7f7; }
    .group-item-action:disabled, .group-add-button:disabled { cursor: wait; opacity: .65; }
    .group-live-toast { position: fixed; top: 88px; right: 22px; z-index: 1080; max-width: min(390px, calc(100vw - 32px)); padding: .85rem 1rem; border: 1px solid #bde4d9; border-radius: 14px; color: #05634f; background: #effbf7; box-shadow: 0 16px 35px rgba(10, 80, 66, .18); font-weight: 700; animation: group-menu-in .2s ease both; }
    .group-live-toast.is-error { color: #a61b1b; border-color: #fecaca; background: #fff1f2; }
    .group-owner-away { display: none; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.2rem; border: 1px solid #fed7aa; border-radius: 18px; color: #9a3412; background: linear-gradient(135deg, #fff7ed, #fffbeb); box-shadow: 0 12px 30px rgba(194, 65, 12, .08); }
    .group-owner-away.is-visible { display: flex; animation: group-menu-in .25s ease both; }
    .group-owner-away-icon { display: grid; place-items: center; width: 44px; height: 44px; flex: 0 0 auto; border-radius: 14px; color: #c2410c; background: #ffedd5; font-size: 1.2rem; }
    .group-owner-away-time { min-width: 76px; padding: .5rem .7rem; border-radius: 12px; color: #9a3412; background: rgba(255,255,255,.75); text-align: center; font-size: 1.05rem; font-weight: 850; font-variant-numeric: tabular-nums; }
    .member-item-image { width: 58px; height: 58px; flex: 0 0 auto; border-radius: 13px; object-fit: cover; background: #edf7f4; }
    .group-summary { position: sticky; bottom: 1rem; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.35rem; border: 1px solid #cbe6df; border-radius: 20px; background: rgba(255,255,255,.96); box-shadow: 0 18px 50px rgba(9, 94, 77, .16); backdrop-filter: blur(12px); }
    .empty-group { padding: 4rem 1rem; text-align: center; }
    .empty-group-icon { display: grid; place-items: center; width: 70px; height: 70px; margin: 0 auto 1rem; border-radius: 22px; color: #07836d; background: #dff6f0; font-size: 1.8rem; }
    @keyframes group-countdown-pulse { 50% { transform: scale(1.035); box-shadow: 0 0 0 6px rgba(249, 115, 22, .08); } }
    @keyframes group-expire-pop { 0% { transform: scale(.94); opacity: .55; } 60% { transform: scale(1.06); } 100% { transform: scale(1); opacity: 1; } }
    @keyframes group-menu-in { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 767.98px) {
        .group-page { padding-top: 2rem; }
        .group-hero { padding: 1.35rem; }
        .group-share { min-width: 100%; }
        .group-share-row, .group-summary { align-items: stretch; flex-direction: column; }
        .group-share-row .btn, .group-summary .btn { width: 100%; }
        .group-order-form-head { align-items: flex-start; padding: 1.15rem; }
        .group-order-form-body { padding: 1.15rem; }
        .group-custom-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .group-submit-row { align-items: stretch; flex-direction: column; }
        .group-add-button { width: 100%; min-width: 0; }
        .group-live-toast { top: 76px; right: 16px; left: 16px; }
        .group-owner-away { align-items: flex-start; }
    }
</style>
