@extends('layouts.app')

@section('main_class', 'customer-show')
@section('content')

@php
    $canOpenInvoices = auth()->user()?->hasPermission('manage_invoices');
    $canOpenWarrantyClaims = auth()->user()?->hasPermission('view_warranty_claims')
        || auth()->user()?->hasPermission('manage_warranty_claims')
        || auth()->user()?->hasPermission('manage_products');

    $authUser = auth()->user();
    $canGrantGrace = (bool) $authUser?->hasPermission('grant_grace_period');
    $canOverrideValidity = (bool) $authUser?->hasPermission('override_service_validity');
    $canQuickActivate = (bool) $authUser?->hasPermission('quick_activate_service');
    $canForceStatus = (bool) $authUser?->hasPermission('force_service_status');

    $isSpecial = (bool) $customer->never_suspend;
    $activeUntil = $isSpecial ? null : $customer->activeUntil();
    $daysRemaining = $isSpecial ? null : $customer->activeDaysRemaining();
    $totalDue = (float) $customer->invoices->sum('due_amount');
    $netBalance = (float) $customer->account_balance - $totalDue;
    $serviceSubscription = $customer->activeSubscription ?: $customer->subscriptions->sortByDesc('id')->first();
    $assignedRouters = $customer->mikrotikRouters;
    if ($assignedRouters->isEmpty() && $customer->mikrotikRouter) {
        $assignedRouters = collect([$customer->mikrotikRouter]);
    }
    $assignedRouterIds = $assignedRouters->pluck('id')->map(fn ($id) => (int) $id)->all();

    if (empty($assignedRouterIds) && $customer->mikrotik_router_id) {
        $assignedRouterIds = [(int) $customer->mikrotik_router_id];
    }

    $servicePackage = $serviceSubscription?->package;
    $serviceListPrice = (float) ($servicePackage?->monthly_price ?? 0);
    $serviceEffectivePrice = $serviceSubscription ? $serviceSubscription->effectivePrice() : 0.0;
    $serviceHasSpecialPrice = (bool) $serviceSubscription?->hasCustomPrice();
    $isActive = $customer->status === 'active';

    $routerTargetsExists = $customer->mikrotik_username || $customer->connection_id;
    if (empty($assignedRouterIds) && $assignedRouters->isNotEmpty()) {
        $assignedRouterIds = $assignedRouters->pluck('id')->map(fn ($id) => (int) $id)->all();
    }

    $daysLeftLabel = match (true) {
        $isSpecial => 'No auto suspension',
        $daysRemaining === null => 'No active validity found',
        $daysRemaining < 0 => 'Expired '.abs($daysRemaining).' day(s) ago',
        $daysRemaining === 0 => 'Last day',
        default => $daysRemaining.' day(s) remaining',
    };
    $validityTone = match (true) {
        $isSpecial => 'success',
        $daysRemaining === null => 'neutral',
        $daysRemaining < 0 => 'danger',
        $daysRemaining <= 3 => 'warning',
        default => 'success',
    };

    $roleBadges = collect();
    if ($customer->is_customer) {
        $roleBadges->push(['label' => 'Customer', 'class' => 'active']);
    }
    if ($customer->is_reseller) {
        $roleBadges->push(['label' => 'Reseller', 'class' => 'overdue']);
    }
    if ($customer->is_vendor) {
        $roleBadges->push(['label' => 'Vendor', 'class' => 'pending']);
    }

    $partyNoteEvents = collect();
    $rawPartyNote = trim((string) $customer->notes);

    if ($rawPartyNote !== '') {
        $timestampPattern = '(?:\d{2}\/\d{2}\/\d{4}|\d{4}-\d{2}-\d{2})\s+\d{2}:\d{2}(?::\d{2})?';
        $noteSegments = preg_split('/(?=\['.$timestampPattern.'\])/', $rawPartyNote, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($noteSegments as $segmentIndex => $segment) {
            $segment = trim($segment);
            if ($segment === '') {
                continue;
            }

            $content = $segment;
            $recordedAt = null;
            $recordedText = null;

            if (preg_match('/^\[('.$timestampPattern.')\]\s*(.*)$/s', $segment, $timestampMatch)) {
                $recordedText = $timestampMatch[1];
                $content = trim($timestampMatch[2]);
            } elseif (preg_match('/\bat\s+('.$timestampPattern.')(?:\R|$)/i', $segment, $timestampMatch)) {
                $recordedText = $timestampMatch[1];
            }

            if ($recordedText) {
                foreach (['d/m/Y H:i:s', 'd/m/Y H:i', 'Y-m-d H:i:s', 'Y-m-d H:i'] as $dateFormat) {
                    try {
                        $candidate = \Carbon\Carbon::createFromFormat($dateFormat, $recordedText);
                        if ($candidate !== false) {
                            $recordedAt = $candidate;
                            break;
                        }
                    } catch (\Throwable) {
                        // Try the next supported timestamp format.
                    }
                }
            }

            $event = [
                'title' => 'Party note',
                'tone' => 'note',
                'message' => $content,
                'facts' => [],
                'recorded_at' => $recordedAt,
                'sort_at' => (($recordedAt?->timestamp ?? 0) * 1000) + $segmentIndex,
            ];

            if (str_starts_with(strtolower($content), 'imported from mikrotik')) {
                $event['title'] = 'MikroTik import';
                $event['tone'] = 'import';
                $event['message'] = 'Party information was imported from the router.';
                $importLines = preg_split('/\R+/', $content, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $importHeader = trim((string) array_shift($importLines));

                if (preg_match('/^Imported from MikroTik:\s*(.*?)\s*\(([^)]+)\)\s+at\s+(.+)$/i', $importHeader, $importMatch)) {
                    $event['facts'][] = ['label' => 'Router', 'value' => trim($importMatch[1])];
                    $event['facts'][] = ['label' => 'IP / port', 'value' => trim($importMatch[2])];
                } else {
                    $event['facts'][] = ['label' => 'Source', 'value' => $importHeader];
                }

                foreach ($importLines as $importLine) {
                    if (preg_match('/^([^:]+):\s*(.*)$/', trim($importLine), $importFact)) {
                        $event['facts'][] = ['label' => trim($importFact[1]), 'value' => trim($importFact[2]) ?: 'Not provided'];
                    }
                }
            } elseif (str_starts_with(strtolower($content), 'paid validity:')) {
                $event['title'] = 'Payment & validity';
                $event['tone'] = 'payment';
                $event['message'] = 'Paid service validity was updated.';
                $paymentText = trim((string) preg_replace('/^Paid validity:\s*/i', '', $content));
                $paymentParts = preg_split('/;\s*|(?<=\.)\s+(?=Payment note:)/i', $paymentText, -1, PREG_SPLIT_NO_EMPTY) ?: [];

                foreach ($paymentParts as $paymentPart) {
                    $paymentPart = trim($paymentPart, " \t\n\r\0\x0B.");
                    $label = 'Details';
                    $value = $paymentPart;

                    foreach ([
                        '/^payment date\s+(.+)$/i' => 'Payment date',
                        '/^one-month period\s+(.+)$/i' => 'Billing period',
                        '/^grace deducted\s+(.+)$/i' => 'Grace deducted',
                        '/^validity\s+(.+)$/i' => 'Service validity',
                        '/^Payment note:\s*(.+)$/i' => 'Payment note',
                    ] as $paymentPattern => $paymentLabel) {
                        if (preg_match($paymentPattern, $paymentPart, $paymentMatch)) {
                            $label = $paymentLabel;
                            $value = trim($paymentMatch[1]);
                            break;
                        }
                    }

                    $event['facts'][] = ['label' => $label, 'value' => $value];
                }
            } elseif (preg_match('/^Bulk activated until\s+([^\s]+)\s+for customer with no paid month\.?$/i', $content, $bulkMatch)) {
                $event['title'] = 'Bulk activation';
                $event['tone'] = 'activation';
                $event['message'] = 'Service was activated through a bulk action.';
                $event['facts'] = [
                    ['label' => 'Valid until', 'value' => $bulkMatch[1]],
                    ['label' => 'Reason', 'value' => 'No paid month was found'],
                ];
            } elseif (preg_match('/^Activated package to\s+([^\s]+)\s+via\s+(.+)\.?$/i', $content, $activationMatch)) {
                $event['title'] = 'Package activated';
                $event['tone'] = 'activation';
                $event['message'] = 'The customer package was activated.';
                $event['facts'] = [
                    ['label' => 'Valid until', 'value' => $activationMatch[1]],
                    ['label' => 'Action', 'value' => rtrim($activationMatch[2], '.')],
                ];
            } elseif (str_starts_with(strtolower($content), 'manual validity override:')) {
                $event['title'] = 'Validity changed';
                $event['tone'] = 'change';
                $event['message'] = trim((string) preg_replace('/^Manual validity override:\s*/i', 'Validity changed from ', $content));
            } elseif (str_contains(strtolower($content), 'force-inactivated')) {
                $event['title'] = 'Service status changed';
                $event['tone'] = 'change';
            } elseif (preg_match('/^(?:Bulk invoice\s+(\S+?)\s+)?paid:\s*(.+?),\s*(\d{2}\/\d{2}\/\d{4})\s+to\s+(\d{2}\/\d{2}\/\d{4}),\s*amount\s*([0-9.,]+),\s*reference\s*(.+?)\.?$/i', $content, $paidMatch)) {
                $paidInvoiceNo = trim((string) ($paidMatch[1] ?? ''));
                $paidInvoice = $paidInvoiceNo !== ''
                    ? $customer->invoices->firstWhere('invoice_no', $paidInvoiceNo)
                    : null;
                $paidByAdmin = $paidInvoice
                    ? ($paidInvoice->payments->first()?->entryByUser?->name
                        ?? $paidInvoice->entryByUser?->name
                        ?? $paidInvoice->enteredByLabel)
                    : null;
                $paidAmount = (float) str_replace(',', '', $paidMatch[5]);

                $event['title'] = 'Payment received';
                $event['tone'] = 'payment';
                $event['message'] = 'A service payment was recorded and validity was extended.';
                $event['admin'] = $paidByAdmin;
                $event['package'] = $servicePackage?->name;
                $event['validity_change'] = $paidMatch[3].' → '.$paidMatch[4];
                $event['value_display'] = '৳ '.number_format($paidAmount, 2);
                $event['facts'] = array_values(array_filter([
                    ['label' => 'Duration', 'value' => trim($paidMatch[2])],
                    ['label' => 'Service period', 'value' => $paidMatch[3].' → '.$paidMatch[4]],
                    ['label' => 'Amount', 'value' => '৳ '.number_format($paidAmount, 2)],
                    ['label' => 'Reference', 'value' => trim($paidMatch[6])],
                    $paidInvoiceNo !== '' ? ['label' => 'Invoice', 'value' => $paidInvoiceNo] : null,
                    ['label' => 'Payment taken by', 'value' => $paidByAdmin ?: 'Not recorded'],
                ]));
            }

            $partyNoteEvents->push($event);
        }

        $partyNoteEvents = $partyNoteEvents->sortByDesc('sort_at')->values();
    }

    // One full-width table that merges the parsed party notes with the
    // concession log rows recorded for this party.
    $activityRows = collect();

    foreach ($partyNoteEvents as $event) {
        $activityRows->push([
            'sort_at' => $event['recorded_at']?->timestamp ?? 0,
            'when' => $event['recorded_at'],
            'admin' => $event['admin'] ?? null,
            'action' => $event['title'],
            'tone' => $event['tone'],
            'detail' => $event['message'] ?: null,
            'facts' => $event['facts'] ?? [],
            'free_days' => null,
            'validity_change' => $event['validity_change'] ?? null,
            'package' => $event['package'] ?? null,
            'value' => $event['value_display'] ?? null,
            'running' => false,
        ]);
    }

    foreach ($customer->concessionLogs as $log) {
        $activityRows->push([
            'sort_at' => $log->created_at?->timestamp ?? 0,
            'when' => $log->created_at,
            'admin' => $log->user_name ?: 'System',
            'action' => $log->actionLabel(),
            'tone' => match ($log->action_type) {
                'force_active', 'mark_special' => 'activation',
                'grace_recovered' => 'payment',
                default => 'change',
            },
            'detail' => $log->reason ?: null,
            'facts' => [],
            'free_days' => $log->displayFreeDays(),
            'validity_change' => $log->new_valid_until
                ? (($log->previous_valid_until?->format('d/m/Y') ?? 'not set').' → '.$log->new_valid_until->format('d/m/Y'))
                : null,
            'package' => $log->package?->name,
            'value' => '৳ '.number_format($log->displayValue(), 2),
            'running' => $log->isRunning(),
        ]);
    }

    $activityRows = $activityRows->sortByDesc('sort_at')->values();
@endphp

<style>
    .customer-shell {
        max-width: 1320px;
        margin: 0 auto;
    }
    .customer-hero {
        border-radius: 18px;
        padding: 24px;
        border: 1px solid #c7deff;
        background: linear-gradient(132deg, #0f2749 10%, #0d3d6a 42%, #14634e 96%);
        color: #fff;
        box-shadow: 0 14px 32px rgba(11, 37, 74, .22);
    }
    .customer-hero__head {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        align-items: flex-start;
    }
    .customer-hero__label {
        margin: 0 0 6px;
        color: #d3ecff;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: 12px;
        font-weight: 800;
    }
    .customer-hero__title {
        margin: 0;
        font-size: 34px;
        line-height: 1.12;
        letter-spacing: -0.02em;
    }
    .customer-hero__meta {
        margin: 8px 0 0;
        color: #d7e8ff;
    }
    .customer-status {
        margin-top: 12px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border-radius: 999px;
        padding: 7px 12px;
        background: rgba(255, 255, 255, .18);
        border: 1px solid rgba(255, 255, 255, .3);
        font-weight: 700;
    }
    .customer-status::before {
        content: "";
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #22c55e;
    }
    .customer-status.offline::before {
        background: #fda29b;
    }
    .hero-actions {
        margin-top: 16px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, max-content));
        gap: 10px;
        justify-content: end;
    }
    .hero-actions .btn {
        min-height: 44px;
        padding-inline: 15px;
    }
    .hero-actions .btn.light {
        color: #0f2749;
    }
    .hero-actions .btn--ghost {
        background: rgba(255,255,255,.24);
        color: #ecf4ff;
        border: 1px solid rgba(255,255,255,.22);
    }
    .customer-summary {
        margin-top: 14px;
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 10px;
    }
    .hero-kpi {
        border-radius: 12px;
        border: 1px solid rgba(255,255,255,.22);
        background: rgba(10, 31, 63, .27);
        padding: 13px;
    }
    .hero-kpi__label {
        color: #d3e6ff;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .05em;
    }
    .hero-kpi__value {
        margin-top: 6px;
        font-size: 23px;
        font-weight: 800;
        line-height: 1.2;
    }
    .hero-kpi__meta {
        margin-top: 5px;
        color: #c8ddfa;
        font-size: 12px;
        font-weight: 700;
    }
    .customer-grid {
        margin-top: 16px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr) minmax(0, 1fr);
        gap: 16px;
    }
    .customer-card {
        border-radius: 15px;
        padding: 18px;
        border: 1px solid #dce6f4;
        background: #fff;
        box-shadow: 0 7px 24px rgba(15, 23, 42, .08);
    }
    .customer-card__heading {
        margin: 0 0 14px;
        font-size: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .customer-card__heading::before {
        content: "";
        width: 5px;
        height: 22px;
        border-radius: 999px;
        background: #1d76c9;
    }
    .kv-grid {
        display: grid;
        grid-template-columns: 140px minmax(0, 1fr);
        gap: 11px 16px;
        align-items: start;
        margin: 0;
    }
    .kv-grid__label {
        font-size: 13px;
        color: #54627c;
        font-weight: 700;
        margin-top: 2px;
    }
    .kv-grid__value {
        background: #f8fafc;
        border: 1px solid #e2e9f2;
        border-radius: 8px;
        padding: 10px 11px;
        min-height: 40px;
        word-break: break-word;
    }
    .kv-grid__note {
        grid-column: 1 / -1;
        border-top: 1px dashed #d3deea;
        margin-top: 3px;
        padding-top: 10px;
    }
    .party-note-panel {
        padding: 0;
        overflow: hidden;
        border-color: #cddbec;
        background: #f8fbff;
    }
    .party-note-panel__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 11px 13px;
        color: #17365d;
        background: linear-gradient(110deg, #eaf4ff, #effbf5);
        border-bottom: 1px solid #d6e4f2;
    }
    .party-note-panel__head strong {
        font-size: 13px;
    }
    .party-note-panel__head span {
        color: #65758b;
        font-size: 11px;
        font-weight: 700;
    }
    .party-note-timeline {
        max-height: 360px;
        overflow-y: auto;
        overscroll-behavior: contain;
        padding: 13px 12px 13px 15px;
        scrollbar-color: #9bb6d2 #eaf0f7;
        scrollbar-width: thin;
    }
    .party-note-event {
        --event-color: #64748b;
        position: relative;
        margin-left: 10px;
        padding: 0 0 15px 21px;
        border-left: 2px solid #dbe6f1;
    }
    .party-note-event:last-child {
        padding-bottom: 0;
        border-left-color: transparent;
    }
    .party-note-event::before {
        content: "";
        position: absolute;
        top: 10px;
        left: -7px;
        width: 12px;
        height: 12px;
        border: 3px solid #fff;
        border-radius: 50%;
        background: var(--event-color);
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--event-color) 28%, transparent);
    }
    .party-note-event--payment { --event-color: #059669; }
    .party-note-event--activation { --event-color: #16803b; }
    .party-note-event--change { --event-color: #d97706; }
    .party-note-event--import { --event-color: #2563eb; }
    .party-note-event__card {
        padding: 12px;
        border: 1px solid #dde7f1;
        border-radius: 11px;
        background: #fff;
        box-shadow: 0 3px 10px rgba(30, 64, 100, .06);
    }
    .party-note-event:first-child .party-note-event__card {
        border-color: color-mix(in srgb, var(--event-color) 35%, #dce6f0);
        box-shadow: 0 6px 16px rgba(30, 64, 100, .1);
    }
    .party-note-event__head {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 7px;
    }
    .party-note-event__type,
    .party-note-event__latest {
        display: inline-flex;
        align-items: center;
        min-height: 23px;
        padding: 3px 8px;
        border-radius: 999px;
        font-size: 11px;
        line-height: 1;
        font-weight: 800;
    }
    .party-note-event__type {
        color: var(--event-color);
        background: color-mix(in srgb, var(--event-color) 10%, white);
        border: 1px solid color-mix(in srgb, var(--event-color) 24%, white);
    }
    .party-note-event__latest {
        color: #fff;
        background: #102f54;
    }
    .party-note-event__time {
        margin-left: auto;
        color: #637188;
        font-size: 11px;
        font-weight: 700;
    }
    .party-note-event__message {
        margin: 9px 0 0;
        color: #26374b;
        font-size: 13px;
        line-height: 1.55;
    }
    .party-note-facts {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 7px;
        margin: 10px 0 0;
    }
    .party-note-fact {
        min-width: 0;
        padding: 8px 9px;
        border-radius: 8px;
        background: #f5f8fc;
        border: 1px solid #e4ebf3;
    }
    .party-note-fact dt {
        margin: 0;
        color: #6b778b;
        font-size: 10px;
        font-weight: 800;
        letter-spacing: .035em;
        text-transform: uppercase;
    }
    .party-note-fact dd {
        margin: 4px 0 0;
        color: #152b46;
        font-size: 12px;
        line-height: 1.35;
        font-weight: 700;
        overflow-wrap: anywhere;
    }
    .party-note-empty {
        padding: 18px;
        color: #667085;
        text-align: center;
    }
    .badge-row {
        margin-top: 2px;
        display: flex;
        flex-wrap: wrap;
        gap: 7px;
    }
    .stat-pill {
        display: inline-flex;
        border-radius: 10px;
        border: 1px solid #dbe5f4;
        background: #f5f8fc;
        padding: 8px 10px;
        font-size: 12px;
        font-weight: 700;
    }
    .stat-pill__big {
        font-size: 16px;
        font-weight: 800;
        margin-top: 3px;
    }
    .stat-pill.success {
        background: #ecfdf3;
        border-color: #a7f3d0;
        color: #027a48;
    }
    .stat-pill.warning {
        background: #fffaeb;
        border-color: #fedf89;
        color: #b54708;
    }
    .stat-pill.danger {
        background: #fffbfa;
        border-color: #f7c7c0;
        color: #b42318;
    }
    .stat-pill.neutral {
        background: #eff3f9;
        border-color: #d6dfeb;
        color: #344054;
    }
    .customer-routers {
        margin-top: 14px;
    }
    .mikrotik-grid {
        margin-top: 9px;
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
    }
    .mikrotik-item {
        border: 1px solid #dbe5f4;
        border-radius: 9px;
        padding: 8px 10px;
        background: #fff;
        display: flex;
        gap: 8px;
        align-items: flex-start;
    }
    .mikrotik-item input {
        width: auto;
        margin-top: 3px;
    }
    .mikrotik-item strong {
        display: block;
        font-size: 14px;
    }
    .mikrotik-item small {
        display: block;
        margin-top: 2px;
        color: #667085;
    }
    .action-row {
        margin-top: 10px;
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        align-items: center;
    }
    .action-row .btn {
        min-height: 38px;
        width: auto;
    }
    .form-panel {
        margin-top: 14px;
        padding: 12px;
        border: 1px dashed #bfd4ee;
        border-radius: 10px;
        background: #f7fbff;
    }
    .form-panel__title {
        margin: 0 0 10px;
        font-weight: 700;
        color: #344054;
    }
    .form-grid-2 {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
    }
    .table-wrap {
        overflow-x: auto;
        border-radius: 10px;
        border: 1px solid #dce6f4;
        background: #fff;
        margin-top: 12px;
    }
    .customer-tabs {
        margin-top: 16px;
        border: 1px solid #dce6f4;
        border-radius: 15px;
        background: #fff;
        padding: 14px;
    }
    .tab-list {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }
    .customer-tab {
        border: 1px solid #d9e3ee;
        border-bottom: 0;
        border-top-left-radius: 9px;
        border-top-right-radius: 9px;
        background: #f3f7fc;
        color: #334155;
        padding: 10px 12px;
        font-weight: 700;
        border-bottom: 2px solid transparent;
        cursor: pointer;
        min-height: 40px;
        display: inline-flex;
        align-items: center;
    }
    .customer-tab[aria-selected="true"] {
        background: #ffffff;
        border-color: #c8d8ef;
        color: #0f2749;
        border-bottom-color: #0f2749;
    }
    .customer-tab-panel {
        display: none;
        border-top: 1px solid #d9e3ee;
        margin-top: -1px;
        padding-top: 14px;
    }
    .customer-tab-panel.is-active {
        display: block;
    }
    .customer-tab-panel .card {
        border: 0;
        box-shadow: none;
        padding: 0;
    }
    .customer-extra {
        margin-top: 16px;
    }
    .details-stack {
        border: 1px solid #dce6f4;
        border-radius: 15px;
        background: #fff;
        margin-top: 16px;
        padding: 12px 16px;
    }
    .details-stack summary {
        cursor: pointer;
        font-weight: 700;
        padding: 8px 0;
        list-style: none;
    }
    .details-stack summary::-webkit-details-marker {
        display: none;
    }
    .details-stack + .details-stack {
        margin-top: 10px;
    }
    .details-stack .table-wrap {
        margin-top: 10px;
    }
    .customer-activity {
        margin-top: 16px;
        border-radius: 15px;
        padding: 18px;
        border: 1px solid #dce6f4;
        background: #fff;
        box-shadow: 0 7px 24px rgba(15, 23, 42, .08);
    }
    .customer-activity__head {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 12px;
    }
    .customer-activity__head .customer-card__heading {
        margin: 0;
    }
    .customer-activity__count {
        color: #65758b;
        font-size: 12px;
        font-weight: 700;
    }
    .customer-activity__scroll {
        overflow-x: auto;
        border: 1px solid #e2e9f2;
        border-radius: 10px;
    }
    .customer-activity__table {
        min-width: 940px;
        margin: 0;
        border: 0;
        border-radius: 0;
        font-size: 13px;
    }
    .customer-activity__table th,
    .customer-activity__table td {
        padding: 9px 11px;
    }
    .customer-activity__table td.activity-detail {
        max-width: 340px;
    }
    .customer-activity__table td.activity-value {
        white-space: nowrap;
        font-weight: 700;
    }
    .activity-tag {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 999px;
        font-size: 11px;
        font-weight: 800;
        white-space: nowrap;
        color: #334155;
        background: #eef2f7;
        border: 1px solid #dbe3ee;
    }
    .activity-tag--payment { color: #047a54; background: #e6f7ef; border-color: #b8e6d0; }
    .activity-tag--activation { color: #1f6f37; background: #e8f6ec; border-color: #bde3c6; }
    .activity-tag--change { color: #b45309; background: #fdf1e3; border-color: #f2d9b8; }
    .activity-tag--import { color: #1d4ed8; background: #e8effc; border-color: #c3d5f5; }
    .activity-tag--note { color: #475467; background: #eef2f7; border-color: #dbe3ee; }
    .activity-facts {
        margin: 5px 0 0;
        padding-left: 16px;
        color: #475467;
        font-size: 12px;
        line-height: 1.5;
    }
    .activity-facts b {
        color: #334155;
    }

    @media (max-width: 1120px) {
        .customer-grid {
            grid-template-columns: 1fr 1fr;
        }
        .customer-summary {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .hero-actions {
            justify-content: start;
            grid-template-columns: repeat(2, minmax(150px, 1fr));
        }
    }
    @media (max-width: 820px) {
        .customer-grid {
            grid-template-columns: 1fr;
        }
        .customer-hero {
            padding: 18px;
        }
        .customer-hero__head {
            display: block;
        }
        .hero-actions {
            margin-top: 14px;
            display: grid;
            grid-template-columns: 1fr 1fr;
        }
        .hero-actions .btn,
        .hero-actions .btn.light {
            width: 100%;
            justify-content: center;
            text-align: center;
        }
        .customer-summary {
            grid-template-columns: 1fr 1fr;
            gap: 8px;
        }
        .form-grid-2 {
            grid-template-columns: 1fr;
        }
        .mikrotik-grid {
            grid-template-columns: 1fr;
        }
        .customer-tab {
            width: 100%;
            text-align: left;
        }
        .customer-tab-panel {
            padding-top: 12px;
        }
        .customer-shell {
            padding: 0 2px;
        }
        .party-note-facts {
            grid-template-columns: 1fr;
        }
        .party-note-event__time {
            width: 100%;
            margin-left: 0;
        }
    }
    @media (max-width: 560px) {
        .customer-hero__title {
            font-size: 28px;
            line-height: 1.1;
        }
        .hero-kpi__value {
            font-size: 20px;
        }
        .customer-summary {
            grid-template-columns: 1fr;
        }
        .hero-actions {
            grid-template-columns: 1fr;
        }
        .customer-tabs {
            padding: 10px;
        }
        .customer-tab {
            min-height: 44px;
        }
    }
</style>

<div class="customer-shell">
    <section class="customer-hero">
        <div class="customer-hero__head">
            <div>
                <p class="customer-hero__label">Customer Profile</p>
                <h1 class="customer-hero__title">{{ $customer->name }}</h1>
                <p class="customer-hero__meta">
                    {{ $customer->connection_id ?: 'ID not assigned' }} • {{ $customer->phone ?: 'Phone not provided' }}
                </p>
                <div class="customer-status {{ $isActive ? '' : 'offline' }}">{{ $isActive ? 'Active' : 'Offline' }}</div>
            </div>
            <div class="hero-actions">
                <a class="btn" href="{{ route('customers.payments.create', $customer) }}">Quick Recharge</a>
                <a class="btn secondary" href="{{ route('customers.edit', $customer) }}">Edit Profile</a>
                <button class="btn btn--ghost" type="button" id="mikrotik-quick-sync" {{ ! $routerTargetsExists ? 'disabled' : '' }}>
                    MikroTik Sync
                </button>
                <a class="btn secondary" href="{{ route('customers.history', $customer) }}">Edit History</a>
                <a class="btn light" href="{{ route('customers.index') }}">All Parties</a>
            </div>
        </div>
        <div class="customer-summary">
            <div class="hero-kpi">
                <div class="hero-kpi__label">Service status</div>
                <div class="hero-kpi__value">{{ strtoupper($customer->status) }}</div>
                <div class="hero-kpi__meta">{{ $daysLeftLabel }}</div>
            </div>
            <div class="hero-kpi">
                @if ($isSpecial)
                    <div class="hero-kpi__label">Suspension rule</div>
                    <div class="hero-kpi__value">Special ISP</div>
                    <div class="hero-kpi__meta">No validity limit</div>
                @else
                    <div class="hero-kpi__label">Validity until</div>
                    <div class="hero-kpi__value">{{ $activeUntil?->format('d/m/Y') ?? 'Not set' }}</div>
                    <div class="hero-kpi__meta">Expires in: {{ $daysLeftLabel }}</div>
                @endif
            </div>
            <div class="hero-kpi">
                <div class="hero-kpi__label">Current due</div>
                <div class="hero-kpi__value">৳ {{ number_format($totalDue, 2) }}</div>
                <div class="hero-kpi__meta">From invoices</div>
            </div>
            <div class="hero-kpi">
                <div class="hero-kpi__label">Package</div>
                <div class="hero-kpi__value">
                    {{ $servicePackage ? $servicePackage->name : 'Not assigned' }}
                </div>
                <div class="hero-kpi__meta">
                    {{ $servicePackage ? '৳ '.number_format($serviceEffectivePrice, 2).($serviceHasSpecialPrice ? ' (special)' : '') : 'Set package first' }}
                </div>
            </div>
        </div>
    </section>

    <section class="customer-grid">
        <article class="customer-card">
            <h2 class="customer-card__heading">Personal Details</h2>
            <dl class="kv-grid">
                <dt class="kv-grid__label">Name</dt>
                <dd class="kv-grid__value">{{ $customer->name }}</dd>

                <dt class="kv-grid__label">Phone</dt>
                <dd class="kv-grid__value">
                    @if ($customer->phone)
                        <a href="tel:{{ $customer->phone }}">{{ $customer->phone }}</a>
                    @else
                        Not provided
                    @endif
                </dd>

                <dt class="kv-grid__label">Email</dt>
                <dd class="kv-grid__value">
                    @if ($customer->email)
                        <a href="mailto:{{ $customer->email }}">{{ $customer->email }}</a>
                    @else
                        Not provided
                    @endif
                </dd>

                <dt class="kv-grid__label">Connection ID</dt>
                <dd class="kv-grid__value">{{ $customer->connection_id ?: 'Not assigned' }}</dd>

                <dt class="kv-grid__label">MikroTik user</dt>
                <dd class="kv-grid__value">{{ $customer->mikrotik_username ?: 'Not assigned' }}</dd>

                <dt class="kv-grid__label">Role</dt>
                <dd class="kv-grid__value">
                    <div class="badge-row">
                        @foreach ($roleBadges as $badge)
                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        @endforeach
                        @if ($customer->never_suspend)
                            <span class="badge active">No auto suspend</span>
                        @endif
                    </div>
                </dd>

                <dt class="kv-grid__label">Address</dt>
                <dd class="kv-grid__value kv-grid__note">{{ $customer->address ?: 'Not provided' }}</dd>

                <dt class="kv-grid__label">Party note</dt>
                <dd class="kv-grid__value kv-grid__note">
                    See the full <a href="#party-activity">party activity &amp; concession log</a> table below.
                </dd>
                <dt class="kv-grid__label">MikroTik comment</dt>
                <dd class="kv-grid__value kv-grid__note">
                    {{ $customer->importedSecret?->router_comment ?: 'No comment' }}
                </dd>
            </dl>
        </article>

        <article class="customer-card">
            <h2 class="customer-card__heading">Billing &amp; Package Info</h2>
            <div class="stat-pill {{ $validityTone }}">
                <span>{{ $isSpecial ? 'Special ISP customer:' : 'Validity status:' }}</span>
                <strong class="stat-pill__big" style="margin-left:6px;">{{ $isSpecial ? 'No validity limit' : $daysLeftLabel }}</strong>
            </div>
            <dl class="kv-grid" style="margin-top:10px;">
                <dt class="kv-grid__label">Current package</dt>
                <dd class="kv-grid__value">
                    @if ($servicePackage)
                        <strong>{{ $servicePackage->name }}</strong><br>
                        @if ($serviceHasSpecialPrice)
                            <s class="muted">৳ {{ number_format($serviceListPrice, 2) }}</s>
                            <strong>৳ {{ number_format($serviceEffectivePrice, 2) }}</strong>
                            <span class="badge special">Special price</span>
                        @else
                            ৳ {{ number_format($serviceEffectivePrice, 2) }}
                        @endif
                        <div class="muted">Profile: {{ $servicePackage->mikrotik_profile ?: 'auto' }}</div>
                    @else
                        Not assigned
                    @endif
                </dd>

                @unless ($isSpecial)
                    <dt class="kv-grid__label">Validity until</dt>
                    <dd class="kv-grid__value">
                        {{ $activeUntil?->format('d/m/Y') ?? 'Not set' }}
                        @if ($customer->hasActiveGracePeriod())
                            <div class="muted">Grace until: {{ $customer->grace_until?->format('d/m/Y') }}</div>
                        @endif
                    </dd>
                @endunless

                <dt class="kv-grid__label">Advance balance</dt>
                <dd class="kv-grid__value">৳ {{ number_format((float) $customer->account_balance, 2) }}</dd>

                <dt class="kv-grid__label">Net payable</dt>
                <dd class="kv-grid__value">৳ {{ number_format($netBalance, 2) }}</dd>

                <dt class="kv-grid__label">Invoice count</dt>
                <dd class="kv-grid__value">{{ $customer->invoices->count() }}</dd>

                <dt class="kv-grid__label">Total invoiced</dt>
                <dd class="kv-grid__value">৳ {{ number_format($customer->invoices->sum('total'), 2) }}</dd>
            </dl>

            @if (! $isSpecial && $canOverrideValidity)
                <div class="form-panel">
                    <h3 class="form-panel__title">Force validity date</h3>
                    <form method="post" action="{{ route('customers.service-validity.update', $customer) }}" class="form-grid-2">
                        @csrf
                        <div>
                            <label>New validity date</label>
                            <input type="date" name="service_valid_until" value="{{ old('service_valid_until', $customer->service_valid_until?->format('Y-m-d')) }}" required>
                        </div>
                        <div>
                            <label>Reason / note</label>
                            <input type="text" name="validity_note" value="{{ old('validity_note') }}" placeholder="Reason is required" required>
                        </div>
                        <div class="action-row" style="grid-column:1/-1">
                            <button class="btn secondary" type="submit">Save validity</button>
                        </div>
                    </form>
                </div>
            @endif

            @if ($servicePackage && (float) $customer->account_balance >= $serviceEffectivePrice && $serviceEffectivePrice > 0)
                <div class="form-panel">
                    <h3 class="form-panel__title">Extend from advance balance</h3>
                    <form method="post" action="{{ route('customers.advance-renewal.store', $customer) }}">
                        @csrf
                        <div class="action-row">
                            <button class="btn" type="submit">
                                Renew 1 month from advance
                            </button>
                            @if ($daysRemaining !== null && $daysRemaining >= 0)
                                <small class="muted">Current validity is {{ $daysRemaining }} day(s) remaining; renewal will extend from current date.</small>
                            @endif
                        </div>
                    </form>
                </div>
            @endif

            @if ($canForceStatus && ! $isSpecial)
                <div class="form-panel">
                    <h3 class="form-panel__title">Service control</h3>
                    @if ($customer->status === 'active')
                        <form method="post" action="{{ route('customers.force-inactive', $customer) }}" class="form-grid-2" onsubmit="return confirm('Temporarily make this service inactive now?')">
                            @csrf
                            <div style="grid-column:1/-1">
                                <label>Reason / note</label>
                                <input type="text" name="inactive_note" value="{{ old('inactive_note') }}" placeholder="Reason is required" required>
                            </div>
                            <div class="action-row" style="grid-column:1/-1">
                                <button class="btn danger" type="submit">Temporary inactive</button>
                            </div>
                        </form>
                    @else
                        <form method="post" action="{{ route('customers.force-active', $customer) }}" class="form-grid-2" onsubmit="return confirm('Temporarily make this service active now?')">
                            @csrf
                            <div style="grid-column:1/-1">
                                <label>Reason / note</label>
                                <input type="text" name="active_note" value="{{ old('active_note') }}" placeholder="Reason is required" required>
                            </div>
                            <div class="action-row" style="grid-column:1/-1">
                                <button class="btn secondary" type="submit">Temporary active</button>
                            </div>
                        </form>
                    @endif
                </div>
            @endif
        </article>

        <article class="customer-card">
            <h2 class="customer-card__heading">Network &amp; MikroTik</h2>
            <dl class="kv-grid">
                <dt class="kv-grid__label">Routers assigned</dt>
                <dd class="kv-grid__value">
                    @if ($assignedRouters->isNotEmpty())
                        {{ $assignedRouters->pluck('name')->join(', ') }}
                    @else
                        Not assigned
                    @endif
                </dd>

                <dt class="kv-grid__label">IP assignment</dt>
                <dd class="kv-grid__value">
                    @if ($customer->use_fixed_ip)
                        Fixed: {{ $customer->fixed_ip_address ?: 'Not set' }}
                    @else
                        Dynamic: {{ $customer->last_connected_ip ?: $customer->learned_ip_address ?: 'Not learned yet' }}
                    @endif
                </dd>

                <dt class="kv-grid__label">MAC</dt>
                <dd class="kv-grid__value">{{ $customer->last_connected_mac ?: 'Not learned yet' }}</dd>

                <dt class="kv-grid__label">Last connected at</dt>
                <dd class="kv-grid__value">{{ $customer->last_connected_at?->format('d/m/Y H:i') ?? 'Not learned yet' }}</dd>

                <dt class="kv-grid__label">OLT / ONU signal</dt>
                <dd class="kv-grid__value">Not available in party profile (track via OLT ONUs)</dd>

                <dt class="kv-grid__label">Sync status</dt>
                <dd class="kv-grid__value">
                    @if ($customer->last_connected_at)
                        <span class="stat-pill success">Last seen {{ $customer->last_connected_at->diffForHumans() }}</span>
                    @else
                        <span class="stat-pill neutral">Not synced yet</span>
                    @endif
                </dd>
            </dl>

            <div class="customer-routers">
                <h3 class="form-panel__title" style="margin:0 0 8px;">MikroTik targets</h3>
                <p class="muted">Selecting routers will sync PPPoE user to each selected MikroTik router on save.</p>
                <form method="post" action="{{ route('customers.mikrotik-targets.update', $customer) }}" id="mikrotik-target-form">
                    @csrf
                    <div class="mikrotik-grid">
                        @foreach($routers as $router)
                            <label class="mikrotik-item">
                                <input
                                    type="checkbox"
                                    name="mikrotik_router_ids[]"
                                    value="{{ $router->id }}"
                                    @checked(in_array($router->id, old('mikrotik_router_ids', $assignedRouterIds), true))
                                >
                                <span>
                                    <strong>{{ $router->name }}</strong>
                                    <small>{{ $router->ip_address }}:{{ $router->api_port }} • {{ ucfirst($router->status) }}</small>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    <div class="action-row">
                        @if ($routerTargetsExists && $routers->isNotEmpty())
                            <button class="btn secondary" type="submit">Save &amp; sync targets</button>
                        @elseif ($customer->connection_id || $customer->mikrotik_username)
                            <button class="btn secondary" type="submit" disabled>No router available</button>
                        @else
                            <button class="btn secondary" type="button" disabled>Set Connection ID first</button>
                        @endif
                    </div>
                </form>
            </div>

            <div class="form-panel" style="margin-top:10px;">
                <h3 class="form-panel__title">Reseller</h3>
                <div class="kv-grid">
                    <dt class="kv-grid__label">Assigned reseller</dt>
                    <dd class="kv-grid__value">
                        @if ($customer->reseller)
                            {{ $customer->reseller->name }} • {{ $customer->reseller->phone }}
                        @else
                            Direct / no reseller
                        @endif
                    </dd>
                    @if ($customer->reseller)
                        <dt class="kv-grid__label">Commission</dt>
                        <dd class="kv-grid__value">{{ number_format((float) $customer->reseller->reseller_commission_percent, 2) }}%</dd>
                    @endif
                </div>
            </div>
        </article>
    </section>

    <section class="customer-activity" id="party-activity">
        <div class="customer-activity__head">
            <h2 class="customer-card__heading">Party activity &amp; concession log</h2>
            <span class="customer-activity__count">
                {{ $activityRows->count() }} {{ \Illuminate\Support\Str::plural('record', $activityRows->count()) }} &bull; newest first
            </span>
        </div>
        <div class="customer-activity__scroll">
            <table class="customer-activity__table">
                <thead>
                    <tr>
                        <th>SL</th>
                        <th>When</th>
                        <th>Admin</th>
                        <th>Action</th>
                        <th>Details / reason</th>
                        <th>Free days</th>
                        <th>Validity change</th>
                        <th>Package</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($activityRows as $row)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $row['when']?->format('d/m/Y H:i') ?? 'Not recorded' }}</td>
                            <td>{{ $row['admin'] ?: '—' }}</td>
                            <td><span class="activity-tag activity-tag--{{ $row['tone'] }}">{{ $row['action'] }}</span></td>
                            <td class="activity-detail">
                                @if ($row['detail'])
                                    <span>{{ $row['detail'] }}</span>
                                @endif
                                @if (! empty($row['facts']))
                                    <ul class="activity-facts">
                                        @foreach ($row['facts'] as $fact)
                                            <li><b>{{ $fact['label'] }}:</b> {{ $fact['value'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if (! $row['detail'] && empty($row['facts']))
                                    —
                                @endif
                            </td>
                            <td>{{ $row['free_days'] !== null ? $row['free_days'] : '—' }}</td>
                            <td>{{ $row['validity_change'] ?: '—' }}</td>
                            <td>{{ $row['package'] ?: '—' }}</td>
                            <td class="activity-value">
                                {{ $row['value'] ?: '—' }}
                                @if ($row['running'])
                                    <span class="muted">(running)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="muted">No activity or concession records for this party yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="customer-tabs">
        <div class="tab-list" role="tablist" aria-label="Customer history tabs">
            <button class="customer-tab" type="button" data-tab="invoices" role="tab" aria-selected="true">
                Invoices ({{ $customer->invoices->count() }})
            </button>
            <button class="customer-tab" type="button" data-tab="payments" role="tab" aria-selected="false">
                Advance Balance History ({{ $customer->balanceTransactions->count() }})
            </button>
            <button class="customer-tab" type="button" data-tab="tickets" role="tab" aria-selected="false">
                Support Tickets ({{ $customer->tickets->count() }})
            </button>
        </div>

        <div class="customer-tab-panel is-active" id="tab-panel-invoices" role="tabpanel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Month</th>
                            <th>Total</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->invoices as $invoice)
                            <tr>
                                <td>
                                    @if ($canOpenInvoices)
                                        <a href="{{ route('invoices.show', $invoice) }}">{{ $invoice->invoice_no }}</a>
                                    @else
                                        {{ $invoice->invoice_no }}
                                    @endif
                                </td>
                                <td>{{ $invoice->formatted_billing_month }}</td>
                                <td>৳ {{ number_format($invoice->total, 2) }}</td>
                                <td>৳ {{ number_format($invoice->due_amount, 2) }}</td>
                                <td><span class="badge {{ $invoice->status }}">{{ ucfirst($invoice->status) }}</span></td>
                                <td>{{ $invoice->created_at?->format('d/m/Y') ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No invoices yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="customer-tab-panel" id="tab-panel-payments" role="tabpanel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Reference</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->balanceTransactions as $transaction)
                            <tr>
                                <td>{{ $transaction->transaction_date?->format('d/m/Y') ?? '-' }}</td>
                                <td><span class="badge {{ $transaction->direction === 'credit' ? 'active' : 'due' }}">{{ ucfirst($transaction->direction) }}</span></td>
                                <td>৳ {{ number_format($transaction->amount, 2) }}</td>
                                <td>৳ {{ number_format($transaction->balance_after, 2) }}</td>
                                <td>{{ $transaction->reference ?? 'N/A' }}</td>
                                <td>{{ $transaction->note ?? 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No advance balance history yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="customer-tab-panel" id="tab-panel-tickets" role="tabpanel">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Priority</th>
                            <th>Status</th>
                            <th>Technician</th>
                            <th>Created</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->tickets as $ticket)
                            <tr>
                                <td>{{ $ticket->subject }}</td>
                                <td>{{ $ticket->priority }}</td>
                                <td><span class="badge pending">{{ ucfirst($ticket->status) }}</span></td>
                                <td>{{ $ticket->technician?->name ?: 'Unassigned' }}</td>
                                <td>{{ $ticket->created_at?->format('d/m/Y') ?? '-' }}</td>
                                <td><a class="btn light" href="{{ route('tickets.show', $ticket) }}">View</a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">No support tickets yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if ($customer->is_reseller)
        <section class="details-stack">
            <details>
                <summary>Reseller details</summary>
                <div class="customer-card" style="margin-top:10px;">
                    <div class="customer-routers">
                        <div class="kv-grid">
                            <div class="kv-grid__label">Wallet balance</div>
                            <div class="kv-grid__value">৳ {{ number_format((float) $customer->account_balance, 2) }}</div>
                            <div class="kv-grid__label">Daily payment limit</div>
                            <div class="kv-grid__value">
                                {{ $customer->reseller_daily_payment_limit === null ? 'Unlimited' : '৳ '.number_format((float) $customer->reseller_daily_payment_limit, 2) }}
                            </div>
                            <div class="kv-grid__label">Commission</div>
                            <div class="kv-grid__value">{{ number_format((float) $customer->reseller_commission_percent, 2) }}%</div>
                            <div class="kv-grid__label">Assigned parties</div>
                            <div class="kv-grid__value">{{ $customer->resellerCustomers->count() }}</div>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr><th>Party</th><th>Connection ID</th><th>Phone</th><th>Status</th><th>Action</th></tr>
                                </thead>
                                <tbody>
                                    @forelse ($customer->resellerCustomers as $resellerCustomer)
                                        <tr>
                                            <td>{{ $resellerCustomer->name }}</td>
                                            <td>{{ $resellerCustomer->connection_id ?: 'N/A' }}</td>
                                            <td>{{ $resellerCustomer->phone ?: 'Not provided' }}</td>
                                            <td><span class="badge {{ $resellerCustomer->status }}">{{ ucfirst($resellerCustomer->status) }}</span></td>
                                            <td><a class="btn light" href="{{ route('customers.show', $resellerCustomer) }}">View</a></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="5">No party is assigned to this reseller.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </details>
        </section>
    @endif

    <section class="details-stack">
        <details>
            <summary>Assets &amp; Warranty</summary>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Product</th><th>Serial</th><th>Invoice</th><th>Sold Date</th><th>Warranty</th><th>Status</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($customer->productSerials as $serial)
                            @php
                                $openClaim = $serial->warrantyClaims->first(fn ($claim) => in_array($claim->status, \App\Models\WarrantyClaim::OPEN_STATUSES, true));
                                $warrantyLabel = $serial->warranty_until
                                    ? ($serial->warranty_until->copy()->endOfDay()->gte(now()) ? 'In warranty until '.$serial->warranty_until->format('d/m/Y') : 'Expired '.$serial->warranty_until->format('d/m/Y'))
                                    : 'No warranty';
                            @endphp
                            <tr>
                                <td>{{ $serial->product?->name ?? 'N/A' }}</td>
                                <td><span class="badge">{{ $serial->serial_number }}</span></td>
                                <td>
                                    @if ($serial->invoice)
                                        @if ($canOpenInvoices)
                                            <a href="{{ route('invoices.show', $serial->invoice) }}">{{ $serial->invoice->invoice_no }}</a>
                                        @else
                                            {{ $serial->invoice->invoice_no }}
                                        @endif
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td>{{ $serial->sold_at?->format('d/m/Y') ?? 'N/A' }}</td>
                                <td>{{ $warrantyLabel }}</td>
                                <td>
                                    @if ($openClaim && $canOpenWarrantyClaims)
                                        <a class="badge pending" href="{{ route('warranty-claims.show', $openClaim) }}">{{ str_replace('_', ' ', $openClaim->status) }}</a>
                                    @elseif ($openClaim)
                                        <span class="badge pending">{{ str_replace('_', ' ', $openClaim->status) }}</span>
                                    @else
                                        {{ str_replace('_', ' ', $serial->status) }}
                                    @endif
                                </td>
                                <td>
                                    @if (auth()->user()?->hasPermission('manage_warranty_claims') && ! $openClaim)
                                        <a class="btn light" href="{{ route('warranty-claims.create', ['product_serial_id' => $serial->id]) }}">Warranty Claim</a>
                                    @elseif ($openClaim && $canOpenWarrantyClaims)
                                        <a class="btn light" href="{{ route('warranty-claims.show', $openClaim) }}">View Claim</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No sold serial assets found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    @if ($customer->commissionHistories->isNotEmpty())
        <section class="details-stack">
            <details>
                <summary>Commission change history</summary>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>#</th><th>Changed At</th><th>Previous</th><th>New</th><th>Changed By</th><th>Note</th></tr></thead>
                        <tbody>
                            @forelse($customer->commissionHistories as $history)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $history->changed_at?->format('d/m/Y H:i') }}</td>
                                    <td>{{ $history->old_percent === null ? 'Initial' : number_format((float) $history->old_percent, 2).'%' }}</td>
                                    <td>{{ number_format((float) $history->new_percent, 2) }}%</td>
                                    <td>{{ $history->changedByUser?->name ?? 'System' }}</td>
                                    <td>{{ $history->note ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="6">No commission changes yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </details>
        </section>
    @endif

    @include('customers.partials.map_location', ['editable' => false])
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tabs = document.querySelectorAll('.customer-tab');
    const panels = document.querySelectorAll('.customer-tab-panel');

    const activateTab = function (tabId) {
        tabs.forEach((tab) => {
            const isActive = tab.dataset.tab === tabId;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        panels.forEach((panel) => {
            panel.classList.toggle('is-active', panel.id === 'tab-panel-' + tabId);
        });
    };

    tabs.forEach((tab) => {
        tab.addEventListener('click', function () {
            activateTab(this.dataset.tab);
        });
    });

    const quickSyncButton = document.getElementById('mikrotik-quick-sync');
    const targetForm = document.getElementById('mikrotik-target-form');
    if (quickSyncButton && targetForm) {
        quickSyncButton.addEventListener('click', function () {
            const selected = targetForm.querySelectorAll('input[name=\"mikrotik_router_ids[]\"]:checked').length;
            if (! selected) {
                alert('Please select at least one MikroTik target first.');
                return;
            }
            targetForm.requestSubmit();
        });
    }
});
</script>
@endsection
