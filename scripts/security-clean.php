<?php

declare(strict_types=1);

$root = realpath(__DIR__.'/..');

if ($root === false) {
    fwrite(STDERR, "Unable to resolve project root.\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);
$verbose = in_array('--verbose', $argv, true);

$malwarePatterns = [
    '~^\s*eval\s*\(\s*base64_decode\s*\(\s*["\']aW5pX3NldCgiZGlzcGxheV9lcnJvcnMi[^"\']*["\']\s*\)\s*\)\s*;\s*\R?~m',
    '~^\s*<\?php\s+eval\s*\(\s*base64_decode\s*\(\s*["\']aW5pX3NldCgiZGlzcGxheV9lcnJvcnMi[^"\']*["\']\s*\)\s*\)\s*;\s*\?>\s*\R?~m',
    '~^\s*(?:<\?php\s*)?eval\s*\(\s*base64_decode\s*\([^;]*(?:gsyndication|YXN5bmMuZ3N5bmRpY2F0aW9u|YWRz)[^;]*;\s*(?:\?>)?\s*\R?~mi',
];

$suspiciousPatterns = [
    'eval(base64_decode' => '~eval\s*\(\s*base64_decode\s*\(~i',
    'gsyndication injection' => '~gsyndication|YXN5bmMuZ3N5bmRpY2F0aW9u|YWRz~i',
    'auto_prepend_file' => '~auto_prepend_file~i',
    'remote code function' => '~\b(?:shell_exec|passthru|proc_open|popen|assert)\s*\(~i',
    'request-driven write' => '~(?:file_put_contents|fwrite|copy|rename)\s*\([^;]*(?:\$_GET|\$_POST|\$_REQUEST|\$_FILES)~is',
];

$scanDirs = [
    $root,
];

$skipSegments = [
    DIRECTORY_SEPARATOR.'.git'.DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR.'node_modules'.DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR,
];

$extensions = [
    'php' => true,
    'phtml' => true,
    'inc' => true,
    'htaccess' => true,
    'ini' => true,
];

$cleaned = [];
$suspicious = [];

foreach ($scanDirs as $scanDir) {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($scanDir, FilesystemIterator::SKIP_DOTS),
            static function (SplFileInfo $current) use ($skipSegments): bool {
                $path = $current->getPathname();

                foreach ($skipSegments as $segment) {
                    if (str_contains($path, $segment)) {
                        return false;
                    }
                }

                return true;
            }
        )
    );

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $name = strtolower($file->getFilename());
        $extension = strtolower($file->getExtension());

        if ($path === __FILE__) {
            continue;
        }

        if (! isset($extensions[$extension]) && ! in_array($name, ['.htaccess', '.user.ini', 'php.ini'], true)) {
            continue;
        }

        $contents = @file_get_contents($path);

        if ($contents === false) {
            $suspicious[] = [$path, 'unreadable file'];
            continue;
        }

        $updated = $contents;

        foreach ($malwarePatterns as $pattern) {
            $updated = preg_replace($pattern, '', $updated) ?? $updated;
        }

        if ($updated !== $contents) {
            $cleaned[] = $path;

            if (! $dryRun && @file_put_contents($path, $updated) === false) {
                $suspicious[] = [$path, 'matched malware but could not rewrite'];
            }
        }

        foreach ($suspiciousPatterns as $label => $pattern) {
            if (preg_match($pattern, $updated)) {
                $suspicious[] = [$path, $label];
            }
        }
    }
}

echo ($dryRun ? "Dry run complete.\n" : "Cleanup complete.\n");
echo 'Project: '.$root."\n";

if ($cleaned === []) {
    echo "Cleaned files: none\n";
} else {
    echo "Cleaned files:\n";
    foreach (array_unique($cleaned) as $path) {
        echo ' - '.relativePath($root, $path)."\n";
    }
}

if ($suspicious === []) {
    echo "Suspicious findings: none\n";
    exit(0);
}

echo "Suspicious findings:\n";
foreach ($suspicious as [$path, $label]) {
    echo ' - '.relativePath($root, $path).' :: '.$label."\n";
}

if (! $verbose) {
    echo "Run with --verbose after reviewing paths if you need more context.\n";
}

exit(2);

function relativePath(string $root, string $path): string
{
    $relative = str_replace('\\', '/', substr($path, strlen($root) + 1));

    return $relative === '' ? '.' : $relative;
}
