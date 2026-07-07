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
    .group-topping { display: inline-flex; align-items: center; gap: .55rem; margin: 0; padding: .7rem .85rem; border: 1px solid #dbe8e5; border-radius: 12px; background: #fff; cursor: pointer; }
    .group-topping:has(input:checked) { border-color: #0a9b80; color: #06735f; background: #eaf9f5; }
    .member-card { height: 100%; overflow: hidden; }
    .member-head { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1rem 1.15rem; background: #f4faf8; border-bottom: 1px solid #e1ece9; }
    .member-avatar { display: grid; place-items: center; width: 38px; height: 38px; flex: 0 0 auto; border-radius: 50%; color: #087560; background: #d9f4ed; font-weight: 850; }
    .member-item { display: flex; gap: .85rem; align-items: center; padding: 1rem 1.15rem; border-bottom: 1px solid #edf2f1; }
    .member-item:last-child { border-bottom: 0; }
    .member-item-image { width: 58px; height: 58px; flex: 0 0 auto; border-radius: 13px; object-fit: cover; background: #edf7f4; }
    .group-summary { position: sticky; bottom: 1rem; z-index: 5; display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: 1.15rem 1.35rem; border: 1px solid #cbe6df; border-radius: 20px; background: rgba(255,255,255,.96); box-shadow: 0 18px 50px rgba(9, 94, 77, .16); backdrop-filter: blur(12px); }
    .empty-group { padding: 4rem 1rem; text-align: center; }
    .empty-group-icon { display: grid; place-items: center; width: 70px; height: 70px; margin: 0 auto 1rem; border-radius: 22px; color: #07836d; background: #dff6f0; font-size: 1.8rem; }
    @media (max-width: 767.98px) {
        .group-page { padding-top: 2rem; }
        .group-hero { padding: 1.35rem; }
        .group-share { min-width: 100%; }
        .group-share-row, .group-summary { align-items: stretch; flex-direction: column; }
        .group-share-row .btn, .group-summary .btn { width: 100%; }
    }
</style>
