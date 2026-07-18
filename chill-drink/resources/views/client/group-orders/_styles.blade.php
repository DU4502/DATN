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
    .group-chat-launcher { position: fixed; right: 28px; bottom: 100px; z-index: 1055; width: 58px; height: 58px; border: 3px solid #fff; border-radius: 50%; background: linear-gradient(135deg, #3857c8, #7048d7); color: #fff; box-shadow: 0 12px 32px rgba(63,75,180,.34); font-size: 1.35rem; transition: transform .2s ease; }
    .group-chat-launcher:hover { transform: translateY(-3px); }
    .group-chat-panel { position: fixed; right: 28px; bottom: 100px; z-index: 1055; width: min(390px, calc(100vw - 32px)); overflow: hidden; border: 0 !important; border-radius: 22px !important; box-shadow: 0 22px 60px rgba(31,38,90,.25); }
    .group-chat-head { padding: 1rem 1.15rem; display: flex; gap: .75rem; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #3857c8, #7048d7); color: #fff; }
    .group-chat-head .group-eyebrow { color: rgba(255,255,255,.72); }
    .group-chat-head strong { color: #fff; }
    .group-chat-tools { padding: .75rem 1rem; border-bottom: 1px solid #e5efed; display: grid; grid-template-columns: auto 1fr; gap: .65rem; align-items: center; background: #fff; }
    .group-chat-tabs { display: flex; gap: .5rem; flex-wrap: wrap; }
    .group-chat-tab { border: 1px solid #d9e8e4; background: #fff; border-radius: 999px; padding: .45rem .8rem; font-weight: 600; color: #52615e; }
    .group-chat-tab.is-active { background: #5264ce; border-color: #5264ce; color: #fff; }
    .group-chat-recipient { min-height: 42px !important; padding: .45rem .7rem !important; border-radius: 12px !important; font-size: .9rem !important; }
    .group-chat-private-button { min-height: 42px; border: 1px solid #d9e0f4; background: #fff; border-radius: 12px; padding: .45rem .7rem; display: flex; align-items: center; color: #48516b; font-weight: 700; }
    .group-chat-private-button.is-active { border-color: #5264ce; color: #5264ce; background: #f3f4ff; }
    .group-chat-contacts { padding: .75rem 1rem; border-bottom: 1px solid #e5e8f1; background: #f8f9ff; }
    .group-chat-contact-search { display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid #dfe3ef; border-radius: 12px; padding: .5rem .7rem; }
    .group-chat-contact-search input { width: 100%; border: 0; outline: 0; background: transparent; }
    .group-chat-contact-list { max-height: 145px; overflow-y: auto; margin-top: .55rem; display: grid; gap: .35rem; }
    .group-chat-contact { display: grid; grid-template-columns: 32px 1fr auto; align-items: center; gap: .55rem; width: 100%; padding: .42rem .5rem; border: 0; border-radius: 11px; background: transparent; text-align: left; }
    .group-chat-contact:hover, .group-chat-contact.is-active { background: #e9ebff; color: #4657c5; }
    .group-chat-contact .member-avatar { width: 32px; height: 32px; font-size: .8rem; }
    .group-chat-messages { height: 235px; overflow-y: auto; padding: 1rem; background: linear-gradient(180deg, #f4fbf8, #fff); }
    .group-chat-message { display: flex; margin-bottom: .75rem; }
    .group-chat-message.is-mine { justify-content: flex-end; }
    .group-chat-bubble { max-width: min(78%, 520px); background: #fff; border: 1px solid #e2ece9; border-radius: 16px; padding: .65rem .85rem; }
    .group-chat-message.is-mine .group-chat-bubble { background: linear-gradient(135deg, #5264ce, #7048d7); border-color: #5264ce; color: #fff; }
    .group-chat-compose { display: flex; gap: .6rem; padding: .8rem 1rem; border-top: 1px solid #e5efed; background: #fff; }
    .group-chat-compose .group-input { min-height: 44px; padding: .55rem .75rem; border-radius: 13px; }
    .group-chat-send { width: 44px; height: 44px; padding: 0 !important; border-radius: 50% !important; flex: 0 0 44px; }
    .group-chat-send { background: #5b5fd2 !important; border-color: #5b5fd2 !important; }
    .group-chat-attach { width: 40px; height: 44px; border: 0; border-radius: 12px; background: #eef0ff; color: #5264ce; font-size: 1.2rem; flex: 0 0 40px; }
    .group-chat-attach.is-image { background: #e8f8f1; color: #07875f; }
    .group-chat-attach.is-file { background: #eef0ff; color: #5264ce; }
    .group-chat-selected-file { display: flex; align-items: center; gap: .3rem; margin-top: .35rem; color: #5264ce; max-width: 220px; overflow: hidden; white-space: nowrap; text-overflow: ellipsis; }
    .group-chat-selected-file button { border: 0; background: transparent; color: #dc3545; font-size: 1.1rem; }
    .group-chat-image { display: block; max-width: 220px; max-height: 180px; border-radius: 12px; object-fit: cover; }
    .group-chat-file { display: flex; align-items: center; gap: .55rem; margin-top: .55rem; padding: .55rem; border-radius: 10px; background: rgba(255,255,255,.18); color: inherit; text-decoration: none; }
    .group-chat-file i { font-size: 1.35rem; }
    .group-chat-file span, .group-chat-file small { display: block; }
    .group-chat-read { display: block; margin-top: .35rem; text-align: right; color: rgba(255,255,255,.82); font-size: .7rem; }
    .group-chat-notification-stack { position: fixed; right: 24px; top: 92px; z-index: 2000; width: min(380px, calc(100vw - 32px)); display: grid; gap: .55rem; }
    .group-chat-notification { width: 100%; border: 0; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .65rem; text-align: left; padding: .85rem 1rem; border-radius: 14px; background: #303b8f; color: #fff; box-shadow: 0 14px 38px rgba(24,30,80,.3); font-weight: 600; animation: groupChatNoticeIn .22s ease-out; }
    .group-chat-notification:hover { background: #3d49a6; transform: translateY(-1px); }
    @keyframes groupChatNoticeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 575.98px) {
        .group-chat-launcher { right: 16px; bottom: 88px; }
        .group-chat-panel { right: 16px; bottom: 92px; }
    }
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
