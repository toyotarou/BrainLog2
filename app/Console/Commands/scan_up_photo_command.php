<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use SplFileInfo;

class ScanUpPhotoCommand extends Command
{
    /**
     * コマンド名
     */
    protected $signature = 'ScanUpPhotoCommand';

    /**
     * 説明
     */
    protected $description = 'Scan public/UPPHOTO and preview photo taken datetime + GPS (EXIF if available)';

    /**
     * 作業中のプレビュー表示枚数
     * 0 にすると無制限（大量になるので注意）
     */
    private const PREVIEW_LIMIT = 30;

    /**
     * 対象拡張子
     */
    private const ALLOWED_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * 実行処理
     */
    public function handle()
    {
        $baseDir = public_path('UPPHOTO');

        if (!is_dir($baseDir)) {
            $this->error("Directory not found: {$baseDir}");
            return 1;
        }

        $this->info('Scanning: ' . $baseDir);
        $this->info('Preview limit: ' . self::PREVIEW_LIMIT);

        if (!function_exists('exif_read_data')) {
            $this->warn('exif_read_data() is not available. EXIF taken datetime / GPS will be skipped.');
            $this->warn('If you want EXIF, enable/install php-exif.');
        }

        $this->scanAndPreview($baseDir);

        $this->info('Done.');
        return 0;
    }

