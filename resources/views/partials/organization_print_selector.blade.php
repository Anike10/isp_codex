<label class="print-option" style="gap:8px">
    <span>Organization</span>
    <select id="printOrganization" style="width:auto;min-width:210px;padding:7px;border:1px solid #cfd7e3;border-radius:5px;background:#fff" onchange="selectPrintOrganization(this.value)">
        @foreach ($organizations as $organizationOption)
            <option value="{{ $organizationOption->id }}" @selected($selectedOrganization->is($organizationOption))>{{ $organizationOption->name }}</option>
        @endforeach
    </select>
</label>
