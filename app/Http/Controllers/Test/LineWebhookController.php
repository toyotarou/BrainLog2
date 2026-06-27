<?php

namespace App\Http\Controllers\Test;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;

class LineWebhookController extends Controller
{
    public function webhook(Request $request)
    {
        $token  = config('services.line.channel_access_token');
        $secret = config('services.line.channel_secret');

        // -------------------------------------------------------
        // LINEクライアントの初期化
        // -------------------------------------------------------
        $httpClient = new CurlHTTPClient($token);
        $bot = new LINEBot($httpClient, ['channelSecret' => $secret]);

        // -------------------------------------------------------
        // 署名検証
        // LINEから来たリクエストが本物かどうかを確認する
        // 第三者からの不正なリクエストを弾くために必須
        // -------------------------------------------------------
        $signature = $request->header('X-Line-Signature');
        $body      = $request->getContent();

        if (!$bot->validateSignature($body, $signature)) {
            // 署名が一致しない場合は400を返して終了
            return response('Invalid signature', 400);
        }

        // -------------------------------------------------------
        // Webhookのイベントを解析
        // LINEから送られてくるJSONを解析してイベントの配列にする
        // -------------------------------------------------------
        $events = $bot->parseEventRequest($body, $signature);

        foreach ($events as $event) {

            // -------------------------------------------------------
            // イベントの種類を確認
            // follow   → 友だち追加 or ブロック解除
            // message  → メッセージを送ってきた
            // どちらの場合もUser IDが取得できる
            // -------------------------------------------------------
            $type   = $event->getType();
            $userId = $event->getUserId();

            if (in_array($type, ['follow', 'message'])) {

                // -------------------------------------------------------
                // Laravel 5.5対応：insertOrIgnoreの代わりに
                // 既存チェックしてからinsertする
                // -------------------------------------------------------
                $exists = DB::table('t_messaging_test_user')
                    ->where('user_id', $userId)
                    ->exists();

                if (!$exists) {
                    DB::table('t_messaging_test_user')->insert([
                        'user_id' => $userId,
                    ]);
                }
            }
        }

        // LINEサーバーには必ず200を返す（返さないとエラー扱いになる）
        return response('OK', 200);
    }
}
