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
        $detailPonPort = null;

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

            if ($record = $this->parseHsgqGponOpticalLine($line)) {
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

            if ($record = $this->parseGponServicePortLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], [
                    'pon_port' => $record['pon_port'],
                    'onu_id' => $record['onu_id'],
                    'port_vlans' => [$record['port_vlan_key'] => $record['port_vlan']],
                ]);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($record = $this->parseLearnedMacLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $incoming = [
                    'pon_port' => $record['pon_port'],
                    'onu_id' => $record['onu_id'],
                    'learned_macs' => [$record['learned_mac']['mac'] => $record['learned_mac']],
                ];

                if (isset($record['port_vlan'])) {
                    $incoming['port_vlans'] = [$record['port_vlan_key'] => $record['port_vlan']];
                }

                $records[$current] = $this->mergeRecord($records[$current] ?? [], $incoming);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($this->isNoiseLine($line)) {
                continue;
            }

            if (preg_match('/^PON\s+ID\s*:\s*(\d+)/i', $line, $match)) {
                $detailPonPort = (int) $match[1];

                continue;
            }

            if ($detailPonPort !== null && preg_match('/^ONU\s+ID\s*:\s*(\d+)/i', $line, $match)) {
                $onuId = (int) $match[1];

                if (! $this->isValidPonOnu($detailPonPort, $onuId)) {
                    $current = null;
                    continue;
                }

                $current = $this->key($detailPonPort, $onuId);
                $records[$current] ??= [
                    'pon_port' => $detailPonPort,
                    'onu_id' => $onuId,
                ];

                continue;
            }

            if ($record = $this->parseHsgqOnuInfoLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $record);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($record = $this->parseHsgqGponOntInfoLine($line)) {
                $current = $this->key($record['pon_port'], $record['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $record);
                $records[$current]['raw_live_output'] = trim(($records[$current]['raw_live_output'] ?? '')."\n".$line);

                continue;
            }

            if ($detail = $this->parseOntDetailLine($line, $records[$current] ?? [])) {
                $current = $this->key($detail['pon_port'], $detail['onu_id']);
                $records[$current] = $this->mergeRecord($records[$current] ?? [], $detail);
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

            if (isset($record['learned_macs'])) {
                $record['learned_macs'] = array_values($record['learned_macs']);
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

    private function parseHsgqGponOntInfoLine(string $line): ?array
    {
        if (! preg_match('/^\s*(\d+)\/(\d+)\s+(\S+)\s+(Active|Inactive)\s+(\S+)\s+(\S+)\s+\S+\s+(.*?)\s{2,}(.+?)\s*$/i', $line, $match)) {
            return null;
        }

        if (! $this->isValidPonOnu((int) $match[1], (int) $match[2])) {
            return null;
        }

        $description = trim($match[7]);

        $record = [
            'pon_port' => (int) $match[1],
            'onu_id' => (int) $match[2],
            'mac_address' => $match[3],
            'status' => strtolower($match[5]) === 'online' ? 'online' : strtolower($match[4]),
            'name' => trim($match[8]),
        ];

        if ($description !== '') {
            $record['description'] = $description;
            $record['last_deregister_reason'] = $description;
        }

        return $record;
    }

    private function parseHsgqGponOpticalLine(string $line): ?array
    {
        if (! preg_match('/^\s*(\d+)\/(\d+)\s+(\S+)\s+.*?mA\s+(-?(?:inf|\d+(?:\.\d+)?))\s*dBm\s+(-?(?:inf|\d+(?:\.\d+)?))\s*dBm/i', $line, $match)) {
            return null;
        }

        if (! $this->isValidPonOnu((int) $match[1], (int) $match[2]) || strtolower($match[5]) === '-inf') {
            return null;
        }

        return [
            'pon_port' => (int) $match[1],
            'onu_id' => (int) $match[2],
            'mac_address' => $match[3],
            'rx_power_dbm' => (float) $match[5],
        ];
    }

    private function parseAlarmHistoryLine(string $line): ?array
    {
        if (! preg_match('/^\[(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})\]\s+(Info|Warning):\s+(?:ONU|ONT)\s+(\d{1,2})\/(\d{1,3})\s+(\S+)\s+(.+?)(?:,\s*Reason:\s*(.*))?\s*$/i', $line, $match)) {
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
            'mac_address' => str_contains($match[5], ':') ? strtolower($match[5]) : $match[5],
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
        if (! preg_match('/^(?:ONU|ONT)\s*:\s*([1-8])[:\/](\d{1,3})\b/i', $line, $match)) {
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

    private function parseGponServicePortLine(string $line): ?array
    {
        if (! preg_match('/^\s*\d+\s+(\d+)\s+PON0?(\d{1,2})\s+(\d{1,3})\s+(\d+)\s+/i', $line, $match)) {
            return null;
        }

        $ponPort = (int) $match[2];
        $onuId = (int) $match[3];

        if (! $this->isValidPonOnu($ponPort, $onuId)) {
            return null;
        }

        return [
            'pon_port' => $ponPort,
            'onu_id' => $onuId,
            'port_vlan' => [
                'port' => (int) $match[4],
                'mode' => 'service-port',
                'vlan' => (int) $match[1],
                'priority' => null,
                'service_port' => (int) trim(strtok($line, ' ')),
            ],
            'port_vlan_key' => 'vlan-'.(int) $match[1],
        ];
    }

    private function parseLearnedMacLine(string $line): ?array
    {
        if (preg_match('/^\s*(\d+)\s+([0-9a-f]{2}(?::[0-9a-f]{2}){5})\s+(\d+)\s+PON0?(\d{1,2})\s+(\d{1,3})\s+(\d+)\s+(\S+)(?:\s+(.+?))?\s*$/i', $line, $match)) {
            $ponPort = (int) $match[4];
            $onuId = (int) $match[5];

            if (! $this->isValidPonOnu($ponPort, $onuId)) {
                return null;
            }

            return [
                'pon_port' => $ponPort,
                'onu_id' => $onuId,
                'learned_mac' => [
                    'mac' => strtolower($match[2]),
                    'vlan' => (int) $match[3],
                    'type' => strtolower($match[7]),
                    'onu_name' => trim($match[8] ?? ''),
                    'service_port' => (int) $match[1],
                    'gemport' => (int) $match[6],
                ],
                'port_vlan' => [
                    'port' => 1,
                    'mode' => 'learned-mac',
                    'vlan' => (int) $match[3],
                    'priority' => null,
                    'service_port' => (int) $match[1],
                    'gemport' => (int) $match[6],
                ],
                'port_vlan_key' => 'vlan-'.(int) $match[3],
            ];
        }

        if (! preg_match('/^(?:PON|EPON|GPON)\s*0?([1-8])\s+(\d{1,3})\s+([0-9a-f]{2}(?::[0-9a-f]{2}){5})\s+(\d+)\s+(\S+)(?:\s+(.+?))?\s*$/i', $line, $match)) {
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
            'learned_mac' => [
                'mac' => strtolower($match[3]),
                'vlan' => (int) $match[4],
                'type' => strtolower($match[5]),
                'onu_name' => trim($match[6] ?? ''),
            ],
        ];
    }

    private function parseOntDetailLine(string $line, array $currentRecord): ?array
    {
        return $this->parseOntDetailValueLine($line, $currentRecord);
    }

    private function parseOntDetailValueLine(string $line, array $currentRecord): ?array
    {
        if (! isset($currentRecord['pon_port'], $currentRecord['onu_id'])) {
            return null;
        }

        $record = [
            'pon_port' => (int) $currentRecord['pon_port'],
            'onu_id' => (int) $currentRecord['onu_id'],
        ];

        if (preg_match('/^ONU\s+Name\s*:\s*(.+)$/i', $line, $match)) {
            $record['name'] = trim($match[1]);
        } elseif (preg_match('/^SerialNumber\s*:\s*(\S+)/i', $line, $match)) {
            $record['mac_address'] = $match[1];
        } elseif (preg_match('/^Distance\s*:\s*(\d+)/i', $line, $match)) {
            $record['distance_m'] = (int) $match[1];
        } elseif (preg_match('/^Last\s+up\s+Time\s*:\s*(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})/i', $line, $match)) {
            $record['last_registered_at'] = $this->parseTimestamp($match[1]);
        } elseif (preg_match('/^Last\s+down\s+Time\s*:\s*(\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2})/i', $line, $match)) {
            $record['last_deregistered_at'] = $this->parseTimestamp($match[1]);
        } elseif (preg_match('/^Last\s+down\s+cause\s*:\s*(.+)$/i', $line, $match)) {
            $reason = trim($match[1]);

            if ($reason === '') {
                return null;
            }

            $record['last_deregister_reason'] = $reason;
            $record['description'] = $reason;
        } else {
            return null;
        }

        return $record;
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
        return $ponPort >= 1 && $ponPort <= 16 && $onuId >= 0 && $onuId <= 256;
    }

    private function isNoiseLine(string $line): bool
    {
        return preg_match('/^\[\d{4}\/\d{2}\/\d{2}\s+\d{2}:\d{2}:\d{2}\]\s+Warning:/i', $line)
            || preg_match('/^-{5,}$/', $line)
            || preg_match('/^(all|show\s+|pon\/onu\b|port\s+id\b)/i', $line);
    }

    private function mergeRecord(array $existing, array $incoming): array
    {
        if (isset($incoming['learned_macs'])) {
            $incoming['learned_macs'] = $this->mergeLearnedMacs($existing['learned_macs'] ?? [], $incoming['learned_macs']);
        }

        if (isset($incoming['port_vlans'])) {
            $incoming['port_vlans'] = $this->mergePortVlans($existing['port_vlans'] ?? [], $incoming['port_vlans']);
        }

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

    private function mergeLearnedMacs(array $existing, array $incoming): array
    {
        foreach ($incoming as $mac => $entry) {
            $existing[$mac] = $entry;
        }

        return $existing;
    }

    private function mergePortVlans(array $existing, array $incoming): array
    {
        foreach ($incoming as $key => $entry) {
            if (isset($existing[$key]) && ($entry['mode'] ?? null) === 'learned-mac') {
                continue;
            }

            $existing[$key] = $entry;
        }

        return $existing;
    }

    private function normalizeAlarmReason(string $message): string
    {
        return trim(preg_replace('/^ONU\s+/i', '', $message) ?? $message);
    }
}