    /**
     * UPPHOTO を再帰走査して、プレビュー表示する
     */
    private function scanAndPreview($baseDir)
    {
        $baseDir = rtrim($baseDir, '/');

        $dirIt = new RecursiveDirectoryIterator(
            $baseDir,
            FilesystemIterator::SKIP_DOTS
        );

        $it = new RecursiveIteratorIterator(
            $dirIt,
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        $previewCount = 0;

        foreach ($it as $fileInfo) {
            /** @var SplFileInfo $fileInfo */
            if (!$fileInfo->isFile()) {
                continue;
            }

            $ext = strtolower($fileInfo->getExtension());
            if (!in_array($ext, self::ALLOWED_EXT, true)) {
                continue;
            }

            // baseDir からの相対パス（例: 2015/2015-05-30/2015-05-30_005_l.jpg）
            $fullPath = $fileInfo->getPathname();
            $relative = substr($fullPath, strlen($baseDir) + 1);
            $relative = str_replace('\\', '/', $relative);

            $parts = explode('/', $relative);
            if (count($parts) < 3) {
                continue;
            }

            $year = $parts[0];
            $dateDir = $parts[1];
            $filename = $fileInfo->getFilename();

            if (!preg_match('/^\d{4}$/', $year)) {
                continue;
            }
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDir)) {
                continue;
            }

            // できるだけ頑張って撮影日時を推定
            $taken = $this->getBestTakenAt($fullPath, $dateDir, $filename);

            // GPS（取れれば10進数で返す）
            $gps = $this->getGpsFromExif($fullPath);
            $gpsText = $gps !== null ? ($gps['lat'] . ',' . $gps['lng']) : 'null';

            $this->line(sprintf(
                'taken=%s | gps=%s | path=%s',
                $taken !== null ? $taken : 'null',
                $gpsText,
                $relative
            ));

            // プレビュー制限
            if (self::PREVIEW_LIMIT > 0) {
                $previewCount++;
                if ($previewCount >= self::PREVIEW_LIMIT) {
                    $this->info('Preview limit reached. Stop scanning.');
                    break;
                }
            }
        }
    }

    /**
     * 撮影日時を「できるだけ」推定する
     */
    private function getBestTakenAt($fullPath, $dateDir, $filename)
    {
        $exifTaken = $this->getTakenAtFromExif($fullPath);
        if ($exifTaken !== null) {
            return $exifTaken;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateDir)) {
            return $dateDir . ' 00:00:00';
        }

        $nameDate = $this->getDateFromFilename($filename);
        if ($nameDate !== null) {
            return $nameDate . ' 00:00:00';
        }

        return null;
    }

    /**
     * EXIF から撮影日時を取得
     */
    private function getTakenAtFromExif($fullPath)
    {
        if (!function_exists('exif_read_data')) {
            return null;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'], true)) {
            return null;
        }

        $exif = @exif_read_data($fullPath, 'EXIF', true);
        if (!is_array($exif)) {
            return null;
        }

        $candidates = [
            $exif['EXIF']['DateTimeOriginal'] ?? null,
            $exif['EXIF']['CreateDate'] ?? null,
            $exif['IFD0']['DateTime'] ?? null,
        ];

        foreach ($candidates as $value) {
            $normalized = $this->normalizeExifDateTime($value);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    /**
     * EXIF由来の日時文字列を 'YYYY-MM-DD HH:MM:SS' に正規化
     */
    private function normalizeExifDateTime($value)
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        if (preg_match('/^\d{4}:\d{2}:\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            $datePart = str_replace(':', '-', substr($value, 0, 10));
            $timePart = substr($value, 10);
            return $datePart . $timePart;
        }

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
            return $value;
        }

        return null;
    }

    /**
     * ファイル名から日付を抽出（例: 2015-05-30_005_l.jpg -> 2015-05-30）
     */
    private function getDateFromFilename($filename)
    {
        if (!is_string($filename) || $filename === '') {
            return null;
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2})_\d+/i', $filename, $m)) {
            return $m[1];
        }

        return null;
    }

    /**
     * EXIF GPS から緯度経度を取得（取れなければ null）
     *
     * 返り値:
     *  ['lat' => '35.718507', 'lng' => '139.586953']
     */
    private function getGpsFromExif($fullPath)
    {
        if (!function_exists('exif_read_data')) {
            return null;
        }

        $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg'], true)) {
            return null;
        }

        // GPS セクションを読む（壊れEXIF警告抑止）
        $exif = @exif_read_data($fullPath, 'GPS', true);
        if (!is_array($exif)) {
            return null;
        }

        if (!isset($exif['GPS'])) {
            return null;
        }

        $gps = $exif['GPS'];

        $lat = $gps['GPSLatitude'] ?? null;
        $latRef = $gps['GPSLatitudeRef'] ?? null;
        $lng = $gps['GPSLongitude'] ?? null;
        $lngRef = $gps['GPSLongitudeRef'] ?? null;

        if ($lat === null || $latRef === null || $lng === null || $lngRef === null) {
            return null;
        }

        $latDecimal = $this->gpsToDecimal($lat, $latRef);
        $lngDecimal = $this->gpsToDecimal($lng, $lngRef);

        if ($latDecimal === null || $lngDecimal === null) {
            return null;
        }

        // 表示用に小数点以下をある程度揃える（必要なら変更）
        return [
            'lat' => $this->formatDecimal($latDecimal),
            'lng' => $this->formatDecimal($lngDecimal),
        ];
    }

    /**
     * GPS 度分秒を 10進数に変換
     *
     * $coord は配列で渡ることが多い:
     * 例: ['35/1', '43/1', '1234/100']
     */
    private function gpsToDecimal($coord, $ref)
    {
        if (!is_array($coord) || count($coord) < 3) {
            return null;
        }
        if (!is_string($ref) || $ref === '') {
            return null;
        }

        $deg = $this->fractionToFloat($coord[0]);
        $min = $this->fractionToFloat($coord[1]);
        $sec = $this->fractionToFloat($coord[2]);

        if ($deg === null || $min === null || $sec === null) {
            return null;
        }

        $decimal = $deg + ($min / 60.0) + ($sec / 3600.0);

        // 南半球/西経はマイナス
        $refUpper = strtoupper($ref);
        if ($refUpper === 'S' || $refUpper === 'W') {
            $decimal *= -1.0;
        }

        return $decimal;
    }

    /**
     * "1234/100" のような分数文字列を float にする
     * "35/1" -> 35.0
     * "12"   -> 12.0
     */
    private function fractionToFloat($value)
    {
        if (is_numeric($value)) {
            return (float)$value;
        }

        if (!is_string($value) || $value === '') {
            return null;
        }

        if (strpos($value, '/') === false) {
            return is_numeric($value) ? (float)$value : null;
        }

        $parts = explode('/', $value, 2);
        if (count($parts) !== 2) {
            return null;
        }

        $num = $parts[0];
        $den = $parts[1];

        if (!is_numeric($num) || !is_numeric($den)) {
            return null;
        }

        $denF = (float)$den;
        if ($denF == 0.0) {
            return null;
        }

        return ((float)$num) / $denF;
    }

    /**
     * 表示向けの丸め（誤差を見やすく）
     */
    private function formatDecimal($value)
    {
        // 小数点以下 6 桁程度で十分なことが多い（約0.11m〜0.2m級のオーダー）
        return rtrim(rtrim(number_format($value, 6, '.', ''), '0'), '.');
    }
}
