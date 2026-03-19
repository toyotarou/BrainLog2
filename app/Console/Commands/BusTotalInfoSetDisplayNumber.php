<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusTotalInfoSetDisplayNumber extends Command
{
    protected $signature = 'BusTotalInfoSetDisplayNumber
                            {--operator= : 特定の事業者だけ処理}
                            {--line= : 特定の路線だけ処理}
                            {--dry-run : 更新せず結果だけ表示する}';

    protected $description = 't_bus_total_info の bus_stop_order_num を緯度経度ベースで高精度寄りに再採番する';

    public function handle()
    {
        $operatorOption = $this->option('operator');
        $lineOption = $this->option('line');
        $dryRun = (bool)$this->option('dry-run');

        $routesQuery = DB::table('t_bus_total_info')
            ->select('operator', 'line')
            ->groupBy('operator', 'line')
            ->orderBy('operator')
            ->orderBy('line');

        if (!empty($operatorOption)) {
            $routesQuery->where('operator', $operatorOption);
        }

        if (!empty($lineOption)) {
            $routesQuery->where('line', $lineOption);
        }

        $routes = $routesQuery->get();

        if ($routes->isEmpty()) {
            $this->warn('対象路線が見つかりません。');
            return 0;
        }

        $this->info("対象路線: {$routes->count()} 件。bus_stop_order_num を" . ($dryRun ? '試算' : '更新') . "します…");

        foreach ($routes as $r) {
            $operator = (string)($r->operator ?? '');
            $line = (string)($r->line ?? '');

            $this->line('');
            $this->info("処理中: {$operator} / {$line}");

            $rows = DB::table('t_bus_total_info')
                ->where('operator', $operator)
                ->where('line', $line)
                ->orderByRaw('CASE WHEN order_num IS NULL THEN 1 ELSE 0 END')
                ->orderBy('order_num')
                ->orderBy('id')
                ->get([
                    'id',
                    'name',
                    'lat',
                    'lon',
                    'order_num',
                    'display_num',
                    'bus_stop_order_num',
                ]);

            if ($rows->isEmpty()) {
                continue;
            }

            $pts = [];
            $nolat = [];

            foreach ($rows as $rw) {
                $lat = $this->toFloat($rw->lat);
                $lon = $this->toFloat($rw->lon);
                $ord = is_null($rw->order_num) ? PHP_INT_MAX : (int)$rw->order_num;

                if ($lat === null || $lon === null) {
                    $nolat[] = [
                        'id' => (int)$rw->id,
                        'name' => (string)($rw->name ?? ''),
                        'order_num' => $ord,
                    ];
                    continue;
                }

                $pts[] = [
                    'id' => (int)$rw->id,
                    'name' => (string)($rw->name ?? ''),
                    'lat' => $lat,
                    'lon' => $lon,
                    'order_num' => $ord,
                    'display_num' => is_null($rw->display_num) ? null : (int)$rw->display_num,
                    'bus_stop_order_num' => is_null($rw->bus_stop_order_num) ? null : (int)$rw->bus_stop_order_num,
                ];
            }

            $updates = [];

            if (count($pts) >= 2) {
                $orderedPts = $this->buildBestRouteOrder($pts);

                $seq = 1;
                foreach ($orderedPts as $pt) {
                    $updates[] = [
                        'id' => $pt['id'],
                        'bus_stop_order_num' => $seq++,
                    ];
                }
            } else {
                $seq = 1;
                foreach ($pts as $pt) {
                    $updates[] = [
                        'id' => $pt['id'],
                        'bus_stop_order_num' => $seq++,
                    ];
                }
            }

            if (!empty($nolat)) {
                usort($nolat, function ($a, $b) {
                    return $a['order_num'] <=> $b['order_num'];
                });

                $seq = count($updates) + 1;
                foreach ($nolat as $np) {
                    $updates[] = [
                        'id' => $np['id'],
                        'bus_stop_order_num' => $seq++,
                    ];
                }
            }

            if (!$dryRun) {
                $this->applyUpdates($updates, $operator, $line);
            }

            $this->outputPreview($operator, $line, $rows, $updates);
        }

        $this->info('');
        $this->info($dryRun ? 'dry-run が完了しました。' : 'bus_stop_order_num の更新が完了しました。');

        return 0;
    }

    /**
     * 高精度寄りの並び順生成
     *
     * 手順:
     * 1. 最遠2点を求める
     * 2. その両端からの nearest neighbor を両方向で作る
     * 3. PCA風の主軸投影ソートも候補にする
     * 4. 各候補に 2-opt をかける
     * 5. 総距離 + ジャンプ + 急カーブ + 端点名スコアで最良候補を採用
     *
     * @param  array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>  $pts
     * @return array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>
     */
    private function buildBestRouteOrder(array $pts): array
    {
        $count = count($pts);

        if ($count <= 2) {
            return $pts;
        }

        [$farA, $farB] = $this->findFarthestPairIndexes($pts);

        $candidates = [];

        // 候補1: 最遠端Aから最近傍
        $route1 = $this->buildNearestNeighborPath($pts, $farA);
        $route1 = $this->improvePathByTwoOpt($route1);
        $candidates[] = $route1;

        // 候補2: 最遠端Bから最近傍
        $route2 = $this->buildNearestNeighborPath($pts, $farB);
        $route2 = $this->improvePathByTwoOpt($route2);
        $candidates[] = $route2;

        // 候補3: PCA風主軸ソート
        $route3 = $this->buildProjectedAxisPath($pts);
        $route3 = $this->improvePathByTwoOpt($route3);
        $candidates[] = $route3;

        // 候補4: 主軸ソート逆順
        $route4 = array_reverse($route3);
        $route4 = $this->improvePathByTwoOpt($route4);
        $candidates[] = $route4;

        // 候補5: 旧 order_num 昇順をたたき台にして 2-opt
        $route5 = $pts;
        usort($route5, function ($a, $b) {
            return $a['order_num'] <=> $b['order_num'];
        });
        $route5 = $this->improvePathByTwoOpt($route5);
        $candidates[] = $route5;

        $bestRoute = $candidates[0];
        $bestScore = $this->scorePath($bestRoute);

        foreach ($candidates as $idx => $candidate) {
            $score = $this->scorePath($candidate);

            if ($score < $bestScore) {
                $bestScore = $score;
                $bestRoute = $candidate;
            }
        }

        return $bestRoute;
    }

    /**
     * 最遠2点
     *
     * @param  array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>  $pts
     * @return array{0:int,1:int}
     */
    private function findFarthestPairIndexes(array $pts): array
    {
        $maxDistance = -1.0;
        $bestI = 0;
        $bestJ = 1;

        $count = count($pts);

        for ($i = 0; $i < $count; $i++) {
            for ($j = $i + 1; $j < $count; $j++) {
                $distance = $this->hav(
                    $pts[$i]['lat'],
                    $pts[$i]['lon'],
                    $pts[$j]['lat'],
                    $pts[$j]['lon']
                );

                if ($distance > $maxDistance) {
                    $maxDistance = $distance;
                    $bestI = $i;
                    $bestJ = $j;
                }
            }
        }

        return [$bestI, $bestJ];
    }

    /**
     * 最近傍法
     *
     * @param  array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>  $pts
     * @return array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>
     */
    private function buildNearestNeighborPath(array $pts, int $startIndex): array
    {
        $count = count($pts);

        $ordered = [];
        $used = [];

        $currentIndex = $startIndex;
        $ordered[] = $pts[$currentIndex];
        $used[$currentIndex] = true;

        while (count($ordered) < $count) {
            $nearestIndex = null;
            $nearestDistance = INF;

            for ($i = 0; $i < $count; $i++) {
                if (isset($used[$i])) {
                    continue;
                }

                $distance = $this->hav(
                    $pts[$currentIndex]['lat'],
                    $pts[$currentIndex]['lon'],
                    $pts[$i]['lat'],
                    $pts[$i]['lon']
                );

                if ($distance < $nearestDistance) {
                    $nearestDistance = $distance;
                    $nearestIndex = $i;
                    continue;
                }

                if (abs($distance - $nearestDistance) < 0.001 && $nearestIndex !== null) {
                    if ($pts[$i]['order_num'] < $pts[$nearestIndex]['order_num']) {
                        $nearestIndex = $i;
                    }
                }
            }

            if ($nearestIndex === null) {
                break;
            }

            $ordered[] = $pts[$nearestIndex];
            $used[$nearestIndex] = true;
            $currentIndex = $nearestIndex;
        }

        return $ordered;
    }

    /**
     * PCA風の主軸投影ソート
     * 一本道路線に比較的強い
     *
     * @param  array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>  $pts
     * @return array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>
     */
    private function buildProjectedAxisPath(array $pts): array
    {
        $count = count($pts);

        $avgLat = 0.0;
        $avgLon = 0.0;

        foreach ($pts as $pt) {
            $avgLat += $pt['lat'];
            $avgLon += $pt['lon'];
        }

        $avgLat /= $count;
        $avgLon /= $count;

        // 簡易平面変換（小エリア前提）
        $xy = [];
        foreach ($pts as $idx => $pt) {
            $x = ($pt['lon'] - $avgLon) * cos(deg2rad($avgLat));
            $y = ($pt['lat'] - $avgLat);
            $xy[$idx] = ['x' => $x, 'y' => $y];
        }

        // 共分散
        $sxx = 0.0;
        $syy = 0.0;
        $sxy = 0.0;

        foreach ($xy as $p) {
            $sxx += $p['x'] * $p['x'];
            $syy += $p['y'] * $p['y'];
            $sxy += $p['x'] * $p['y'];
        }

        // 主軸方向
        $theta = 0.5 * atan2(2.0 * $sxy, $sxx - $syy);
        $ux = cos($theta);
        $uy = sin($theta);

        $projected = [];
        foreach ($pts as $idx => $pt) {
            $proj = $xy[$idx]['x'] * $ux + $xy[$idx]['y'] * $uy;
            $projected[] = [
                'pt' => $pt,
                'proj' => $proj,
            ];
        }

        usort($projected, function ($a, $b) {
            if ($a['proj'] < $b['proj']) {
                return -1;
            }
            if ($a['proj'] > $b['proj']) {
                return 1;
            }
            return $a['pt']['order_num'] <=> $b['pt']['order_num'];
        });

        $out = [];
        foreach ($projected as $row) {
            $out[] = $row['pt'];
        }

        return $out;
    }

    /**
     * open path 用 2-opt
     * 端点は固定し、中間の交差をほどく
     *
     * @param  array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>  $route
     * @return array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>
     */
    private function improvePathByTwoOpt(array $route): array
    {
        $n = count($route);

        if ($n < 4) {
            return $route;
        }

        $improved = true;
        $loopGuard = 0;

        while ($improved && $loopGuard < 20) {
            $improved = false;
            $loopGuard++;

            for ($i = 1; $i < $n - 2; $i++) {
                for ($k = $i + 1; $k < $n - 1; $k++) {
                    $a = $route[$i - 1];
                    $b = $route[$i];
                    $c = $route[$k];
                    $d = $route[$k + 1];

                    $before =
                        $this->hav($a['lat'], $a['lon'], $b['lat'], $b['lon']) +
                        $this->hav($c['lat'], $c['lon'], $d['lat'], $d['lon']);

                    $after =
                        $this->hav($a['lat'], $a['lon'], $c['lat'], $c['lon']) +
                        $this->hav($b['lat'], $b['lon'], $d['lat'], $d['lon']);

                    if ($after + 0.01 < $before) {
                        $middle = array_slice($route, $i, $k - $i + 1);
                        $middle = array_reverse($middle);
                        array_splice($route, $i, $k - $i + 1, $middle);
                        $improved = true;
                    }
                }
            }
        }

        return $route;
    }

    /**
     * 経路スコア
     * 小さいほど良い
     *
     * @param  array<int,array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}>  $route
     */
    private function scorePath(array $route): float
    {
        $count = count($route);

        if ($count <= 1) {
            return 0.0;
        }

        $segmentDistances = [];
        $totalDistance = 0.0;

        for ($i = 0; $i < $count - 1; $i++) {
            $dist = $this->hav(
                $route[$i]['lat'],
                $route[$i]['lon'],
                $route[$i + 1]['lat'],
                $route[$i + 1]['lon']
            );
            $segmentDistances[] = $dist;
            $totalDistance += $dist;
        }

        $median = $this->median($segmentDistances);

        // 大ジャンプ罰則
        $jumpPenalty = 0.0;
        if ($median > 0.0) {
            foreach ($segmentDistances as $dist) {
                if ($dist > $median * 2.5) {
                    $jumpPenalty += ($dist - $median * 2.5) * 8.0;
                }
            }
        }

        // 急角度罰則
        $turnPenalty = 0.0;
        for ($i = 1; $i < $count - 1; $i++) {
            $angle = $this->calcTurnAngleDeg($route[$i - 1], $route[$i], $route[$i + 1]);

            if ($angle < 70.0) {
                $turnPenalty += (70.0 - $angle) * 120.0;
            }
        }

        // 端点名スコア（駅、車庫、営業所などを少し優遇）
        $endpointBonus =
            $this->nameEndpointScore($route[0]['name']) +
            $this->nameEndpointScore($route[$count - 1]['name']);

        // 旧orderとの乖離罰則（弱く）
        $orderPenalty = 0.0;
        foreach ($route as $idx => $pt) {
            if ($pt['order_num'] !== PHP_INT_MAX) {
                $orderPenalty += abs(($idx + 1) - (int)$pt['order_num']) * 3.0;
            }
        }

        // 総合
        $score =
            $totalDistance +
            $jumpPenalty +
            $turnPenalty +
            $orderPenalty -
            ($endpointBonus * 300.0);

        return $score;
    }

    /**
     * 折れ角（0〜180度）
     *
     * @param  array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}  $a
     * @param  array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}  $b
     * @param  array{id:int,name:string,lat:float,lon:float,order_num:int,display_num:?int,bus_stop_order_num:?int}  $c
     */
    private function calcTurnAngleDeg(array $a, array $b, array $c): float
    {
        $lat0 = $b['lat'];

        $abx = ($a['lon'] - $b['lon']) * cos(deg2rad($lat0));
        $aby = ($a['lat'] - $b['lat']);

        $cbx = ($c['lon'] - $b['lon']) * cos(deg2rad($lat0));
        $cby = ($c['lat'] - $b['lat']);

        $len1 = sqrt($abx * $abx + $aby * $aby);
        $len2 = sqrt($cbx * $cbx + $cby * $cby);

        if ($len1 == 0.0 || $len2 == 0.0) {
            return 180.0;
        }

        $dot = $abx * $cbx + $aby * $cby;
        $cos = $dot / ($len1 * $len2);

        if ($cos < -1.0) {
            $cos = -1.0;
        }
        if ($cos > 1.0) {
            $cos = 1.0;
        }

        return rad2deg(acos($cos));
    }

    private function nameEndpointScore(string $name): int
    {
        $score = 0;

        if (mb_strpos($name, '駅') !== false) {
            $score += 5;
        }

        if (mb_strpos($name, '北口') !== false || mb_strpos($name, '南口') !== false) {
            $score += 3;
        }

        if (mb_strpos($name, '営業所') !== false || mb_strpos($name, '車庫') !== false) {
            $score += 4;
        }

        if (mb_strpos($name, '発') !== false || mb_strpos($name, '着') !== false) {
            $score += 2;
        }

        return $score;
    }

    /**
     * @param  array<int,float>  $values
     */
    private function median(array $values): float
    {
        if (empty($values)) {
            return 0.0;
        }

        sort($values);
        $count = count($values);
        $mid = intdiv($count, 2);

        if ($count % 2 === 0) {
            return ($values[$mid - 1] + $values[$mid]) / 2.0;
        }

        return $values[$mid];
    }

    private function toFloat($v): ?float
    {
        if ($v === null) {
            return null;
        }

        $s = trim((string)$v);

        if ($s === '') {
            return null;
        }

        if (is_numeric($s)) {
            return (float)$s;
        }

        if (preg_match('/[-+]?\d+(\.\d+)?/u', $s, $m)) {
            return (float)$m[0];
        }

        return null;
    }

    private function hav(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = 6371000.0;
        $to = M_PI / 180.0;

        $dlat = ($lat2 - $lat1) * $to;
        $dlon = ($lon2 - $lon1) * $to;

        $a =
            sin($dlat / 2) ** 2 +
            cos($lat1 * $to) * cos($lat2 * $to) * sin($dlon / 2) ** 2;

        return 2 * $R * atan2(sqrt(max(0.0, $a)), sqrt(max(0.0, 1.0 - $a)));
    }

    /**
     * @param  array<int,array{id:int,bus_stop_order_num:int}>  $updates
     */
    private function applyUpdates(array $updates, string $operator, string $line): void
    {
        if (empty($updates)) {
            return;
        }

        $ids = array_column($updates, 'id');

        $cases = [];
        $bindings = [];

        foreach ($updates as $u) {
            $cases[] = 'WHEN ? THEN ?';
            $bindings[] = $u['id'];
            $bindings[] = $u['bus_stop_order_num'];
        }

        $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids, [$operator, $line]);

        $sql = "
            UPDATE t_bus_total_info
               SET bus_stop_order_num = CASE id
                    " . implode(' ', $cases) . "
               END
             WHERE id IN ($inPlaceholders)
               AND operator = ?
               AND line = ?
        ";

        DB::beginTransaction();

        try {
            DB::statement($sql, $bindings);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error("UPDATE失敗 [{$operator} / {$line}] : " . $e->getMessage());
        }
    }

    /**
     * 確認用プレビュー
     */
    private function outputPreview(string $operator, string $line, $rows, array $updates): void
    {
        $map = [];
        foreach ($updates as $u) {
            $map[$u['id']] = $u['bus_stop_order_num'];
        }

        $previewRows = [];

        foreach ($rows as $rw) {
            $previewRows[] = [
                'id' => (int)$rw->id,
                'name' => (string)($rw->name ?? ''),
                'order_num' => is_null($rw->order_num) ? null : (int)$rw->order_num,
                'display_num' => is_null($rw->display_num) ? null : (int)$rw->display_num,
                'bus_stop_order_num' => $map[(int)$rw->id] ?? null,
            ];
        }

        usort($previewRows, function ($a, $b) {
            $aa = $a['bus_stop_order_num'] ?? PHP_INT_MAX;
            $bb = $b['bus_stop_order_num'] ?? PHP_INT_MAX;
            return $aa <=> $bb;
        });

        $this->line("---- {$operator} / {$line} preview ----");

        foreach ($previewRows as $pr) {
            $newNo = is_null($pr['bus_stop_order_num']) ? 'null' : str_pad((string)$pr['bus_stop_order_num'], 2, '0', STR_PAD_LEFT);
            $oldOrder = is_null($pr['order_num']) ? 'null' : (string)$pr['order_num'];
            $oldDisplay = is_null($pr['display_num']) ? 'null' : (string)$pr['display_num'];

            $this->line(
                "{$newNo} | id={$pr['id']} | {$pr['name']} | old_order={$oldOrder} | old_display={$oldDisplay}"
            );
        }
    }
}
