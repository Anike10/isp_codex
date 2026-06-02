<?php

namespace App\Services;

use App\Models\OltDevice;
use App\Models\OltOnu;

class OltSnmpClient
{
    public function singleOnuSnapshot(OltDevice $oltDevice, OltOnu $oltOnu): ?array
    {
        if (! $this->canPoll($oltDevice)) {
            return null;
        }

        $record = [
            'pon_port' => $oltOnu->pon_port,
            'onu_id' => $oltOnu->onu_id,
        ];
        $raw = [];

        if ($oltDevice->snmp_status_oid_template) {
            $oid = $this->fillOidTemplate($oltDevice->snmp_status_oid_template, $oltOnu);
            $value = $this->get($oltDevice, $oid);

            if ($value !== null) {
                $record['status'] = $this->normalizeStatus($value);
                $raw[] = $oid.' = '.$value;
            }
        }

        if ($oltDevice->snmp_power_oid_template) {
            $oid = $this->fillOidTemplate($oltDevice->snmp_power_oid_template, $oltOnu);
            $value = $this->get($oltDevice, $oid);
            $power = $this->normalizePower($value, (float) ($oltDevice->snmp_power_divisor ?: 1));

            if ($power !== null) {
                $record['rx_power_dbm'] = $power;
                $raw[] = $oid.' = '.$value;
            }
        }

        if (count($record) <= 2) {
            return null;
        }

        $record['raw_live_output'] = "SNMP\n".implode("\n", $raw);

        return $record;
    }

    public function canPoll(OltDevice $oltDevice): bool
    {
        return (bool) $oltDevice->snmp_enabled
            && (string) $oltDevice->snmp_community !== ''
            && ($oltDevice->snmp_status_oid_template || $oltDevice->snmp_power_oid_template)
            && $this->hasPhpSnmpSupport($oltDevice);
    }

    public function hasPhpSnmpSupport(OltDevice $oltDevice): bool
    {
        return match ($oltDevice->snmp_version ?: '2c') {
            '1' => function_exists('snmpget'),
            default => function_exists('snmp2_get'),
        };
    }

    public function fillOidTemplate(string $template, OltOnu $oltOnu): string
    {
        return str_replace(
            ['{pon_port}', '{onu_id}', '{pon_onu}', '{pon_onu_dot}'],
            [
                (string) $oltOnu->pon_port,
                (string) $oltOnu->onu_id,
                $oltOnu->pon_port.'/'.$oltOnu->onu_id,
                $oltOnu->pon_port.'.'.$oltOnu->onu_id,
            ],
            $template
        );
    }

    public function normalizeStatus(string $value): string
    {
        $value = strtolower(trim($this->stripTypePrefix($value), "\"' \t\n\r\0\x0B"));

        if (in_array($value, ['1', 'up', 'online', 'active', 'working'], true)) {
            return 'online';
        }

        if (in_array($value, ['2', '0', 'down', 'offline', 'inactive', 'los', 'dying-gasp'], true)) {
            return 'offline';
        }

        return $value ?: 'unknown';
    }

    public function normalizePower(?string $value, float $divisor = 1): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = $this->stripTypePrefix($value);

        if (! preg_match('/-?\d+(?:\.\d+)?/', $value, $matches)) {
            return null;
        }

        $power = (float) $matches[0];
        $divisor = $divisor > 0 ? $divisor : 1;

        return round($power / $divisor, 2);
    }

    private function get(OltDevice $oltDevice, string $oid): ?string
    {
        $timeout = max(100, (int) ($oltDevice->snmp_timeout_ms ?: 800)) * 1000;
        $retries = max(0, (int) ($oltDevice->snmp_retries ?? 1));

        if (function_exists('snmp_set_quick_print')) {
            snmp_set_quick_print(true);
        }

        $value = match ($oltDevice->snmp_version ?: '2c') {
            '1' => @snmpget($oltDevice->host, $oltDevice->snmp_community, $oid, $timeout, $retries),
            default => @snmp2_get($oltDevice->host, $oltDevice->snmp_community, $oid, $timeout, $retries),
        };

        if ($value === false) {
            return null;
        }

        return trim((string) $value);
    }

    private function stripTypePrefix(string $value): string
    {
        return trim((string) preg_replace('/^[A-Z0-9-]+(?:\s+\d+)?:\s*/i', '', $value));
    }
}
