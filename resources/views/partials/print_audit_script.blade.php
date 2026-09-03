<script>
    function selectPrintOrganization(organizationId) {
        const url = new URL(window.location.href);
        url.searchParams.set('organization_id', organizationId);
        window.location.href = url.toString();
    }

    function recordPrint(documentType, printableId) {
        // Open the print dialog inside the click's user gesture. If it runs after
        // an awaited fetch, Chrome drops the gesture and the Save-as-PDF file name
        // (taken from document.title) comes out blank. The audit log is recorded
        // as a best-effort side call.
        fetch(@json(route('print-logs.store')), {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
            body: JSON.stringify({organization_id: {{ $selectedOrganization->id }}, document_type: documentType, printable_id: printableId}),
            keepalive: true
        }).catch(() => {});

        window.print();
    }
</script>
