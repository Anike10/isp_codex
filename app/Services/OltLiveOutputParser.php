<?php

namespace App\Services;

use App\Support\Utf8Text;
use Carbon\Carbon;

class OltLiveOutputParser
{
    public function parse(string $output): array
    {
        $output = Utf8Text::clean($output) ?? '';

        $records = [];
        $current = null;

        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '!')) {
                continue;
            }

            if ($record = $this->parseHsgqOpticalInfoLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $record);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($record = $this->parseAlarmHistoryLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $record);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($record = $this->parsePortVlanContextLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $record);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($this->isNoiseLine($line)) {
                continue;
            }

            if ($record = $this->parseHsgqOnuInfoLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $record);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if (preg_match('/(?:interface\s+)?(?:epon|pon|gpon)[^\d]*(\d+)[\/:\s]+(?:onu|ont)?\s*(\d+)/i', $line, $match)) {
                $ponPort = (int) $match[1];
                $onuId = (int) $match[2];

                if (! $this->isValidPonOnu($ponPort, $onuId)) {
                    $current = null;
                    continue;
                }

                $current = $this->key($ponPort, $onuId);
                $records[$current] ??= [
                    'pon_port' => $ponPort,
                    'onu_id' => $onuId,
                ];
            } elseif (preg_match('/^\s*([1-8])\/(\d{1,3})\b/', $line, $match)) {
                $ponPort = (int) $match[1];
                $onuId = (int) $match[2];

                if (! $this->isValidPonOnu($ponPort, $onuId)) {
                    $current = null;
                    continue;
                }

                $current = $this->key($ponPort, $onuId);
                $records[$current] ??= [
                    'pon_port' => $ponPort,
                    'onu_id' => $onuId,
                ];
            }

            if (! $current) {
                continue;
            }

            $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

            if (preg_match('/\b([0-9a-f]{2}(?::[0-9a-f]{2}){5})\b/i', $line, $match)) {
                $records[$current]['mac_address'] = strtolower($match[1]);
            }

            if (preg_match('/\b(online|offline|active|inactive|los|dying-gasp)\b/i', $line, $match)) {
                $records[$current]['status'] = strtolower($match[1]);
            }

            if (preg_match('/(?:rx|receive|received|onu rx|optical)[^-\d]*(-?\d+(?:\.\d+)?)\s*(?:dbm|dBm)?/i', $line, $match)) {
                $records[$current]['rx_power_dbm'] = (float) $match[1];
            } elseif (preg_match('/(-\d{1,2}(?:\.\d+)?)\s*dBm/i', $line, $match)) {
                $records[$current]['rx_power_dbm'] = (float) $match[1];
            } elseif (preg_match('/\b(?:online|offline|active|inactive|los)\b.*?\s(-\d{1,2}(?:\.\d+)?)\s/i', $line, $match)) {
                $records[$current]['rx_power_dbm'] = (float) $match[1];
            }

            if (preg_match('/(?:distance)\D*(\d+)/i', $line, $match)) {
                $records[$current]['distance_m'] = (int) $match[1];
            } elseif (isset($records[$current]['rx_power_dbm']) && preg_match('/\s(-?\d{1,2}(?:\.\d+)?)\s+(\d{2,6})\s*$/', $line, $match)) {
                $records[$current]['distance_m'] = (int) $match[2];
            }

            if (preg_match('/(?:name|description|desc)\s*[:=]\s*(.+)$/i', $line, $match)) {
                $records[$current]['name'] ??= trim($match[1], '" ');
            }

            if ($vlan = $this->parsePortVlanLine($line)) {
                $records[$current]['port_vlans'] ??= [];
                $records[$current]['port_vlans'][$vlan['port']] = $vlan;
                $records[$current]['raw_interface_config'] = trim(($records[$current]['raw_interface_config'] ?? '')."\n".$line);
            }
        }

        return array_map(function (array $record): array {
            if (isset($record['port_vlans'])) {
                $record['port_vlans'] = array_values($record['port_vlans']);
            }

            return $record;
        }, array_values($records));
    }

    private function parseHsgqOpticalInfoLine(string $line): ?array
    {
        if (! preg_match('/^\s*(\d+)\/(\d+)\s+(.+?)\s+([0-9a-f]{2}(?::[0-9a-f]{2}){5})\s+.*?\s(-?\d+(?:\.\d+)?)\s*dBm\s+(-?\d+(?:\.\d+)?)\s*dBm\s*$/i', $line, $match)) {
            return null;
        }

        if (! $this->isValidPonOnu((int) $match[1], (int) $match[2])) {
            return null;
        }

        return [
            'pon_port' => (int) $match[1],
            'onu_id' => (int) $match[2],
            'name' => trim($match[3]),
            'mac_address' => strtolower($match[4]),
            'rx_power_dbm' => (float) $match[6],
        ];
    }

    private function parseHsgqOnuInfoLine(string $line): ?array
    {
        if (! preg_match('/^\s*(\d+)\/(\d+)\s+([0-9a-f]{2}(?::[0-9a-f]{2}){5})\s+(\S+)\s+\S+\s+\S+\s+(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\s+(.+?)\s*$/i', $line, $match)) {
            return null;
        }

        if (! $this->isValidPonOnu((int) $match[1], (int) $match[2])) {
            return null;
        }

        return [
            'pon_port' => (int) $match[1],
            'onu_id' => (int) $match[2],
            'mac_address' => strtolower($match[3]),
            'status' => strtolower($match[4]),
            'last_registered_at' => $this->parseTimestamp($match[5]),
            'name' => trim($match[6]),
        ];
    }

    private function parseAlarmHistoryLine(string $line): ?array
    {
        if (! preg_match('/^\[(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\]\s+(Info|Warning):\s+ONU\s+([1-8])\/(\d{1,3})\s+([0-9a-f]{2}(?::[0-9a-f]{2}){5})\s+(.+?)(?:,\s*Reason:\s*(.*))?\s*$/i', $line, $match)) {
            return null;
        }

        $message = trim($match[6]);
        $reason = trim($match[7] ?? '');
        $ponPort = (int) $match[3];
        $onuId = (int) $match[4];

        if (! $this->isValidPonOnu($ponPort, $onuId)) {
            return null;
        }

        $record = [
            'pon_port' => $ponPort,
            'onu_id' => $onuId,
            'mac_address' => strtolower($match[5]),
        ];

        if (preg_match('/\b(link\s+up|authorization\s+success)\b/i', $message)) {
            $record['last_registered_at'] = $this->parseTimestamp($match[1]);
        }

        if (preg_match('/\b(deregister|dying\s+gasp|los)\b/i', $message)) {
            $record['last_deregistered_at'] = $this->parseTimestamp($match[1]);
            $record['last_deregister_reason'] = $reason !== '' ? $reason : $this->normalizeAlarmReason($message);
        }

        return count($record) > 3 ? $record : null;
    }

    private function parsePortVlanContextLine(string $line): ?array
    {
        if (! preg_match('/^ONU\s*:\s*([1-8])[:\/](\d{1,3})\b/i', $line, $match)) {
            return null;
        }

        $ponPort = (int) $match[1];
        $onuId = (int) $match[2];

        if (! $this->isValidPonOnu($ponPort, $onuId)) {
            return null;
        }

        return [
            'pon_port' => $ponPort,
            'onu_id' => $onuId,
            'port_vlans' => [],
            'raw_interface_config' => "interface onu {$ponPort}/{$onuId}",
        ];
    }

    private function parsePortVlanLine(string $line): ?array
    {
        if (preg_match('/^port\s*(\d+)\s+transparent\b/i', $line, $match)) {
            return [
                'port' => (int) $match[1],
                'mode' => 'transparent',
                'vlan' => null,
                'priority' => null,
            ];
        }

        if (! preg_match('/^port\s*(\d+)\s+(\S+)\s+id\s*:\s*(\d+)(?:\s+pri\s*:\s*(\d+))?/i', $line, $match)) {
            return null;
        }

        return [
            'port' => (int) $match[1],
            'mode' => strtolower($match[2]),
            'vlan' => (int) $match[3],
            'priority' => isset($match[4]) ? (int) $match[4] : 0,
        ];
    }

    private function parseTimestamp(string $value): ?Carbon
    {
        return Carbon::createFromFormat('Y/m/d H:i:s', $value);
    }

    private function key(int $ponPort, int $onuId): string
    {
        return $ponPort.'/'.$onuId;
    }

    private function isValidPonOnu(int $ponPort, int $onuId): bool
    {
        return $ponPort >= 1 && $ponPort <= 8 && $onuId >= 1 && $onuId <= 256;
    }

    private function isNoiseLine(string $line): bool
    {
        return preg_match('/^\[\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2}\]\s+Warning:/i', $line)
            || preg_match('/^-{5,}$/', $line)
            || preg_match('/^(all|show\s+|pon\/onu\b|port\s+id\b)/i', $line);
    }

    private function mergeRecord(array $existing, array $incoming): array
    {
        foreach (['last_registered_at', 'last_deregistered_at'] as $field) {
            if (! isset($incoming[$field], $existing[$field]) || $incoming[$field]->greaterThanOrEqualTo($existing[$field])) {
                continue;
            }

            unset($incoming[$field]);

            if ($field === 'last_deregistered_at') {
                unset($incoming['last_deregister_reason']);
            }
        }

        return array_merge($existing, $incoming);
    }

    private function normalizeAlarmReason(string $message): string
    {
        return trim(preg_replace('/^ONU\s+/i', '', $message) ?? $message);
    }
}
