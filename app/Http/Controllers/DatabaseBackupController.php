<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DatabaseBackupController extends Controller
{
    public function download(): StreamedResponse
    {
        $database = DB::getDatabaseName();
        $filename = $database.'_backup_'.now()->format('Ymd_His').'.sql';

        return response()->streamDownload(function () use ($database) {
            echo "-- Database backup for {$database}\n";
            echo '-- Generated at '.now()->toDateTimeString()."\n\n";
            echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

            $tableKey = 'Tables_in_'.$database;
            foreach (DB::select('SHOW TABLES') as $tableRow) {
                $table = $tableRow->{$tableKey};
                $create = DB::selectOne("SHOW CREATE TABLE `{$table}`");
                $createSql = $create->{'Create Table'};

                echo "DROP TABLE IF EXISTS `{$table}`;\n";
                echo $createSql.";\n\n";

                DB::table($table)->orderByRaw('1')->chunk(200, function ($rows) use ($table) {
                    foreach ($rows as $row) {
                        $columns = array_keys((array) $row);
                        $values = array_map(fn ($value) => $this->sqlValue($value), array_values((array) $row));

                        echo 'INSERT INTO `'.$table.'` (`'.implode('`, `', $columns).'`) VALUES ('.implode(', ', $values).");\n";
                    }
                });

                echo "\n";
            }

            echo "SET FOREIGN_KEY_CHECKS=1;\n";
        }, $filename, ['Content-Type' => 'application/sql']);
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return DB::getPdo()->quote((string) $value);
    }
}
