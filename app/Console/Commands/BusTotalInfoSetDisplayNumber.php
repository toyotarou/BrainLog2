<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusTotalInfoSetDisplayNumber extends Command
{

    protected $signature = 'BusTotalInfoSetDisplayNumber';

    protected $description = 'Command description';

    public function handle()
    {

        // operator+line の一覧
        $routes = DB::table('t_bus_total_info')
            ->select('operator', 'line')
            ->groupBy('operator', 'line')
            ->orderBy('operator')->orderBy('line')
            ->get();

        if ($routes->isEmpty()) {
            $this->warn('対象路線が見つかりません。');
            return 0;
        }

        $this->info("対象路線: {$routes->count()} 件。display_num を更新します…");

        foreach ($routes as $r) {
            $operator = (string)($r->operator ?? '');
            $line     = (string)($r->line ?? '');

            // 該当路線の全レコード
            $rows = DB::table('t_bus_total_info')
                ->where('operator', $operator)
                ->where('line', $line)
                ->orderBy('order_num')
                ->get(['id','name','lat','lon','order_num']);

            if ($rows->isEmpty()) {
                continue;
            }

            // 位置あり / 位置なし に分ける
            $pts = [];   // 並べ替え対象（lat/lonあり）
            $nolat = []; // lat/lonなし（最後に旧order順で付与）
            foreach ($rows as $rw) {
                $lat = $this->toFloat($rw->lat);
                $lon = $this->toFloat($rw->lon);
                $ord = is_null($rw->order_num) ? PHP_INT_MAX : (int)$rw->order_num;
                if ($lat === null || $lon === null) {
                    $nolat[] = ['id'=>(int)$rw->id, 'order'=>$ord];
                } else {
                    $pts[] = [
                        'id'   => (int)$rw->id,
                        'lat'  => $lat,
                        'lon'  => $lon,
                        'ord'  => $ord,
                    ];
                }
            }

            $updates = [];

            if (count($pts) >= 2) {
                // 始点/終点：order_num 最小/最大を固定
                usort($pts, fn($a,$b)=>$a['ord']<=>$b['ord']);
                $startIdx = 0;
                $endIdx   = count($pts)-1;

                // 経路（ptsの添字）: [start, end] から開始
                $path = [$startIdx, $endIdx];

                // 未使用の中間点（添字）
                $unused = range(1, $endIdx-1);

                // Best Insertion（増分距離が最小になる位置に挿入）
                while (!empty($unused)) {
                    $bestGain = INF; $bestIns = null; $bestU = null;

                    foreach ($unused as $u) {
                        for ($i=0; $i<count($path)-1; $i++) {
                            $a = $path[$i];
                            $b = $path[$i+1];
                            $old = $this->hav($pts[$a]['lat'],$pts[$a]['lon'],$pts[$b]['lat'],$pts[$b]['lon']);
                            $new = $this->hav($pts[$a]['lat'],$pts[$a]['lon'],$pts[$u]['lat'],$pts[$u]['lon'])
                                 + $this->hav($pts[$u]['lat'],$pts[$u]['lon'],$pts[$b]['lat'],$pts[$b]['lon']);
                            $gain = $new - $old;
                            if ($gain < $bestGain) {
                                $bestGain = $gain; $bestIns = $i+1; $bestU = $u;
                            }
                        }
                    }
                    array_splice($path, $bestIns, 0, [$bestU]);
                    $unused = array_values(array_diff($unused, [$bestU]));
                }

                // display_num を 1..N で採番
                $seq = 1;
                foreach ($path as $pi) {
                    $updates[] = ['id'=>$pts[$pi]['id'], 'display_num'=>$seq++];
                }
            } else {
                // 位置が1以下の場合は旧order_num順で採番
                $seq = 1;
                foreach ($rows as $rw) {
                    $updates[] = ['id'=>(int)$rw->id, 'display_num'=>$seq++];
                }
            }

            // 位置なしは最後尾に旧order順で付与
            if (!empty($nolat)) {
                usort($nolat, fn($a,$b)=>$a['order']<=>$b['order']);
                $seq = count($updates) + 1;
                foreach ($nolat as $np) {
                    $updates[] = ['id'=>$np['id'], 'display_num'=>$seq++];
                }
            }

            // 1路線につき1本の UPDATE ... CASE で更新
            $this->applyUpdates($updates, $operator, $line);
        }

        $this->info('display_num の更新が完了しました。');
        return 0;

    }



    private function toFloat($v): ?float
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        if ($s === '') return null;
        if (is_numeric($s)) return (float)$s;
        if (preg_match('/[-+]?\d+(\.\d+)?/u', $s, $m)) return (float)$m[0];
        return null;
    }





    private function hav(float $lat1,float $lon1,float $lat2,float $lon2): float
    {
        $R=6371000.0; $to=M_PI/180.0;
        $dlat=($lat2-$lat1)*$to; $dlon=($lon2-$lon1)*$to;
        $a=sin($dlat/2)**2 + cos($lat1*$to)*cos($lat2*$to)*sin($dlon/2)**2;
        return 2*$R*atan2(sqrt(max(0,$a)), sqrt(max(0,1-$a)));
    }






    /**
     * 路線単位の一括 UPDATE（CASE 式）
     * @param array<int,array{id:int,display_num:int}> $updates
     */
    private function applyUpdates(array $updates, string $operator, string $line): void
    {
        if (empty($updates)) return;

        $ids = array_column($updates, 'id');

        // CASE 式とバインド生成
        $cases = [];
        $bindings = [];
        foreach ($updates as $u) {
            $cases[] = "WHEN ? THEN ?";
            $bindings[] = $u['id'];
            $bindings[] = $u['display_num'];
        }
        $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));
        $bindings = array_merge($bindings, $ids, [$operator, $line]);

        $sql = "
            UPDATE t_bus_total_info
               SET display_num = CASE id
                    ".implode(' ', $cases)."
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
            $this->error("UPDATE失敗 [{$operator} / {$line}] : ".$e->getMessage());
        }
    }




}
