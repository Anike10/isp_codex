<style>
    .searchable-select-native {
        position:absolute !important;
        width:1px !important;
        height:1px !important;
        margin:0 !important;
        padding:0 !important;
        overflow:hidden !important;
        clip:rect(0 0 0 0) !important;
        white-space:nowrap !important;
        border:0 !important;
        opacity:0 !important;
        pointer-events:none !important;
    }
    .searchable-select { position:relative; width:100%; min-width:0; }
    .searchable-select.is-compact { width:min(210px, 100%); min-width:130px; }
    .searchable-select.is-open { z-index:190; }
    .searchable-select-control { position:relative; display:flex; width:100%; min-width:0; }
    .searchable-select-input { width:100%; min-width:0; padding-right:40px; background:#fff; }
    .searchable-select-input:focus { border-color:var(--accent); outline:3px solid rgba(29, 118, 201, .13); }
    .searchable-select-toggle {
        position:absolute;
        top:1px;
        right:1px;
        bottom:1px;
        width:36px;
        padding:0;
        border:0;
        border-left:1px solid var(--line);
        border-radius:0 5px 5px 0;
        color:#475467;
        background:#f8fafc;
        cursor:pointer;
        font-size:15px;
    }
    .searchable-select-toggle:hover { background:#eef4fb; }
    .searchable-select-menu {
        position:absolute;
        top:calc(100% + 5px);
        right:0;
        left:0;
        z-index:200;
        min-width:min(320px, calc(100vw - 24px));
        max-height:280px;
        overflow-y:auto;
        overscroll-behavior:contain;
        padding:6px;
        border:1px solid var(--line);
        border-radius:7px;
        background:#fff;
        box-shadow:0 18px 38px rgba(15, 23, 42, .2);
    }
    .searchable-select-menu[hidden] { display:none; }
    .searchable-select-option {
        display:block;
        width:100%;
        padding:9px 10px;
        border:0;
        border-radius:5px;
        color:var(--ink);
        background:transparent;
        cursor:pointer;
        font:inherit;
        font-size:14px;
        line-height:1.3;
        text-align:left;
    }
    .searchable-select-option:hover,
    .searchable-select-option.is-active { color:#0b4f3c; background:#e9f7f1; }
    .searchable-select-option.is-selected { font-weight:800; }
    .searchable-select-empty,
    .searchable-select-more { padding:9px 10px; color:var(--muted); font-size:12px; }
    .searchable-select.is-disabled .searchable-select-input,
    .searchable-select.is-disabled .searchable-select-toggle { cursor:not-allowed; opacity:.65; background:#f2f4f7; }
    @media (max-width: 560px) {
        .searchable-select-menu { min-width:100%; max-width:calc(100vw - 24px); }
    }
</style>
