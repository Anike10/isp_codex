<style>
    .router-form { display:grid; gap:16px; max-width:960px; }
    .rf-card { background:var(--panel); border:1px solid var(--line); border-radius:12px; padding:20px 22px; }
    .rf-card__head { margin-bottom:16px; }
    .rf-card__head h2 { margin:0; font-size:16px; letter-spacing:.01em; }
    .rf-card__sub { margin:4px 0 0; color:var(--muted); font-size:13px; }
    .rf-grid { display:grid; grid-template-columns:repeat(auto-fit, minmax(240px, 1fr)); gap:16px 18px; }
    .rf-field { display:flex; flex-direction:column; gap:6px; min-width:0; }
    .rf-field--full { grid-column:1 / -1; }
    .rf-field label { margin:0; font-weight:700; font-size:13px; }
    .rf-field input, .rf-field select, .rf-field textarea { border:1px solid var(--line); border-radius:8px; padding:10px 12px; background:#fff; transition:border-color .12s ease, box-shadow .12s ease; }
    .rf-field input:focus, .rf-field select:focus, .rf-field textarea:focus { outline:none; border-color:var(--brand); box-shadow:0 0 0 3px rgba(17, 97, 73, .12); }
    .rf-field textarea { min-height:96px; resize:vertical; }
    .rf-hint { color:var(--muted); font-size:12px; line-height:1.5; }
    .rf-hint code { background:var(--zebra); padding:1px 5px; border-radius:4px; font-size:11px; }
    .rf-input-suffix { position:relative; }
    .rf-input-suffix input { width:100%; padding-right:58px; }
    .rf-input-suffix span { position:absolute; right:12px; top:50%; transform:translateY(-50%); color:var(--muted); font-size:12px; font-weight:700; pointer-events:none; }
    .rf-check { grid-column:1 / -1; display:flex; gap:12px; align-items:flex-start; padding:12px 14px; border:1px solid var(--line); border-radius:8px; background:var(--zebra-soft); }
    .rf-check input { width:18px; height:18px; margin:2px 0 0; flex:0 0 auto; accent-color:var(--brand); }
    .rf-check div strong { display:block; font-size:13px; }
    .rf-check div .rf-hint { margin-top:3px; }
    .rf-actions { position:sticky; bottom:0; z-index:5; display:flex; gap:12px; align-items:center; justify-content:flex-end; padding:14px 18px; background:var(--panel); border:1px solid var(--line); border-radius:12px; box-shadow:0 -6px 18px rgba(15, 23, 42, .06); }
    .rf-actions .rf-spacer { margin-right:auto; color:var(--muted); font-size:12px; }
    @media (max-width:640px) {
        .rf-card { padding:16px; }
        .rf-actions { flex-wrap:wrap; }
        .rf-actions .btn { width:100%; justify-content:center; }
        .rf-actions .rf-spacer { width:100%; margin:0 0 4px; }
    }
</style>
