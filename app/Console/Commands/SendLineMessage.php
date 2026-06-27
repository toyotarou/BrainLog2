<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use LINE\LINEBot;                                        // LINE Botのメインクライアント
use LINE\LINEBot\HTTPClient\CurlHTTPClient;             // HTTP通信の実装（v7はGuzzleではなくCurl）
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;     // テキストメッセージを作るクラス

class SendLineMessage extends Command
{
    /**
     * 使い方:
     *   php artisan line:send              → "テストメッセージです" が送られる
     *   php artisan line:send "おはよう"   → "おはよう" が送られる
     */
    protected $signature = 'line:send {message=テストメッセージです}';

    protected $description = 'LINEに送信する（複数人対応）';

    public function handle()
    {
        $token  = config('services.line.channel_access_token');
        $secret = config('services.line.channel_secret');

        if (empty($token) || empty($secret)) {
            $this->error('.envにLINE_BOT_CHANNEL_ACCESS_TOKENとLINE_BOT_CHANNEL_SECRETを設定してください');
            return 1;
        }

        // -------------------------------------------------------
        // 送信先のUser IDをここに追加していく
        // 友だち追加してもらったらここに追記するだけでOK
        // -------------------------------------------------------
        $userIds = array_filter([
            config('services.line.my_user_id'), // 自分
            // 'Uxxxxxxxxxx', // Aさん（増えたらここに追記）
            // 'Uxxxxxxxxxx', // Bさん
        ]);

        if (empty($userIds)) {
            $this->error('送信先のUser IDが設定されていません');
            return 1;
        }

        $message = $this->argument('message');
        $this->info("送信中: {$message}（送信先: " . count($userIds) . "人）");

        // -------------------------------------------------------
        // v7のクライアント初期化
        // CurlHTTPClient：HTTP通信の実装（v7はCurlを使う）
        // LINEBot：メインクライアント（secret と token を渡す）
        // -------------------------------------------------------
        $httpClient = new CurlHTTPClient($token);
        $bot = new LINEBot($httpClient, ['channelSecret' => $secret]);

        // テキストメッセージオブジェクトを作成
        $messageBuilder = new TextMessageBuilder($message);

        // -------------------------------------------------------
        // multicast：複数人に一度に送信するメソッド
        // 第1引数：User IDの配列
        // 第2引数：メッセージオブジェクト
        // -------------------------------------------------------
        $response = $bot->multicast(array_values($userIds), $messageBuilder);

        if ($response->isSucceeded()) {
            $this->info('✅ 送信成功！LINEを確認してください。');
            return 0;
        }

        $this->error('❌ 送信失敗: ' . $response->getRawBody());
        return 1;
    }
}
