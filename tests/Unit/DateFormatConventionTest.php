<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DateFormatConventionTest extends TestCase
{
    #[DataProvider('userFacingPhpFiles')]
    public function test_user_facing_dates_use_day_month_year(string $file): void
    {
        $lines = file($file);

        foreach ($lines as $index => $line) {
            $isNativeDateInput = preg_match('/type\s*=\s*["\']date["\']/i', $line) === 1;

            if ($isNativeDateInput) {
                continue;
            }

            $this->assertDoesNotMatchRegularExpression(
                '/format\(\s*["\'](?:Y-m-d|d M Y)/',
                $line,
                $file.':'.($index + 1).' uses a non-standard displayed date.'
            );
        }
    }

    public static function userFacingPhpFiles(): iterable
    {
        $root = dirname(__DIR__, 2);

        foreach ([$root.'/resources/views', $root.'/app/Http/Controllers'] as $directory) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));

            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    yield $file->getPathname() => [$file->getPathname()];
                }
            }
        }

        yield $root.'/app/Services/PaymentService.php' => [$root.'/app/Services/PaymentService.php'];
    }
}
