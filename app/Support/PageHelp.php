<?php

namespace App\Support;

use Illuminate\Support\Str;

final class PageHelp
{
    /**
     * Build the help content for the current named route.
     *
     * @return array{title:string, intro:string, features:array<int,string>, steps:array<int,string>, notes:array<int,string>}
     */
    public static function forRoute(?string $routeName): array
    {
        $routeName = $routeName ?: 'unknown';
        $defaultModule = config('page_help.default_module', []);
        $defaultMode = config('page_help.default_mode', []);
        $module = self::firstMatch(config('page_help.modules', []), $routeName) ?? $defaultModule;
        $mode = self::firstMatch(config('page_help.modes', []), $routeName) ?? $defaultMode;
        $override = self::firstMatch(config('page_help.pages', []), $routeName) ?? [];

        return [
            'title' => $override['title'] ?? trim(($module['title'] ?? 'পেজ নির্দেশিকা').' — '.($mode['title'] ?? 'ব্যবহার পদ্ধতি')),
            'intro' => $override['intro'] ?? trim(($module['purpose'] ?? '').' '.($mode['description'] ?? '')),
            'features' => array_values($override['features'] ?? $module['features'] ?? []),
            'steps' => array_values($override['steps'] ?? $mode['steps'] ?? []),
            'notes' => array_values($override['notes'] ?? $module['notes'] ?? []),
        ];
    }

    /**
     * @param  array<string,array<string,mixed>>  $definitions
     * @return array<string,mixed>|null
     */
    private static function firstMatch(array $definitions, string $routeName): ?array
    {
        foreach ($definitions as $pattern => $definition) {
            foreach (explode('|', $pattern) as $candidate) {
                if (Str::is($candidate, $routeName)) {
                    return $definition;
                }
            }
        }

        return null;
    }
}
