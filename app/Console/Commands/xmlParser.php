<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class XmlParser extends Command
{
    // 例:
    // php /var/www/html/BrainLog/artisan XmlParser --csv=/tmp/bus_stops.csv
    // php /var/www/html/BrainLog/artisan XmlParser --routes=/tmp/routes
    // php /var/www/html/BrainLog/artisan XmlParser --inspect-id=n10
    protected $signature = 'XmlParser {--inspect-id=} {--csv=} {--routes=}';
    protected $description = '停留所(ED01)と座標(GM_Point)の突き合わせ / 全件CSV / 路線ごとの推定順序CSV';

    public function handle()
    {
        $url       = "http://toyohide.work/test/20251016/bus_info.xml";
        $inspectId = $this->option('inspect-id');
        $csvPath   = $this->option('csv');
        $routesDir = $this->option('routes');

        if ($url === '') {
            $this->error('ERROR: URL が未設定です。');
            return 1;
        }

        $this->info("Fetching: {$url}");

        // 1) ダウンロード
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 60,
                    'header'  => "Accept: application/xml, text/xml, */*\r\n",
                ],
            ]);
            $body = @file_get_contents($url, false, $context);
            if ($body === false) {
                $this->error('取得に失敗しました。URL/ネットワークをご確認ください。');
                return 1;
            }

            global $http_response_header;
            $this->line('--- HTTP Response Header ---');
            if (isset($http_response_header)) {
                foreach ($http_response_header as $h) $this->line($h);
            } else {
                $this->line('(ヘッダ情報なし)');
            }

            $len = strlen($body);
            $snippet = mb_substr($body, 0, 800);
            $this->line("\n--- Snippet (先頭800文字) ---");
            $this->line($snippet);
            $this->line("\nBody bytes: {$len}");
            $this->info('OK: 読み込み成功。');
        } catch (\Throwable $e) {
            $this->error('例外発生: '.$e->getMessage());
            return 1;
        }

        // 2) 全文パース
        $enc  = mb_detect_encoding($body, ['UTF-8','SJIS-win','EUC-JP','ISO-2022-JP','CP932'], true) ?: 'UTF-8';
        $text = $enc === 'UTF-8' ? $body : mb_convert_encoding($body, 'UTF-8', $enc);
        $text = ltrim($text, "\xEF\xBB\xBF");

        libxml_use_internal_errors(true);
        $this->line("\nParsing with simplexml_load_string() ...");
        $xml = simplexml_load_string($text, 'SimpleXMLElement', LIBXML_NONET | LIBXML_COMPACT);
        if ($xml === false) {
            $this->error('XML パースエラー:');
            foreach (libxml_get_errors() as $err) $this->warn(trim($err->message));
            libxml_clear_errors();
            return 1;
        }

        // 名前空間登録
        $namespaces = $xml->getDocNamespaces(true) ?: [];
        $xml->registerXPathNamespace('ksj', $namespaces['ksj'] ?? 'http://nlftp.mlit.go.jp/ksj/schemas/ksj-app');
        $xml->registerXPathNamespace('jps', $namespaces['jps'] ?? 'http://www.gsi.go.jp/GIS/jpgis/standardSchemas');

        // 3) GM_Point を辞書化 (id => [lat, lon])
        $this->line("\nIndexing GM_Point (id => lat/lon) ...");
        $pointById = []; // id => [lat, lon]
        $pointXml  = []; // id => SimpleXMLElement
        $gmPoints = $xml->xpath('//jps:GM_Point') ?: [];
        foreach ($gmPoints as $pt) {
            /** @var \SimpleXMLElement $pt */
            $pid = (string)($pt['id'] ?? '');
            if ($pid === '') continue;
            $coordText = $this->firstNonEmptyText($pt, [
                'jps:GM_Point.position/jps:DirectPosition/DirectPosition.coordinate',
                'jps:GM_Point.position/jps:DirectPosition/jps:DirectPosition.coordinate',
                'jps:GM_Point.position/DirectPosition/coordinate',
                'jps:GM_Point.position/jps:DirectPosition/jps:coordinate',
            ]);
            if ($coordText === null) continue;
            [$lat, $lon] = $this->parseLatLon($coordText); // このXMLは「lat lon」順
            if ($lat !== null && $lon !== null) {
                $pointById[$pid] = [$lat, $lon];
                $pointXml[$pid]  = $pt;
            }
        }
        $this->line('GM_Point indexed: '.count($pointById));

        // 4) ED01 を走査し、名称・idref・事業者/路線を集約
        $this->line("Indexing ED01 (stops) ...");
        $edList = $xml->xpath('//ksj:ED01') ?: [];
        $this->line('ED01 elements: '.count($edList));

        $doCsv = $csvPath !== null;
        if ($doCsv && $csvPath === '') $csvPath = storage_path('app/bus_stops.csv');
        $fh = null;
        if ($doCsv) {
            $dir = dirname($csvPath);
            if (!is_dir($dir)) @mkdir($dir, 0777, true);
            $fh = @fopen($csvPath, 'w');
            if ($fh === false) {
                $this->error("CSVを書き込めません: {$csvPath}");
                return 1;
            }
            fputcsv($fh, ['edId', 'name', 'lat', 'lon', 'point_id', 'operators', 'lines']);
        }

        // 路線グルーピング: key = operator|line （事業者×路線名）
        $routes = []; // key => [ [name, lat, lon, edId, point_id, operator, line], ... ]
        $printed = 0;
        $this->line("\nPreview (first 10) of stops with resolved coordinates:");
        foreach ($edList as $ed) {
            /** @var \SimpleXMLElement $ed */
            $name = $this->firstNonEmptyText($ed, ['ksj:BSN']) ?? '(名称不明)';
            $edId = (string)($ed['id'] ?? '');

            $posNodes = $ed->xpath('ksj:POS');
            $idref = null;
            if ($posNodes && isset($posNodes[0])) $idref = (string)($posNodes[0]['idref'] ?? '');

            $lat = $lon = null;
            if ($idref && isset($pointById[$idref])) {
                [$lat, $lon] = $pointById[$idref];
            }

            // 事業者/路線（複数 BRI があり得る）
            $bris = $ed->xpath('ksj:BRI') ?: [];
            $operators = [];
            $linesAll  = [];
            foreach ($bris as $bri) {
                $boc = trim((string)($bri->xpath('ksj:BOC')[0] ?? ''));
                $bln = trim((string)($bri->xpath('ksj:BLN')[0] ?? ''));
                if ($boc !== '') $operators[$boc] = true;
                if ($bln !== '') $linesAll[] = $bln;

                // 路線グループ用に蓄積（座標が取れている場合のみ）
                if ($lat !== null && $lon !== null && $boc !== '' && $bln !== '') {
                    $key = $boc.'|'.$bln;
                    $routes[$key][] = [
                        'name' => $name,
                        'lat'  => $lat,
                        'lon'  => $lon,
                        'edId' => $edId,
                        'point_id' => $idref,
                        'operator' => $boc,
                        'line' => $bln,
                    ];
                }
            }

            if ($printed < 10) {
                if ($lat !== null && $lon !== null) {
                    $this->line(sprintf('• %s  lat=%.6f  lon=%.6f  (idref=%s edId=%s)', $name, $lat, $lon, $idref, $edId));
                } else {
                    $this->line(sprintf('• %s  (座標未解決; idref=%s edId=%s)', $name, $idref ?? '-', $edId));
                }
                $printed++;
            }

            if ($doCsv) {
                fputcsv($fh, [
                    $edId,
                    $name,
                    $lat !== null ? sprintf('%.9f', $lat) : '',
                    $lon !== null ? sprintf('%.9f', $lon) : '',
                    $idref ?? '',
                    implode('|', array_keys($operators)),
                    implode('|', $linesAll),
                ]);
            }
        }

        if ($fh) {
            fclose($fh);
            $this->info("\nCSVを書き出しました: {$csvPath}");
            $this->info("行数の目安: ".(count($edList)+1)." 行（ヘッダ含む）");
        }

        // 5) 路線ごとの推定順序CSV
        $doRoutes = $routesDir !== null;
        if ($doRoutes && $routesDir === '') $routesDir = storage_path('app/routes');
        if ($doRoutes) {
            if (!is_dir($routesDir)) @mkdir($routesDir, 0777, true);

            $this->info("\nExporting per-route CSV with PCA-based ordering (heuristic) ...");
            $countFiles = 0;

            foreach ($routes as $key => $stops) {
                if (count($stops) < 2) continue; // 並べようがない

                // PCA で主軸を求め、投影値でソート
                $ordered = $this->pcaSort($stops);

                // ファイル名: 事業者__路線.csv （記号を安全化）
                [$boc, $bln] = explode('|', $key, 2);
                $safe = function(string $s) {
                    $s = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $s);
                    return preg_replace('/\s+/', '_', $s);
                };
                $fname = $routesDir.'/'.$safe($boc).'__'.$safe($bln).'.csv';

                $f = @fopen($fname, 'w');
                if ($f === false) {
                    $this->warn("書き込み不可: {$fname}");
                    continue;
                }
                fputcsv($f, ['order', 'name', 'lat', 'lon', 'edId', 'point_id', 'operator', 'line']);
                $ord = 1;
                foreach ($ordered as $st) {
                    fputcsv($f, [
                        $ord++,
                        $st['name'],
                        sprintf('%.9f', $st['lat']),
                        sprintf('%.9f', $st['lon']),
                        $st['edId'],
                        $st['point_id'],
                        $st['operator'],
                        $st['line'],
                    ]);
                }
                fclose($f);
                $countFiles++;
            }

            $this->info("路線ファイルを書き出しました: {$routesDir} （合計 {$countFiles} 本）");
            $this->line("例: head -n 5 {$routesDir}/都営バス__渋66.csv");
            $this->warn("※ 注意: PCAによる推定順のため、ループ路線/枝分かれでは誤ることがあります。GTFSのstop_times等があればそれを優先してください。");
        }

        // 6) 個別IDの詳細（任意）
        if ($inspectId) {
            $this->info("\n[Inspect] id='{$inspectId}' の詳細");
            // 逆参照（どのED01が参照しているか）
            $refEd = $xml->xpath("//ksj:ED01[ksj:POS[@idref='{$inspectId}']]");
            if ($refEd) {
                foreach ($refEd as $ed) {
                    $nm  = $this->firstNonEmptyText($ed, ['ksj:BSN']) ?? '(名称不明)';
                    $eid = (string)($ed['id'] ?? '');
                    $this->line(sprintf("  • name=%s  edId=%s", $nm, $eid));
                }
            } else {
                $this->line("  (no ED01 referencing this id)");
            }
        }

        $this->info("\nOK: 全件CSV / 路線ごとの推定順序CSV まで完了。");
        return 0;
    }

    /** 候補XPathを順に試し、最初に得られた non-empty テキストを返す */
    private function firstNonEmptyText(\SimpleXMLElement $base, array $paths): ?string
    {
        foreach ($paths as $xp) {
            $nodes = $base->xpath($xp);
            if ($nodes && isset($nodes[0])) {
                $txt = trim((string)$nodes[0]);
                if ($txt !== '') return $txt;
            }
        }
        return null;
    }

    /** "35.70189169 139.63615728" / "35.70,139.63" → (lat, lon) */
    private function parseLatLon(string $s): array
    {
        $parts = preg_split('/[\s,]+/', trim($s));
        if (!$parts || count($parts) < 2) return [null, null];
        $lat = $this->toFloatSafe($parts[0]); // 先頭が緯度
        $lon = $this->toFloatSafe($parts[1]); // 次が経度
        return ($lat !== null && $lon !== null) ? [$lat, $lon] : [null, null];
    }

    private function toFloatSafe($v): ?float
    {
        $v = trim((string)$v);
        if ($v === '') return null;
        if (is_numeric($v)) return (float)$v;
        if (preg_match('/[-+]?\d+(\.\d+)?/u', $v, $m)) return (float)$m[0];
        return null;
    }

    /**
     * PCAで主軸ソート: stops[] = ['lat'=>..,'lon'=>..,...]
     * 1) 平均を引く 2) 共分散行列 3) 固有ベクトル(最大固有値) 4) 投影値で昇順
     * ループ路線や枝分かれでは誤ることがあります。
     */
    private function pcaSort(array $stops): array
    {
        $n = count($stops);
        if ($n <= 2) return $stops;

        // 緯度経度をx,yに（x=lon, y=lat）
        $xs = []; $ys = [];
        foreach ($stops as $s) { $xs[] = $s['lon']; $ys[] = $s['lat']; }
        $mx = array_sum($xs)/$n;
        $my = array_sum($ys)/$n;

        // 共分散
        $sxx = 0.0; $syy = 0.0; $sxy = 0.0;
        for ($i=0; $i<$n; $i++) {
            $dx = $xs[$i] - $mx;
            $dy = $ys[$i] - $my;
            $sxx += $dx*$dx;
            $syy += $dy*$dy;
            $sxy += $dx*$dy;
        }
        $sxx /= $n; $syy /= $n; $sxy /= $n;

        // 2x2 の最大固有値側の固有ベクトル
        $tr = $sxx + $syy;
        $det = $sxx*$syy - $sxy*$sxy;
        $tmp = sqrt(max(0.0, $tr*$tr/4 - $det));
        $lambda1 = $tr/2 + $tmp;
        // (A - λI)v = 0 → (sxx-λ)*vx + sxy*vy = 0
        $vx = 1.0; $vy = 0.0;
        if (abs($sxy) > 1e-12 || abs($sxx - $lambda1) > 1e-12) {
            // vy を 1 として vx を解く or 逆
            if (abs($sxy) > abs($sxx - $lambda1)) {
                $vy = 1.0;
                $vx = -$sxy / max(1e-12, $sxx - $lambda1);
            } else {
                $vx = 1.0;
                $vy = -max(1e-12, $sxx - $lambda1) / max(1e-12, $sxy);
            }
        }
        // 正規化
        $norm = sqrt($vx*$vx + $vy*$vy);
        if ($norm > 0) { $vx /= $norm; $vy /= $norm; }

        // 投影値 t = v·(p - mean)
        foreach ($stops as &$s) {
            $dx = $s['lon'] - $mx;
            $dy = $s['lat'] - $my;
            $s['_t'] = $vx*$dx + $vy*$dy;
        }
        unset($s);

        usort($stops, function($a,$b){ return $a['_t'] <=> $b['_t']; });
        foreach ($stops as &$s) unset($s['_t']);
        return $stops;
    }
}
