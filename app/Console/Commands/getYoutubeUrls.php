<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class getYoutubeUrls extends Command
{
    protected $signature = 'getYoutubeUrls';
    protected $description = 'Search YouTube videos and keep only 2+ hour videos';

    public function handle()
    {
        $apiKey = 'AIzaSyD9PkTM1Pur3YzmO-v4VzS0r8ZZ0jRJTIU';
        $query = 'プレボクシング';

        $pageToken = null;
        $searchPageCount = 0;
        $maxSearchPages = 3;

        $videoMap = []; // videoId 重複排除用

        do {
            $params = [
                'part' => 'snippet',
                'q' => $query,
                'type' => 'video',
                'maxResults' => 50,
                'videoDuration' => 'long', // 20分超で一次絞り
                'key' => $apiKey,
            ];

            if ($pageToken) {
                $params['pageToken'] = $pageToken;
            }

            $url = 'https://www.googleapis.com/youtube/v3/search?' . http_build_query($params);

            $response = file_get_contents($url);
            if ($response === false) {
                $this->error('search.list の取得に失敗しました');
                return Command::FAILURE;
            }

            $data = json_decode($response, true);

            if (!empty($data['error'])) {
                $this->error('search.list error: ' . json_encode($data['error'], JSON_UNESCAPED_UNICODE));
                return Command::FAILURE;
            }

            foreach ($data['items'] ?? [] as $item) {
                $videoId = $item['id']['videoId'] ?? null;
                if (!$videoId) {
                    continue;
                }

                $videoMap[$videoId] = [
                    'title' => $item['snippet']['title'] ?? '',
                    'channelTitle' => $item['snippet']['channelTitle'] ?? '',
                    'videoId' => $videoId,
                    'url' => 'https://www.youtube.com/watch?v=' . $videoId,
                    'publishedAt' => $item['snippet']['publishedAt'] ?? '',
                ];
            }

            $pageToken = $data['nextPageToken'] ?? null;
            $searchPageCount++;

        } while ($pageToken && $searchPageCount < $maxSearchPages);

        $videoIds = array_keys($videoMap);

        if (empty($videoIds)) {
            $this->info('動画が見つかりませんでした');
            return Command::SUCCESS;
        }

        $results = [];

        // videos.list は id をカンマ区切りでまとめて投げられる
        foreach (array_chunk($videoIds, 50) as $chunk) {
            $videoUrl = 'https://www.googleapis.com/youtube/v3/videos?' . http_build_query([
                'part' => 'contentDetails,snippet',
                'id' => implode(',', $chunk),
                'key' => $apiKey,
            ]);

            $videoResponse = file_get_contents($videoUrl);
            if ($videoResponse === false) {
                $this->error('videos.list の取得に失敗しました');
                return Command::FAILURE;
            }

            $videoData = json_decode($videoResponse, true);

            if (!empty($videoData['error'])) {
                $this->error('videos.list error: ' . json_encode($videoData['error'], JSON_UNESCAPED_UNICODE));
                return Command::FAILURE;
            }

            foreach ($videoData['items'] ?? [] as $videoItem) {
                $videoId = $videoItem['id'] ?? null;
                $durationIso = $videoItem['contentDetails']['duration'] ?? null;

                if (!$videoId || !$durationIso) {
                    continue;
                }

                $seconds = $this->iso8601DurationToSeconds($durationIso);




if($videoItem['snippet']['channelTitle']!=null){
if($videoItem['snippet']['channelTitle'] == "ASBのあそび場"){
                if ($seconds >= 7200) { // 2時間以上
                    $base = $videoMap[$videoId] ?? [
                        'title' => $videoItem['snippet']['title'] ?? '',
                        'channelTitle' => $videoItem['snippet']['channelTitle'] ?? '',
                        'videoId' => $videoId,
                        'url' => 'https://www.youtube.com/watch?v=' . $videoId,
                        'publishedAt' => $videoItem['snippet']['publishedAt'] ?? '',

                         'description' => $videoItem['snippet']['description'] ?? '',

                    ];

                    $base['duration'] = $durationIso;
                    $base['durationSeconds'] = $seconds;

                    $results[] = $base;
                }
}
}











            }
        }

        usort($results, function ($a, $b) {
            return $b['durationSeconds'] <=> $a['durationSeconds'];
        });

        print_r($results);

//        return Command::SUCCESS;
    }

    private function iso8601DurationToSeconds(string $duration): int
    {
        // 例: PT2H13M45S
        $pattern = '/^P(?:(\d+)D)?T?(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/';

        if (!preg_match($pattern, $duration, $matches)) {
            return 0;
        }

        $days = isset($matches[1]) && $matches[1] !== '' ? (int)$matches[1] : 0;
        $hours = isset($matches[2]) && $matches[2] !== '' ? (int)$matches[2] : 0;
        $minutes = isset($matches[3]) && $matches[3] !== '' ? (int)$matches[3] : 0;
        $seconds = isset($matches[4]) && $matches[4] !== '' ? (int)$matches[4] : 0;

        return $days * 86400 + $hours * 3600 + $minutes * 60 + $seconds;
    }
}
