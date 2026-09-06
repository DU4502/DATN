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
    .group-hero h1 { color: #fff; overflow-wrap: anywhere; }
    .group-hero p { overflow-wrap: anywhere; }
    .group-hero::after { content: ''; position: absolute; width: 260px; height: 260px; right: -85px; top: -120px; border-radius: 50%; background: rgba(255,255,255,.08); }
    .group-code { display: inline-flex; padding: .45rem .75rem; border: 1px solid rgba(255,255,255,.25); border-radius: 10px; background: rgba(255,255,255,.12); font-weight: 800; letter-spacing: .12em; }
    .group-share { position: relative; z-index: 1; min-width: min(100%, 360px); padding: .8rem; border-radius: 16px; background: rgba(255,255,255,.12); backdrop-filter: blur(8px); }
    .group-share-row { display: flex; gap: .55rem; }
    .group-share input { min-width: 0; color: #17332d; background: #fff; }
    .group-section-title { font-size: 1.22rem; font-weight: 850; color: #172622; }
    .group-option-box { padding: 1rem; border: 1px solid #dce8e5; border-radius: 16px; background: #fbfefd; }
    .group-create-shell { max-width: 1120px; }
    [data-vue-group-order-create].group-page { padding-top: 2rem; padding-bottom: 3.5rem; }
    .group-create-back { display: inline-flex; align-items: center; gap: .5rem; margin-bottom: 1.25rem; color: #6d7c78; font-size: .88rem; font-weight: 700; text-decoration: none; transition: color .18s ease, transform .18s ease; }
    .group-create-back:hover { color: #07866f; transform: translateX(-3px); }
    .group-create-layout { min-height: 0; }
    .group-create-hero { display: flex; flex-direction: column; justify-content: space-between; min-height: 100%; padding: 2rem; }
    .group-create-title { max-width: 320px; font-size: clamp(1.85rem, 3vw, 2.55rem); line-height: 1.08; letter-spacing: -.04em; }
    .group-create-benefits { display: grid; gap: .65rem; margin-top: 2.25rem; }
    .group-create-benefits > div { display: flex; align-items: center; gap: .7rem; padding: .65rem .75rem; border: 1px solid rgba(255,255,255,.12); border-radius: 13px; background: rgba(255,255,255,.08); font-size: .82rem; }
    .group-create-benefits span { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; flex: 0 0 30px; border-radius: 9px; color: #e6fff8; background: rgba(255,255,255,.13); }
    .group-create-form { padding: 1.75rem 2rem; }
    .group-create-form-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1.35rem; }
    .group-create-step { display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .7rem; border-radius: 999px; color: #087560; background: #e9f8f4; font-size: .74rem; font-weight: 800; white-space: nowrap; }
    .group-create-option { padding: .8rem; border-radius: 15px; }
    .group-create-option-head { display: flex; align-items: center; gap: .55rem; margin-bottom: .55rem; }
    .group-create-option-head > span { display: inline-flex; align-items: center; justify-content: center; width: 30px; height: 30px; flex: 0 0 30px; border-radius: 9px; color: #07866f; background: #e5f7f2; }
    .group-create-form .group-input { min-height: 46px; border-radius: 12px; }
    .group-create-form .form-text { color: #71807c; font-size: .72rem; line-height: 1.4; }
    .group-create-note { min-height: 78px !important; resize: vertical; }
    .group-create-form [data-group-create-submit] { min-height: 50px; box-shadow: 0 10px 24px rgba(10,155,128,.18); }
    .group-branch-picker { position: relative; }
    .group-branch-trigger { display: flex; align-items: center; justify-content: space-between; gap: .7rem; width: 100%; min-height: 58px; padding: .55rem .7rem; border: 1px solid #d9e5e2; border-radius: 12px; color: #172622; background: #fff; text-align: left; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .group-branch-trigger:hover, .group-branch-trigger.is-open { border-color: #0a9b80; background: #f8fffd; box-shadow: 0 0 0 .2rem rgba(10,155,128,.1); }
    .group-branch-trigger-content { display: flex; align-items: center; gap: .6rem; min-width: 0; }
    .group-branch-trigger-content > span:last-child { display: grid; gap: .08rem; min-width: 0; }
    .group-branch-trigger-content strong { overflow: hidden; color: #20322e; font-size: .82rem; text-overflow: ellipsis; white-space: nowrap; }
    .group-branch-trigger-content small { overflow: hidden; color: #71817d; font-size: .68rem; text-overflow: ellipsis; white-space: nowrap; }
    .group-branch-trigger-icon { display: inline-flex; align-items: center; justify-content: center; width: 34px; height: 34px; flex: 0 0 34px; border-radius: 10px; color: #07866f; background: #e6f7f2; }
    .group-branch-trigger > .bi-chevron-down { flex: 0 0 auto; color: #75918a; transition: transform .18s ease; }
    .group-branch-trigger.is-open > .bi-chevron-down { transform: rotate(180deg); }
    .group-branch-trigger.has-value { border-color: #b8ded5; }
    .group-branch-menu { position: absolute; top: calc(100% + .55rem); left: 0; z-index: 1080; width: min(420px, calc(100vw - 32px)); overflow: hidden; border: 1px solid #cfe5df; border-radius: 18px; background: #fff; box-shadow: 0 24px 60px rgba(10,73,62,.2); animation: group-menu-in .18s ease both; }
    .group-branch-menu-head { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .85rem 1rem; color: #fff; background: linear-gradient(135deg, #087560, #0aa184); }
    .group-branch-menu-head > span { display: grid; gap: .08rem; }
    .group-branch-menu-head small { color: rgba(255,255,255,.75); font-size: .68rem; }
    .group-branch-menu-head > i { font-size: 1.25rem; }
    .group-branch-list { display: grid; gap: .4rem; max-height: 290px; padding: .55rem; overflow-y: auto; }
    .group-branch-option { display: grid; grid-template-columns: 38px minmax(0, 1fr) 24px; align-items: center; gap: .65rem; width: 100%; padding: .7rem; border: 1px solid transparent; border-radius: 13px; color: #223530; background: #fff; text-align: left; transition: border-color .16s ease, background .16s ease, transform .16s ease; }
    .group-branch-option:hover { border-color: #cbe6df; background: #f0faf7; transform: translateY(-1px); }
    .group-branch-option.is-selected { border-color: #82cbbb; background: #e7f8f3; }
    .group-branch-option-icon { display: inline-flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 11px; color: #07866f; background: #ddf4ee; }
    .group-branch-option-copy { display: grid; gap: .15rem; min-width: 0; }
    .group-branch-option-copy strong { font-size: .83rem; }
    .group-branch-option-copy small { color: #70807c; font-size: .69rem; line-height: 1.35; }
    .group-branch-check { color: #0a9b80; text-align: center; }
    .group-branch-empty { display: flex; align-items: center; justify-content: center; gap: .55rem; padding: 1.5rem 1rem; color: #71817d; font-size: .8rem; }
    .group-datetime { position: relative; }
    .group-datetime-trigger { display: flex; align-items: center; justify-content: space-between; gap: .75rem; width: 100%; min-height: 46px; padding: .6rem .8rem; border: 1px solid #d9e5e2; border-radius: 12px; color: #172622; background: #fff; font-weight: 750; text-align: left; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    .group-datetime-trigger > span { display: inline-flex; align-items: center; gap: .55rem; min-width: 0; }
    .group-datetime-trigger > span i { color: #079179; }
    .group-datetime-trigger > .bi-chevron-down { color: #75918a; transition: transform .18s ease; }
    .group-datetime-trigger:hover, .group-datetime-trigger.is-open { border-color: #0a9b80; background: #f8fffd; box-shadow: 0 0 0 .2rem rgba(10,155,128,.1); }
    .group-datetime-trigger.is-open > .bi-chevron-down { transform: rotate(180deg); }
    .group-datetime-popover { position: absolute; top: calc(100% + .55rem); right: 0; z-index: 1080; width: min(370px, calc(100vw - 32px)); padding: 1rem; border: 1px solid #cfe5df; border-radius: 19px; background: #fff; box-shadow: 0 24px 60px rgba(10,73,62,.2); animation: group-menu-in .18s ease both; }
    .group-datetime-calendar-head { display: grid; grid-template-columns: 36px 1fr 36px; align-items: center; gap: .5rem; margin-bottom: .8rem; }
    .group-datetime-calendar-head strong { color: #12463c; text-align: center; text-transform: capitalize; }
    .group-datetime-calendar-head button { width: 36px; height: 36px; border: 0; border-radius: 10px; color: #087560; background: #e9f8f4; }
    .group-datetime-calendar-head button:hover { color: #fff; background: #0a9b80; }
    .group-datetime-weekdays, .group-datetime-days { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: .25rem; }
    .group-datetime-weekdays { margin-bottom: .3rem; }
    .group-datetime-weekdays span { color: #78908a; font-size: .7rem; font-weight: 850; text-align: center; }
    .group-datetime-days button, .group-datetime-days > span { min-height: 35px; }
    .group-datetime-days button { border: 0; border-radius: 10px; color: #253833; background: transparent; font-size: .82rem; font-weight: 750; transition: color .15s ease, background .15s ease, transform .15s ease; }
    .group-datetime-days button:hover:not(:disabled) { color: #087560; background: #e8f8f4; transform: translateY(-1px); }
    .group-datetime-days button.is-selected { color: #fff; background: linear-gradient(135deg, #0a9b80, #087560); box-shadow: 0 6px 14px rgba(10,155,128,.25); }
    .group-datetime-days button:disabled { color: #c8d1ce; cursor: not-allowed; }
    .group-datetime-time { display: flex; align-items: flex-end; justify-content: center; gap: .5rem; margin-top: .85rem; padding: .8rem; border-radius: 14px; background: #f1faf7; }
    .group-datetime-time > div { flex: 1 1 0; }
    .group-datetime-time label { display: block; margin-bottom: .3rem; color: #68807a; font-size: .68rem; font-weight: 800; text-transform: uppercase; }
    .group-datetime-time select { width: 100%; height: 40px; padding: 0 .65rem; border: 1px solid #cfe3de; border-radius: 10px; color: #0b6f5d; background: #fff; font-weight: 850; }
    .group-datetime-time > span { padding-bottom: .5rem; color: #0a9b80; font-size: 1.1rem; font-weight: 900; }
    .group-datetime-footer { display: flex; justify-content: space-between; gap: .65rem; margin-top: .85rem; }
    .group-datetime-footer button { min-height: 40px; padding: .55rem 1rem; border-radius: 999px; font-size: .78rem; font-weight: 850; }
    .group-datetime-today { border: 1px solid #cfe5df; color: #087560; background: #fff; }
    .group-datetime-done { border: 0; color: #fff; background: #0a9b80; box-shadow: 0 7px 16px rgba(10,155,128,.2); }
    .group-order-form { overflow: visible; padding: 0 !important; }
    .group-chat-launcher { position: fixed; right: 28px; bottom: 100px; z-index: 1055; width: 58px; height: 58px; border: 3px solid #fff; border-radius: 50%; background: linear-gradient(135deg, #3857c8, #7048d7); color: #fff; box-shadow: 0 12px 32px rgba(63,75,180,.34); font-size: 1.35rem; transition: transform .2s ease; }
    .group-chat-launcher:hover { transform: translateY(-3px); }
    .group-chat-hide-launcher .group-chat-launcher { display: none !important; }
    .group-chat-launcher-badge { position: absolute; top: -5px; right: -5px; min-width: 22px; height: 22px; padding: 0 5px; border: 2px solid #fff; border-radius: 999px; background: #dc3545; color: #fff; font-size: .7rem; font-weight: 800; line-height: 18px; }
    .group-chat-panel { position: fixed; right: 24px; bottom: 100px; z-index: 1070; width: min(376px, calc(100vw - 32px)); height: min(500px, calc(100vh - 124px)); overflow: hidden; border: 1px solid #dfe5f3 !important; border-radius: 20px !important; background: #fff; box-shadow: 0 20px 50px rgba(31,38,90,.24); display: flex; flex-direction: column; }
    .group-chat-head { min-height: 82px; padding: .9rem 1.1rem; display: flex; gap: .75rem; align-items: center; justify-content: space-between; background: linear-gradient(135deg, #3857c8, #7048d7); color: #fff; }
    .group-chat-head .group-eyebrow { color: rgba(255,255,255,.72); }
    .group-chat-head strong { color: #fff; }
    .group-chat-tools { padding: .75rem 1rem; border-bottom: 1px solid #e5efed; display: grid; grid-template-columns: auto 1fr; gap: .65rem; align-items: center; background: #fff; flex-shrink: 0; }
    .group-chat-tabs { display: flex; gap: .5rem; flex-wrap: wrap; }
    .group-chat-tab { border: 1px solid #d9e8e4; background: #fff; border-radius: 999px; padding: .45rem .8rem; font-weight: 600; color: #52615e; }
    .group-chat-tab.is-active { background: #5264ce; border-color: #5264ce; color: #fff; }
    .group-chat-recipient { min-height: 42px !important; padding: .45rem .7rem !important; border-radius: 12px !important; font-size: .9rem !important; }
    .group-chat-private-button { min-height: 42px; border: 1px solid #d9e0f4; background: #fff; border-radius: 12px; padding: .45rem .7rem; display: flex; align-items: center; color: #48516b; font-weight: 700; }
    .group-chat-private-button.is-active { border-color: #5264ce; color: #5264ce; background: #f3f4ff; }
    .group-chat-contacts { padding: .75rem 1rem; border-bottom: 1px solid #e5e8f1; background: #f8f9ff; flex-shrink: 0; max-height: 220px; overflow-y: auto; }
    .group-chat-contact-search { display: flex; align-items: center; gap: .5rem; background: #fff; border: 1px solid #dfe3ef; border-radius: 12px; padding: .5rem .7rem; }
    .group-chat-contact-search input { width: 100%; border: 0; outline: 0; background: transparent; }
    .group-chat-contact-list { max-height: 130px; overflow-y: auto; margin-top: .55rem; display: grid; gap: .35rem; }
    .group-chat-contact { display: grid; grid-template-columns: 32px 1fr auto; align-items: center; gap: .55rem; width: 100%; padding: .42rem .5rem; border: 0; border-radius: 11px; background: transparent; text-align: left; }
    .group-chat-contact:hover, .group-chat-contact.is-active { background: #e9ebff; color: #4657c5; }
    .group-chat-contact .member-avatar { width: 32px; height: 32px; font-size: .8rem; }
    .group-chat-messages { flex: 1 1 0; min-height: 120px; overflow-y: auto; padding: 1rem; background: linear-gradient(180deg, #f4fbf8, #fff); }
    .group-chat-message { display: flex; margin-bottom: .75rem; }
    .group-chat-message.is-mine { justify-content: flex-end; }
    .group-chat-bubble { max-width: min(78%, 520px); background: #fff; border: 1px solid #e2ece9; border-radius: 16px; padding: .65rem .85rem; }
    .group-chat-message.is-mine .group-chat-bubble { background: linear-gradient(135deg, #5264ce, #7048d7); border-color: #5264ce; color: #fff; }
    .group-chat-compose { display: flex; gap: .6rem; padding: .8rem 1rem; border-top: 1px solid #e5efed; background: #fff; flex-shrink: 0; }
    .group-chat-compose .group-input { min-height: 44px; padding: .55rem .75rem; border-radius: 13px; }
    .group-chat-send { width: 44px; height: 44px; padding: 0 !important; border-radius: 50% !important; flex: 0 0 44px; }
    .group-chat-send { background: #5b5fd2 !important; border-color: #5b5fd2 !important; }
    .group-chat-scroll-latest { position: absolute; left: 50%; bottom: 70px; z-index: 4; width: 42px; height: 42px; border: 0; border-radius: 50%; transform: translateX(-50%); background: linear-gradient(135deg, #5264ce, #7048d7); color: #fff; box-shadow: 0 8px 20px rgba(63,75,180,.32); transition: transform .18s ease; }
    .group-chat-scroll-latest:hover { transform: translateX(-50%) scale(1.08); }
    .group-chat-read { display: block; margin-top: .35rem; text-align: right; color: rgba(255,255,255,.82); font-size: .7rem; }
    .group-chat-recipient-bar { display: flex; align-items: center; gap: .5rem; padding: .55rem 1rem; border-bottom: 1px solid #e5e8f1; background: #f3f4ff; cursor: pointer; flex-shrink: 0; transition: background .15s ease; }
    .group-chat-recipient-bar:hover { background: #e9ebff; }
    .group-chat-recipient-bar strong { font-size: .85rem; color: #3d49a6; }
    .group-chat-recipient-bar .bi-chevron-left { color: #5264ce; font-size: .75rem; }
    .group-chat-notification-stack { position: fixed; right: 24px; top: 92px; z-index: 2000; width: min(380px, calc(100vw - 32px)); display: grid; gap: .55rem; }
    .group-chat-notification { width: 100%; border: 0; display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .65rem; text-align: left; padding: .85rem 1rem; border-radius: 14px; background: #303b8f; color: #fff; box-shadow: 0 14px 38px rgba(24,30,80,.3); font-weight: 600; animation: groupChatNoticeIn .22s ease-out; }
    .group-chat-notification:hover { background: #3d49a6; transform: translateY(-1px); }
    @keyframes groupChatNoticeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 575.98px) {
        .group-chat-launcher { right: 16px; bottom: 88px; }
        .group-chat-panel { right: 16px; bottom: 92px; }
    }
    .group-order-form-head { border-top-left-radius: 24px; border-top-right-radius: 24px; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.35rem 1.5rem; border-bottom: 1px solid #e5efed; background: linear-gradient(135deg, #f1fbf8, #fff); }
    .group-order-form-body { padding: 1.5rem; position: relative; }
    .group-field-panel { height: 100%; padding: 1rem; border: 1px solid #deebe8; border-radius: 18px; background: #fbfefd; }
    .group-product-picker { position: relative; z-index: 15; }
    .group-product-search { position: relative; }
    .group-product-search > .bi-search { position: absolute; top: 50%; left: 1.25rem; z-index: 2; color: #07866f; font-size: 1.15rem; transform: translateY(-50%); pointer-events: none; }
    .group-product-search > .bi-chevron-down { position: absolute; top: 50%; right: 1.25rem; z-index: 2; color: #07866f; font-size: 1.15rem; transform: translateY(-50%); pointer-events: none; transition: transform .2s ease; }
    .group-product-picker.is-open .bi-chevron-down { transform: translateY(-50%) rotate(180deg); }
    .group-product-search .group-input { min-height: 56px; font-size: 1.05rem; padding-right: 3.2rem; padding-left: 3.2rem; border-radius: 16px; border: 1.5px solid #cce5df; background: #fff; font-weight: 600; box-shadow: 0 4px 14px rgba(7, 82, 70, .04); }
    .group-product-menu { position: absolute; top: calc(100% + .55rem); right: 0; left: 0; z-index: 1050; display: none; max-height: 420px; padding: .85rem; overflow-y: auto; border: 1.5px solid #bfe3da; border-radius: 20px; background: #fff; box-shadow: 0 24px 60px rgba(10, 73, 62, .22); }
    .group-product-picker.is-open .group-product-menu { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: .65rem; animation: group-menu-in .18s ease both; }
    .group-product-option { display: flex; align-items: center; gap: 1rem; width: 100%; padding: .8rem 1rem; border: 1.5px solid #e2ece9; border-radius: 14px; color: #172622; background: #fff; text-align: left; transition: all .16s ease; }
    .group-product-option:hover, .group-product-option:focus { color: #06735f; background: #edf9f6; border-color: #0d9373; transform: translateY(-1px); box-shadow: 0 6px 18px rgba(13, 147, 115, .12); outline: 0; }
    .group-product-option strong { display: block; font-size: 1rem; font-weight: 750; color: #172622; line-height: 1.3; }
    .group-product-option .small { color: #68807a; font-size: .78rem; margin-top: .15rem; }
    .group-product-option small.group-product-option-price { font-size: .95rem; font-weight: 800; color: #0d9373; white-space: nowrap; margin-left: auto; padding-left: .5rem; }
    .group-product-option-image { width: 64px !important; height: 64px !important; flex: 0 0 64px; padding: .25rem !important; border: 1px solid #dcece8; border-radius: 12px; object-fit: contain !important; background: #f4fbf9 !important; }
    .group-product-option-copy { min-width: 0; flex: 1 1 auto; }
    .group-product-option-copy strong { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .group-product-option-copy .small { display: block; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .group-product-option-price { margin-left: auto; }
    .group-search-empty { display: none; grid-column: 1 / -1; padding: 1.5rem; text-align: center; color: #b42318; font-size: .88rem; font-weight: 650; }
    .group-search-empty.is-visible { display: block; }
    .group-custom-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .75rem; }
    .group-submit-row { display: flex; align-items: end; gap: .85rem; }
    .group-submit-row .group-note { flex: 1 1 auto; }
    /* Selected Product Preview Card */
    .group-selected-product-card { display: flex; align-items: center; gap: .85rem; padding: .65rem .85rem; border: 1.5px solid #cbe6df; border-radius: 14px; background: #f4fbf9; transition: all .2s ease; }
    .group-selected-product-thumb { width: 52px; height: 52px; border-radius: 10px; object-fit: cover; background: #fff; border: 1px solid #d9ebe6; padding: 2px; flex-shrink: 0; }
    .group-selected-product-info { display: flex; flex-direction: column; min-width: 0; }
    .group-selected-product-name { font-weight: 750; color: #172622; font-size: .95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .group-selected-product-price { font-weight: 800; color: #0d9373; font-size: .92rem; }
    /* Size Segmented Control */
    .group-size-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: .6rem; }
    .group-size-pill { position: relative; margin: 0; cursor: pointer; }
    .group-size-radio { position: absolute; opacity: 0; pointer-events: none; }
    .group-size-btn { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: .65rem .4rem; border: 1.5px solid #d9e5e2; border-radius: 14px; background: #fff; transition: all .18s ease; text-align: center; user-select: none; min-height: 68px; }
    .group-size-btn .size-letter { font-weight: 800; font-size: 1.15rem; color: #172622; line-height: 1.2; }
    .group-size-btn .size-sub { font-size: .78rem; font-weight: 600; color: #6d7f7a; margin-top: 2px; }
    .group-size-pill:hover .group-size-btn { border-color: #0d9373; background: #f8fdfc; }
    .group-size-radio:checked + .group-size-btn { border-color: #0d9373; background: #ecfdf5; box-shadow: 0 4px 14px rgba(13, 147, 115, .15); }
    .group-size-radio:checked + .group-size-btn .size-letter { color: #065f46; }
    .group-size-radio:checked + .group-size-btn .size-sub { color: #0d9373; font-weight: 700; }

    /* Sugar & Ice Pills matching Quick Modal */
    .group-level-pills-row { display: flex; flex-wrap: wrap; gap: .45rem; }
    .group-level-choice { position: relative; margin: 0; cursor: pointer; user-select: none; }
    .group-level-choice input[type="radio"] { position: absolute; opacity: 0; pointer-events: none; }
    .group-level-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 52px; padding: .42rem .95rem; border: 1.5px solid #d9e5e2; border-radius: 999px; background: #ffffff; color: #172622; font-size: .88rem; font-weight: 700; transition: all .16s ease; text-align: center; white-space: nowrap; }
    .group-level-choice:hover .group-level-btn { border-color: #0d9373; background: #f4fbf9; color: #067a5f; }
    .group-level-choice input[type="radio"]:checked + .group-level-btn { border-color: #0d9373; background: #ecfdf5; color: #067a5f; box-shadow: 0 2px 10px rgba(13, 147, 115, .15); font-weight: 800; }

    /* Form select & Stepper */
    .group-form-select { min-height: 44px; border: 1.5px solid #d9e5e2; border-radius: 12px; background-color: #fff; font-size: .84rem; font-weight: 600; color: #1a2f2b; transition: border-color .18s ease, box-shadow .18s ease; }
    .group-form-select:focus { border-color: #0d9373; box-shadow: 0 0 0 .2rem rgba(13, 147, 115, .12); }
    .group-qty-widget { display: flex; align-items: center; width: 120px; height: 46px; border: 1.5px solid #d9e5e2; border-radius: 999px; background: #fff; overflow: hidden; flex-shrink: 0; }
    .group-qty-btn { display: flex; align-items: center; justify-content: center; width: 40px; height: 100%; border: 0; background: transparent; color: #0d9373; font-size: 1.25rem; font-weight: 700; cursor: pointer; transition: background .15s ease; }
    .group-qty-btn:hover { background: #ecfdf5; }
    .group-qty-input { width: 100%; height: 100%; border: 0 !important; background: transparent !important; text-align: center; font-weight: 800; font-size: 1.05rem; color: #172622; box-shadow: none !important; padding: 0 !important; }

    /* Toppings Grid like Product Detail */
    .group-toppings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(175px, 1fr)); gap: .65rem; }
    .group-topping-card { display: flex; align-items: center; gap: .65rem; margin: 0; padding: .65rem .85rem; border: 1.5px solid #e2ece9; border-radius: 12px; background: #fff; cursor: pointer; transition: all .18s ease; user-select: none; }
    .group-topping-card:hover { border-color: #0d9373; background: #f8fdfc; transform: translateY(-1px); }
    .group-topping-card:has(input:checked) { border-color: #0d9373; background: #ecfdf5; box-shadow: 0 4px 14px rgba(13, 147, 115, .12); }
    .group-topping-check { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid #a3c9bf; flex-shrink: 0; }
    .group-topping-card:has(input:checked) .group-topping-check { background-color: #0d9373; border-color: #0d9373; }
    .group-topping-meta { display: flex; flex-direction: column; min-width: 0; }
    .group-topping-title { font-size: .84rem; font-weight: 750; color: #1a2f2b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .group-topping-card:has(input:checked) .group-topping-title { color: #065f46; }
    .group-topping-price { font-size: .74rem; font-weight: 700; color: #0d9373; }

    .group-topping { display: inline-flex; align-items: center; gap: .6rem; margin: 0; padding: .72rem .9rem; border: 1px solid #dbe8e5; border-radius: 13px; background: #fff; cursor: pointer; transition: border-color .18s ease, background .18s ease, transform .18s ease; }
    .group-topping:hover { border-color: #8fd2c3; transform: translateY(-1px); }
    .group-topping:has(input:checked) { border-color: #0a9b80; color: #06735f; background: #eaf9f5; box-shadow: 0 7px 16px rgba(10, 155, 128, .1); }
    .member-card { height: 100%; overflow: hidden; }
    .member-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; background: #f4faf8; border-bottom: 1px solid #e1ece9; }
    .member-avatar { display: grid; place-items: center; width: 38px; height: 38px; flex: 0 0 auto; border-radius: 50%; color: #087560; background: #d9f4ed; font-weight: 850; }
    .member-item { display: flex; gap: .85rem; align-items: center; padding: 1rem 1.15rem; border-bottom: 1px solid #edf2f1; }
    .member-item:last-child { border-bottom: 0; }
    .group-item-actions { display: flex; align-items: center; justify-content: flex-end; gap: .6rem; margin-top: .5rem; }
    .group-quantity-stepper { display: flex; flex: 0 0 102px; flex-wrap: nowrap; align-items: center; width: 102px; height: 36px; overflow: hidden; border: 1px solid #cfe6df; border-radius: 999px; background: #f8fcfb; box-shadow: 0 4px 12px rgba(8, 117, 96, .06); white-space: nowrap; }
    .group-quantity-stepper form { display: block; flex: 0 0 34px; width: 34px !important; min-width: 34px; margin: 0; }
    .group-stepper-button { display: inline-grid; place-items: center; width: 34px; height: 34px; padding: 0; border: 0; color: #527069; background: transparent; transition: background .18s ease, color .18s ease; }
    .group-stepper-button:hover { color: #087560; background: #e7f7f2; }
    .group-stepper-button.is-add { color: #07866f; }
    .group-stepper-value { display: grid; flex: 0 0 32px; place-items: center; width: 32px; min-width: 32px; color: #173c34; font-weight: 800; font-variant-numeric: tabular-nums; }
    .group-item-action { display: inline-grid; place-items: center; width: 34px; height: 34px; padding: 0; border: 1px solid #d7e8e4; border-radius: 50%; background: #fff; transition: transform .18s ease, box-shadow .18s ease, background .18s ease; }
    .group-item-action:hover { transform: translateY(-1px); box-shadow: 0 6px 14px rgba(8, 117, 96, .14); }
    .group-item-action.is-edit { color: #087560; border-color: #a3d9cf; background: #f0fdf9; }
    .group-item-action.is-remove { color: #dc3545; border-color: #f3c5ca; background: #fff7f7; }
    .group-live-toast { position: fixed; top: 88px; right: 22px; z-index: 1080; max-width: min(420px, calc(100vw - 32px)); padding: .85rem 1.15rem; border: 1.5px solid #bde4d9; border-radius: 14px; color: #05634f; background: #effbf7; box-shadow: 0 16px 35px rgba(10, 80, 66, .18); font-weight: 700; font-size: .88rem; display: flex; align-items: center; animation: group-menu-in .2s ease both; }
    .group-live-toast.is-error { color: #a61b1b; border-color: #fecaca; background: #fff1f2; }
    .group-live-toast.is-warning { color: #92400e; border-color: #fde68a; background: #fffbeb; box-shadow: 0 16px 35px rgba(180, 83, 9, .14); }
    .member-item-image { width: 58px; height: 58px; flex: 0 0 auto; border-radius: 13px; object-fit: cover; background: #edf7f4; }
    .group-summary { position: sticky; bottom: 1rem; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.35rem; border: 1px solid #cbe6df; border-radius: 20px; background: rgba(255,255,255,.96); box-shadow: 0 18px 50px rgba(9, 94, 77, .16); backdrop-filter: blur(12px); }
    .empty-group { padding: 4rem 1rem; text-align: center; }
    .empty-group-icon { display: grid; place-items: center; width: 70px; height: 70px; margin: 0 auto 1rem; border-radius: 22px; color: #07836d; background: #dff6f0; font-size: 1.8rem; }
    @keyframes group-countdown-pulse { 50% { transform: scale(1.035); box-shadow: 0 0 0 6px rgba(249, 115, 22, .08); } }
    @keyframes group-expire-pop { 0% { transform: scale(.94); opacity: .55; } 60% { transform: scale(1.06); } 100% { transform: scale(1); opacity: 1; } }
    @keyframes group-menu-in { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    @media (max-width: 991.98px) {
        .group-hero > .d-flex {
            display: grid !important;
            grid-template-columns: minmax(0, 1fr) minmax(260px, 340px);
            align-items: center !important;
            gap: 1rem !important;
        }
        .group-hero > .d-flex > * { min-width: 0; }
        .group-hero h1 { font-size: clamp(1.55rem, 4vw, 2.1rem); line-height: 1.08; }
        .group-hero p { font-size: .78rem; line-height: 1.45; }
        .group-share { width: 100%; min-width: 0; }
        .group-share input { min-width: 0; }
    }
    @media (max-width: 767.98px) {
        .group-page { padding: 1rem 0 6rem; }
        .group-page .group-shell { --bs-gutter-x: 1.25rem; }
        .group-card { border-radius: 18px; box-shadow: 0 10px 28px rgba(16, 94, 80, .07); }
        .group-hero { padding: 1.1rem; border-radius: 18px; }
        .group-hero::after { width: 190px; height: 190px; right: -90px; top: -90px; }
        .group-hero > .d-flex { grid-template-columns: minmax(0, 1fr); align-items: stretch !important; gap: .85rem !important; }
        .group-hero > .d-flex > div:first-child > .d-flex { gap: .45rem !important; margin-bottom: .65rem !important; }
        .group-hero h1 { margin-bottom: .45rem !important; font-size: clamp(1.3rem, 6vw, 1.7rem); line-height: 1.12; letter-spacing: -.025em; }
        .group-hero p { margin-bottom: .25rem !important; font-size: .72rem; line-height: 1.4; }
        .group-code { padding: .32rem .5rem; border-radius: 8px; font-size: .66rem; letter-spacing: .08em; }
        .group-hero .group-status { padding: .32rem .5rem; font-size: .64rem; }
        .group-share { min-width: 100%; }
        .group-share-row { align-items: stretch; flex-direction: column; }
        .group-share-row .btn { width: 100%; }
        .group-order-form { margin-bottom: 1.25rem !important; }
        .group-order-form-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: .7rem;
            padding: .9rem 1rem;
        }
        .group-order-form-head .group-section-title { font-size: 1.05rem; line-height: 1.25; }
        .group-order-form-head .group-eyebrow { margin-bottom: .2rem !important; font-size: .66rem; }
        .group-order-form-head .group-status { max-width: 104px; justify-content: center; padding: .38rem .55rem; font-size: .64rem; line-height: 1.2; text-align: center; }
        .group-create-form { padding: 1.3rem; }
        .group-create-form-head { align-items: flex-start; flex-direction: column; margin-bottom: 1.1rem; }
        .group-create-step { white-space: normal; }
        .group-create-hero { min-height: auto; padding: 1.5rem; }
        .group-create-benefits { margin-top: 1.25rem; }
        .group-datetime-popover { position: fixed; top: 50%; right: auto; left: 50%; max-height: calc(100vh - 32px); overflow-y: auto; transform: translate(-50%, -50%); animation: none; }
        .group-branch-menu { position: fixed; top: 50%; right: auto; left: 50%; width: min(420px, calc(100vw - 32px)); max-height: calc(100vh - 32px); transform: translate(-50%, -50%); animation: none; }
        .group-order-form-body { padding: .8rem; }
        .group-order-form-body > .row { --bs-gutter-x: .65rem; --bs-gutter-y: .65rem; margin-bottom: .65rem !important; }
        .group-field-panel { padding: .75rem; border-radius: 14px; }
        .group-form-label { margin-bottom: .38rem; font-size: .75rem; }
        .group-input { min-height: 44px; border-radius: 12px; }
        .group-product-search > .bi-search { left: .8rem; }
        .group-product-search > .bi-chevron-down { right: .8rem; }
        .group-product-search .group-input { padding-right: 2.35rem; padding-left: 2.35rem; font-size: 16px; }
        .group-product-menu { position: fixed; top: 50%; right: auto; bottom: auto; left: 50%; width: min(420px, calc(100vw - 24px)); max-height: min(62dvh, 440px); transform: translate(-50%, -50%); }
        .group-product-picker.is-open .group-product-menu { animation: none; }
        .group-product-option { gap: .6rem; padding: .55rem; }
        .group-product-option-image { width: 48px !important; height: 48px !important; flex-basis: 48px; }
        .group-product-option-copy strong { font-size: .8rem; }
        .group-product-option-copy .small, .group-product-option-price { font-size: .66rem; }
        .group-custom-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .group-custom-grid { gap: .6rem; }
        .group-submit-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; align-items: end; gap: .6rem; }
        .group-submit-row .group-note { min-width: 0; }
        .group-add-button { width: 100%; min-width: 0; }
        .group-option-box { padding: .75rem; border-radius: 14px; }
        .group-option-box > p { margin-bottom: .65rem !important; font-size: .7rem; }
        .group-topping { padding: .55rem .65rem; border-radius: 11px; font-size: .72rem; }
        [data-group-order-heading] { align-items: flex-start !important; gap: .35rem !important; margin-bottom: .65rem !important; }
        [data-group-order-heading] .group-section-title { font-size: 1rem; }
        [data-group-order-heading] > span { font-size: .7rem; }
        [data-group-members] { --bs-gutter-x: .7rem; --bs-gutter-y: .7rem; margin-bottom: 1rem !important; }
        .member-head { padding: .75rem .85rem; }
        .member-avatar { width: 34px; height: 34px; }
        .member-item { display: grid; grid-template-columns: 48px minmax(0, 1fr) auto; align-items: start; gap: .65rem; padding: .75rem .85rem; }
        .member-item-image { width: 48px; height: 48px; border-radius: 11px; }
        .member-item .text-secondary { font-size: .68rem; line-height: 1.35; }
        .group-item-actions { gap: .4rem; }
        .group-summary {
            position: sticky;
            bottom: max(.5rem, env(safe-area-inset-bottom));
            z-index: 1040;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            align-items: center;
            gap: .65rem;
            padding: .7rem .8rem;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(9, 94, 77, .18);
        }
        .group-summary > div:first-child { min-width: 76px; }
        .group-summary > div:first-child small { font-size: .65rem; line-height: 1.2; }
        .group-summary > div:first-child strong { font-size: 1.2rem !important; line-height: 1.15; }
        .group-summary > .d-flex { display: grid !important; grid-template-columns: auto minmax(0, 1fr); gap: .45rem !important; min-width: 0; }
        .group-summary > .d-flex:has(form:only-child),
        .group-summary > .d-flex:not(:has(form)) { grid-template-columns: 1fr; }
        .group-summary > .d-flex form { min-width: 0; }
        .group-summary .group-btn { width: 100%; min-height: 42px; padding: .5rem .65rem; font-size: .72rem; line-height: 1.15; white-space: nowrap; }
        body:has(.group-page) .client-chatbox { right: .75rem !important; bottom: 5.75rem !important; }
        .group-live-toast { top: 76px; right: 16px; left: 16px; }
    }

    @media (max-width: 575.98px) {
        .group-page { padding-top: .65rem; }
        .group-page .group-shell { --bs-gutter-x: .9rem; }
        .group-hero { margin-bottom: .8rem !important; padding: .85rem; border-radius: 15px; }
        .group-hero h1 { font-size: 1.25rem; }
        .group-hero p { font-size: .67rem; }
        .group-hero .text-white-50 { color: rgba(255,255,255,.76) !important; }
        .group-share { padding: .65rem; border-radius: 12px; }
        .group-share label { margin-bottom: .4rem !important; font-size: .68rem; }
        .group-share-row { gap: .4rem; }
        .group-share-row .group-input { min-height: 40px; padding: .45rem .6rem; border-radius: 10px; font-size: 16px; }
        .group-share-row .group-btn { min-height: 40px; padding: .45rem .7rem; font-size: .7rem; }
        .group-share > small { margin-top: .4rem !important; font-size: .6rem; line-height: 1.35; }
        .group-order-form-head { padding: .75rem .8rem; }
        .group-order-form-head .group-status { max-width: 82px; padding: .32rem .42rem; font-size: .58rem; }
        .group-order-form-body { padding: .65rem; }
        .group-field-panel { padding: .65rem; border-radius: 13px; }
        .group-custom-grid { gap: .5rem; }
        .group-submit-row { grid-template-columns: 1fr; }
        .group-add-button { min-height: 44px; }
        .group-summary { grid-template-columns: 72px minmax(0, 1fr); padding: .62rem .7rem; }
        .group-summary > .d-flex { grid-template-columns: minmax(82px, auto) minmax(0, 1fr); }
        .group-summary .group-btn { padding-inline: .45rem; font-size: .66rem; }
        .group-summary .group-btn i { margin-right: .3rem !important; }
        body:has(.group-page) .client-chatbox { bottom: 5.35rem !important; }
        body:has(.group-page) .client-chatbox > button:first-of-type { width: 48px; height: 48px; }
    }

    @media (max-width: 374.98px) {
        .group-hero { padding: .7rem; }
        .group-hero > .d-flex { gap: .65rem !important; }
        .group-hero > .d-flex > div:first-child > .d-flex { align-items: flex-start !important; }
        .group-hero h1 { font-size: 1.08rem; line-height: 1.15; }
        .group-hero p { font-size: .61rem; line-height: 1.35; }
        .group-code, .group-hero .group-status { font-size: .56rem; }
        .group-share { padding: .55rem; }
        .group-share-row .group-input { min-height: 38px; }
        .group-share-row .group-btn { min-height: 38px; }
        .group-order-form-head { grid-template-columns: 1fr; }
        .group-order-form-head .group-status { max-width: none; width: fit-content; }
        .group-summary { grid-template-columns: 1fr; }
        .group-summary > div:first-child { display: flex; align-items: center; justify-content: space-between; }
        .group-summary > .d-flex { grid-template-columns: 1fr 1.5fr; }
        body:has(.group-page) .client-chatbox { bottom: 7.2rem !important; }
    }

    @media (max-width: 319.98px) {
        .group-page .group-shell { --bs-gutter-x: .65rem; }
        .group-hero { padding: .6rem; border-radius: 12px; }
        .group-hero > .d-flex > div:first-child > .d-flex { flex-direction: column; }
        .group-hero h1 { font-size: 1rem; }
        .group-share-row { gap: .32rem; }
        .group-custom-grid { grid-template-columns: 1fr; }
        .member-item { grid-template-columns: 42px minmax(0, 1fr); }
        .member-item-image { width: 42px; height: 42px; }
        .member-item > .text-end { grid-column: 2; text-align: left !important; }
        .group-item-actions { justify-content: flex-start; }
        .group-summary > .d-flex { grid-template-columns: 1fr; }
        body:has(.group-page) .client-chatbox { bottom: 9.5rem !important; }
    }
</style>
