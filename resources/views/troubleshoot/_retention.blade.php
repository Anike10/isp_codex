<form method="post" action="{{ route('troubleshoot.retention') }}" class="card" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:14px">
    @csrf
    @method('patch')
    <span class="muted" style="font-weight:700">Old data</span>
    <label class="muted" style="display:flex;gap:6px;align-items:center;font-size:12px">
        Auto-delete disconnect-log rows older than
        <input type="number" name="retention_days" min="0" max="3650" value="{{ $retentionDays }}" style="width:90px">
        day(s)
    </label>
    <button class="btn light" type="submit">Save</button>
    <button class="btn light" type="submit" name="action" value="prune">Delete now</button>
    <span class="muted" style="font-size:12px">Applies to every Troubleshoot report that reads the PPP disconnect log. <strong>0 = keep forever.</strong> Runs nightly.</span>
</form>
