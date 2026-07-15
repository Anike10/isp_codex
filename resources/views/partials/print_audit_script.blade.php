<script>
    function selectPrintOrganization(organizationId) {
        const url = new URL(window.location.href);
        url.searchParams.set('organization_id', organizationId);
        window.location.href = url.toString();
    }

    async function recordPrint(documentType, printableId) {
        const button = window.event?.currentTarget;
        if (button) button.disabled = true;
        try {
            const response = await fetch(@json(route('print-logs.store')), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token())},
                body: JSON.stringify({organization_id: {{ $selectedOrganization->id }}, document_type: documentType, printable_id: printableId})
            });
            if (!response.ok) throw new Error('Print history could not be saved.');
            window.print();
        } catch (error) {
            alert(error.message || 'Print history could not be saved.');
        } finally {
            if (button) button.disabled = false;
        }
    }
</script>
