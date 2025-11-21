<?php
// app/Console/Commands/ImportRoutes.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRoutes extends Command
{
    // 例:
    // php artisan routes:import                         ← 既定 /tmp/routes を読む
    // php artisan routes:import --dir=/tmp/routes       ← ディレクトリ明示
    // php artisan routes:import --truncate              ← 既存データを空にしてから取込
    protected $signature = 'routes:import {--dir=/tmp/routes} {--truncate}';
    protected $description = 'routesディレクトリ内のCSV(路線ごとの推定順序)を読み込み、MySQLへ投入する（古いLaravel互換）';

    public function handle(): int
    {
        $dir = rtrim($this->option('dir') ?? '/tmp/routes', '/');
        $truncate = (bool)$this->option('truncate');

        if (!is_dir($dir)) {
            $this->error("ディレクトリが見つかりません: {$dir}");
            return 1;
        }

        // 事前クリア
        if ($truncate) {
            $this->warn('既存データをTRUNCATEします...');
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::table('t_bus_total_info_route_stops')->truncate();
            DB::table('t_bus_total_info_stops')->truncate();
            DB::table('t_bus_total_info_routes')->truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $expectedHeader = ['order','name','lat','lon','edId','point_id','operator','line'];

        $files = glob($dir.'/*.csv');
        if (!$files) {
            $this->warn('CSVが見つかりませんでした。');
            return 0;
        }

        $this->info('取り込み開始: '.count($files).' ファイル');

        // キャッシュ（高速化）
        $routeCache = []; // "operator|line" => id
        $stopCache  = []; // "p:<point_id>" or "n:<name>|<lat>|<lon>" => id

        $totalRows = 0;

        foreach ($files as $file) {
            $this->line("📄 {$file}");

            $fh = @fopen($file, 'r');
            if ($fh === false) {
                $this->warn("開けませんでした: {$file}");
                continue;
            }

            // ヘッダ
            $header = fgetcsv($fh);
            if (!$header) {
                fclose($fh);
                $this->warn('空ファイルの可能性: '.$file);
                continue;
            }
            $header = array_map('trim', $header);

            if ($header !== $expectedHeader) {
                $this->warn('ヘッダが想定外です。読み込んだヘッダ: '.implode(',', $header));
                $this->warn('期待ヘッダ: '.implode(',', $expectedHeader));
                // 続行はします（列名でアクセス）
            }

            // 1行目をpeekして route を確定
            $firstRow = fgetcsv($fh);
            if ($firstRow === false) {
                fclose($fh);
                $this->warn('データ行がありません: '.$file);
                continue;
            }
            $first = $this->rowAssoc($header, $firstRow);
            $operator = trim((string)($first['operator'] ?? ''));
            $line     = trim((string)($first['line'] ?? ''));
            if ($operator === '' || $line === '') {
                fclose($fh);
                $this->warn('operator/line が空のためスキップ: '.$file);
                continue;
            }

            // route upsert（互換: ON DUPLICATE KEY）
            $routeKey = $operator.'|'.$line;
            if (!isset($routeCache[$routeKey])) {
                $slug = $this->slugify($operator).'__'.$this->slugify($line);
                $this->upsertRoute($operator, $line, $slug);
                $routeId = (int) DB::table('t_bus_total_info_routes')
                    ->where('operator', $operator)
                    ->where('line', $line)
                    ->value('id');
                $routeCache[$routeKey] = $routeId;
            } else {
                $routeId = $routeCache[$routeKey];
            }

            // 先頭行も含めて全行処理するために巻き戻し
            rewind($fh);
            fgetcsv($fh); // header

            $batchPivot = [];
            $batchSize  = 1000;
            $lineCount  = 0;

            while (($row = fgetcsv($fh)) !== false) {
                $lineCount++;
                $assoc = $this->rowAssoc($header, $row);

                // 行データ
                $seq      = (int)($assoc['order'] ?? 0);
                $name     = trim((string)($assoc['name'] ?? ''));
                $lat      = $this->toFloatSafe($assoc['lat'] ?? null);
                $lon      = $this->toFloatSafe($assoc['lon'] ?? null);
                $edId     = trim((string)($assoc['edId'] ?? ''));
                $pointId  = trim((string)($assoc['point_id'] ?? ''));

                if ($name === '' || $lat === null || $lon === null || $seq <= 0) {
                    continue; // スキップ
                }

                // stop upsert（互換）
                $stopKey = ($pointId !== '') ? "p:{$pointId}" : "n:{$name}|{$lat}|{$lon}";
                if (!isset($stopCache[$stopKey])) {
                    $stopId = $this->upsertStopAndGetId($edId, $pointId, $name, $lat, $lon);
                    $stopCache[$stopKey] = $stopId;
                } else {
                    $stopId = $stopCache[$stopKey];
                }

                // pivot 行をためる
                $batchPivot[] = [
                    'route_id'  => $routeId,
                    'stop_id'   => $stopId,
                    'seq'       => $seq,
                ];

                // バルク投入
                if (count($batchPivot) >= $batchSize) {
                    $inserted = $this->bulkUpsertRouteStops($batchPivot);
                    $totalRows += $inserted;
                    $batchPivot = [];
                    $this->line("  ... upserted {$totalRows} rows so far");
                }
            }

            // 残り
            if (!empty($batchPivot)) {
                $inserted = $this->bulkUpsertRouteStops($batchPivot);
                $totalRows += $inserted;
            }

            fclose($fh);
            $this->info("✅ 完了: {$file}（行数: {$lineCount}）");
        }

        $this->info("🎉 取り込み完了。総レコード（pivot）: {$totalRows}");
        return 0;
    }

    /** routes: operator+line を一意に upsert（古いLaravel互換） */
    private function upsertRoute(string $operator, string $line, string $slug): void
    {
        $now = date('Y-m-d H:i:s');
        // UNIQUE KEY (`operator`,`line`) を前提
        $sql = "
            INSERT INTO t_bus_total_info_routes (`operator`,`line`,`slug`)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE `slug`=VALUES(`slug`)
        ";
        DB::statement($sql, [$operator, $line, $slug, $now, $now]);
    }

    /** stops を upsert して id を返す（古いLaravel互換） */
    private function upsertStopAndGetId(?string $edId, ?string $pointId, string $name, float $lat, float $lon): int
    {
        $now = date('Y-m-d H:i:s');

        if ($pointId !== null && $pointId !== '') {
            // UNIQUE KEY (point_id)
            $sql = "
                INSERT INTO t_bus_total_info_stops (`ed_id`,`point_id`,`name`,`lat`,`lon`)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `ed_id`=VALUES(`ed_id`),
                    `name`=VALUES(`name`),
                    `lat`=VALUES(`lat`),
                    `lon`=VALUES(`lon`)
            ";
            DB::statement($sql, [$edId ?: null, $pointId, $name, $lat, $lon, $now, $now]);

            // id 取得
            $id = (int) DB::table('t_bus_total_info_stops')->where('point_id', $pointId)->value('id');
            return $id;
        } else {
            // UNIQUE KEY (name,lat,lon)
            $sql = "
                INSERT INTO t_bus_total_info_stops (`ed_id`,`point_id`,`name`,`lat`,`lon`)
                VALUES (?, NULL, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    `ed_id`=VALUES(`ed_id`)
            ";
            DB::statement($sql, [$edId ?: null, $name, $lat, $lon, $now, $now]);

            $id = (int) DB::table('t_bus_total_info_stops')
                ->where('name', $name)
                ->where('lat', $lat)
                ->where('lon', $lon)
                ->value('id');
            return $id;
        }
    }

    /**
     * route_stops のバルク upsert（古いLaravel互換）
     * UNIQUE KEY(route_id, stop_id) を前提に seq を更新。
     */
    private function bulkUpsertRouteStops(array $rows): int
    {
        if (empty($rows)) return 0;

        // 例: INSERT INTO t_bus_total_info_route_stops (route_id,stop_id,seq)
        //     VALUES (?,?,?,?,?),(?,?,?,?,?),...
        //     ON DUPLICATE KEY UPDATE seq=VALUES(seq)
        $sql = "INSERT INTO t_bus_total_info_route_stops (`route_id`,`stop_id`,`seq`) VALUES ";
        $placeholders = [];
        $params = [];
        foreach ($rows as $r) {
            $placeholders[] = "(?, ?, ?)";
            $params[] = $r['route_id'];
            $params[] = $r['stop_id'];
            $params[] = $r['seq'];
        }
        $sql .= implode(',', $placeholders);
        $sql .= " ON DUPLICATE KEY UPDATE `seq`=VALUES(`seq`)";

        DB::statement($sql, $params);
        return count($rows);
    }

    /** CSVヘッダと行配列から連想配列に変換 */
    private function rowAssoc(array $header, array $row): array
    {
        $out = [];
        $n = min(count($header), count($row));
        for ($i=0; $i<$n; $i++) {
            $out[$header[$i]] = $row[$i];
        }
        return $out;
    }

    private function slugify(string $s): string
    {
        $s = preg_replace('/[\/\\\:\*\?\"\<\>\|]/', '_', $s);
        $s = preg_replace('/\s+/', '_', $s);
        return $s;
    }

    private function toFloatSafe($v): ?float
    {
        if ($v === null) return null;
        $v = trim((string)$v);
        if ($v === '') return null;
        if (is_numeric($v)) return (float)$v;
        if (preg_match('/[-+]?\d+(\.\d+)?/u', $v, $m)) return (float)$m[0];
        return null;
    }
}
