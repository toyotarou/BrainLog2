<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GetPhotoScanningInfo extends Command
{
    /**
     * artisan コマンド名
     */
    protected $signature = 'getPhotoScanningInfo';

    /**
     * 説明
     */
    protected $description = 'Scan public/UPPHOTO (sorted by year/date) and preview taken datetime + GPS from EXIF';

    /**
     * プレビュー表示枚数（0で無制限）
     */
    private const PREVIEW_LIMIT = 30;

    /**
     * 対象拡張子
     */
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function handle()
    {
        $baseDir = public_path('UPPHOTO');

        if (!is_dir($baseDir)) {
            $this->error("Directory not found: {$baseDir}");
            return 1;
        }

        $this->info("Scanning: {$baseDir}");
        $this->info("Preview limit: " . self::PREVIEW_LIMIT);

        if (!function_exists('exif_read_data')) {
            $this->warn('php-exif is not enabled. EXIF info will be skipped.');
        }

        $this->scanSorted($baseDir);

        $this->info('Done.');
        return 0;
    }

    /**
     * 年→日付→ファイルをソートして走査
     */
    private function scanSorted(string $baseDir): void
    {

////////////////////////////////////////////
$skipStr = "
2014-05-03
2014-05-31
2014-06-14
2014-07-06
2014-07-26
2014-09-21
2014-10-10
2014-11-23
2014-12-02
2014-12-07
2014-12-10
2014-12-13
2014-12-24
2014-12-25
2014-12-27
2014-12-28
2015-01-02
2015-06-13
2015-10-11
2015-10-17
2015-11-07
2015-11-13
2015-11-15
2015-12-10
2015-12-11
2015-12-19
2016-01-02
2016-01-04
2016-02-15
2016-02-16
2016-05-04
2016-07-02
2016-10-18
2016-12-01
2016-12-18
2017-02-17
2017-05-13
2017-11-08
2017-12-01
2017-12-10
2017-12-17
2017-12-31
2018-01-24
2018-05-13
2018-05-25
2018-08-04
2018-11-26
2018-11-30
2018-12-02
2018-12-16
2018-12-31
2019-01-05
2019-11-03
2019-11-12
2019-11-30
2019-12-04
2019-12-15
2019-12-31
";

$skipStr2 = "
2014-10-14_008_l.jpg
2015-01-01_001_l.jpg
2015-01-01_017_l.jpg
2015-01-01_018_l.jpg
2015-01-01_019_l.jpg
2015-01-01_021_l.jpg
2015-01-01_028_l.jpg
2015-01-01_030_l.jpg
2015-01-01_037_l.jpg
2015-01-01_038_l.jpg
2015-01-01_042_l.jpg
2017-03-11_011_l.jpg
2018-01-02_001_l.jpg
2018-01-02_002_l.jpg
2018-01-02_004_l.jpg
2018-08-14_010_l.jpg
2019-11-18_014_l.jpg
2019-11-26_014_l.jpg
2019-12-29_005_l.jpg
2020-01-01_001_l.jpg
2020-01-01_002_l.jpg
2020-01-01_003_l.jpg
2020-01-01_004_l.jpg
2020-01-01_014_l.jpg
2020-01-02_001_l.jpg
2020-01-02_002_l.jpg
20230926_083701686.jpg
20230926_083711275.jpg
20230926_083721150.jpg
20230926_083733497.jpg
20230926_102649185.jpg
20230926_102823879.jpg
20230927_081138645.jpg
20230927_084014501.jpg
20230927_084738444.jpg
20230927_155452666.jpg
20230927_155513105.jpg
20230927_155655596.jpg
20230927_155752870.jpg
20230927_161008583.jpg
20230927_161227707.jpg
20230927_161713329.jpg
20230927_161807876.jpg
";

$exSkip = explode("\n", $skipStr);
$exSkip2 = explode("\n", $skipStr2);

$skipList = [];
$skipList2 = [];

foreach($exSkip as $v){
if(trim($v) == ""){continue;}
$skipList[] = trim($v);
}
foreach($exSkip2 as $v){
if(trim($v) == ""){continue;}
$skipList2[] = trim($v);
}

////////////////////////////////////////////

        $baseDir = rtrim($baseDir, '/');

        $years = $this->listDirsSorted($baseDir, '/^\d{4}$/');

        $previewCount = 0;

        foreach ($years as $year) {
            $yearDir = $baseDir . '/' . $year;

            // 日付フォルダ（YYYY-MM-DD）を昇順
            $dates = $this->listDirsSorted($yearDir, '/^\d{4}-\d{2}-\d{2}$/');

            foreach ($dates as $dateDir) {
                $datePath = $yearDir . '/' . $dateDir;

                // ファイル一覧（拡張子で絞る）
                $files = $this->listFilesSorted($datePath);

                foreach ($files as $filename) {
                    $fullPath = $datePath . '/' . $filename;
                    $relative = $year . '/' . $dateDir . '/' . $filename;

                    $taken = $this->getBestTakenAt($fullPath, $dateDir, $filename);
                    $gps = $this->getGpsFromExif($fullPath);
                    $gpsText = $gps ? "{$gps['lat']},{$gps['lng']}" : 'null';

            /*
            $this->line(
            "taken=" . ($taken ?? 'null')
            . " | gps=" . $gpsText
            . " | path=" . $relative
            );
            */

            /*
            $exTaken = explode(" ", $taken);
            $insert['date'] = trim($exTaken[0]);
            $insert['time'] = trim(explode(":", $exTaken[1])[0]) . ":" . trim(explode(":", $exTaken[1])[1]);

            $exGps = explode(",", $gpsText);
            if(count($exGps)>=2){
            $insert['lat'] = trim($exGps[0]);
            $insert['lng'] = trim($exGps[1]);
            }
            */

$exRelative = explode("/", trim($relative));

            // 0    1          2
            // 2014/2014-11-23/2014-11-23_013_l.jpg

if(in_array(trim($exRelative[1]), $skipList)){continue;}
if(in_array(trim($exRelative[2]), $skipList2)){continue;}

$insert = [];
$insert['path'] = "http://160.16.145.135/BrainLog/UPPHOTO/" . trim($relative);
$insert['date'] = trim($exRelative[1]);

print_r($insert);

                    if (self::PREVIEW_LIMIT > 0 && ++$previewCount >= self::PREVIEW_LIMIT) {
                        $this->info('Preview limit reached. Stop scanning.');
                        return;
                    }
                }
            }
        }
    }

    /**
     * 指定ディレクトリ直下の「ディレクトリ名」をソートして返す
     */
    private function listDirsSorted(string $dir, string $nameRegex): array
    {
        $items = @scandir($dir);
        if (!is_array($items)) {
            return [];
        }

        $dirs = [];
        foreach ($items as $name) {
            if ($name === '.' || $name === '..') continue;

            $path = $dir . '/' . $name;
            if (!is_dir($path)) continue;

            if (!preg_match($nameRegex, $name)) continue;

            $dirs[] = $name;
        }

        sort($dirs, SORT_STRING);
        return $dirs;
    }

    /**
     * 指定ディレクトリ直下の画像ファイルをソートして返す
     */
    private function listFilesSorted(string $dir): array
    {
        $items = @scandir($dir);
        if (!is_array($items)) {
            return [];
        }

        $files = [];
        foreach ($items as $name) {
            if ($name === '.' || $name === '..') continue;

            $path = $dir . '/' . $name;
            if (!is_file($path)) continue;

            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, self::ALLOWED_EXT, true)) continue;

            $files[] = $name;
        }

        // 数字を含むファイル名でも見やすい順序に
        natcasesort($files);
        return array_values($files);
    }

    /* ===== 以下、日時 & GPS 補助関数 ===== */

    private function getBestTakenAt(string $fullPath, string $dateDir, string $filename): ?string
    {
        if ($taken = $this->getTakenAtFromExif($fullPath)) {
            return $taken;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDir)) {
            return $dateDir . ' 00:00:00';
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})_/', $filename, $m)) {
            return $m[1] . ' 00:00:00';
        }

        return null;
    }

    private function getTakenAtFromExif(string $path): ?string
    {
        if (!function_exists('exif_read_data')) return null;

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'], true)) return null;

        $exif = @exif_read_data($path, 'EXIF', true);
        if (!is_array($exif)) return null;

        $candidates = [
            $exif['EXIF']['DateTimeOriginal'] ?? null,
            $exif['EXIF']['CreateDate'] ?? null,
            $exif['IFD0']['DateTime'] ?? null,
        ];

        foreach ($candidates as $v) {
            if (!is_string($v) || $v === '') continue;

            // "YYYY:MM:DD HH:MM:SS" -> "YYYY-MM-DD HH:MM:SS"
            if (preg_match('/^\d{4}:\d{2}:\d{2} \d{2}:\d{2}:\d{2}$/', $v)) {
                return str_replace(':', '-', substr($v, 0, 10)) . substr($v, 10);
            }

            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $v)) {
                return $v;
            }
        }

        return null;
    }

    private function getGpsFromExif(string $path): ?array
    {
        if (!function_exists('exif_read_data')) return null;

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'], true)) return null;

        $exif = @exif_read_data($path, 'GPS', true);
        if (!isset($exif['GPS'])) return null;

        $g = $exif['GPS'];

        if (
            !isset($g['GPSLatitude'], $g['GPSLatitudeRef'],
                $g['GPSLongitude'], $g['GPSLongitudeRef'])
        ) {
            return null;
        }

        $lat = $this->gpsToDecimal($g['GPSLatitude'], $g['GPSLatitudeRef']);
        $lng = $this->gpsToDecimal($g['GPSLongitude'], $g['GPSLongitudeRef']);

        if ($lat === null || $lng === null) return null;

        return [
            'lat' => $this->formatDecimal($lat),
            'lng' => $this->formatDecimal($lng),
        ];
    }

    private function gpsToDecimal($coord, $ref): ?float
    {
        if (!is_array($coord) || count($coord) < 3) return null;
        if (!is_string($ref) || $ref === '') return null;

        $deg = $this->fractionToFloat($coord[0]);
        $min = $this->fractionToFloat($coord[1]);
        $sec = $this->fractionToFloat($coord[2]);

        if ($deg === null || $min === null || $sec === null) return null;

        $dec = $deg + ($min / 60.0) + ($sec / 3600.0);

        $r = strtoupper($ref);
        if ($r === 'S' || $r === 'W') $dec *= -1.0;

        return $dec;
    }

    private function fractionToFloat($v): ?float
    {
        if (is_numeric($v)) return (float)$v;

        if (!is_string($v) || $v === '') return null;

        if (strpos($v, '/') === false) {
            return is_numeric($v) ? (float)$v : null;
        }

        $parts = explode('/', $v, 2);
        if (count($parts) !== 2) return null;

        $n = $parts[0];
        $d = $parts[1];

        if (!is_numeric($n) || !is_numeric($d)) return null;

        $den = (float)$d;
        if ($den == 0.0) return null;

        return ((float)$n) / $den;
    }

    private function formatDecimal(float $value): string
    {
        // 小数点以下6桁程度（必要なら変更）
        $s = number_format($value, 6, '.', '');
        return rtrim(rtrim($s, '0'), '.');
    }
}
