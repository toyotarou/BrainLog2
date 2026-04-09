# BrainLog2

個人の総合ライフログシステムのバックエンド API サーバーです（**Laravel 5.5**）。家計・資産・投資・労働・健康・占い・お寺・YouTube・位置情報など、30 カテゴリ以上のデータを REST API として提供します。Flutter アプリ（`flutter_lifetime_log2` 等）のバックエンドとして機能します。

---

## 技術スタック

| カテゴリ | ライブラリ / ツール |
|---|---|
| フレームワーク | Laravel 5.5.* (PHP ^7.2.5\|^8.0) |
| CORS | barryvdh/laravel-cors |
| API ドキュメント | darkaonline/l5-swagger + zircote/swagger-php |
| 管理画面 | encore/laravel-admin |
| Google API 連携 | google/apiclient |
| 決済 | stripe/stripe-php |
| スクレイピング | weidner/goutte |
| テスト | phpunit/phpunit ~6.0 |
| フロントエンド | webpack.mix.js (Laravel Mix) |

---

## API エンドポイント一覧

すべてのエンドポイントは `POST /api/{endpoint}` 形式です（一部 GET あり）。コントローラーは `ApiController` / `ApiControllerSecond` / `ApiControllerThird` / `ApiControllerBrainLog` / `ApiControllerReflection` の 5 クラスに分割されています。

### 💰 家計・金融 (money)

| エンドポイント | 説明 |
|---|---|
| getmonthstartmoney | 月初残高取得 |
| getsalary | 給与データ取得 |
| spenditem / monthlyspenditem | 支出項目取得 |
| moneyinsert / moneydownload | 現金データ登録・ダウンロード |
| monthsummary / yearsummary | 月次・年次集計 |
| uccardspend / allcardspend | クレジットカード支出 |
| creditDetail / getCreditDateData | クレジット明細 |
| getYearCreditSummay | 年間クレジット集計 |
| getMonthlyBankRecord / updateBankMoney | 銀行記録 |
| getAllMoney / getAllMoneySum | 全資産取得 |
| setBankMove / getBankMove | 銀行間移動 |
| setKeihiData / updateKeihiCategory | 経費データ |
| mercaridata | メルカリデータ |
| amazonPurchaseList / getAmazonData | Amazon 購入履歴 |
| seiyuuPurchaseList / seiyuuPurchaseItemList | 西友購入履歴 |
| dutyData / getDutyData | 公共料金データ |
| yachinData | 家賃データ |
| homeFixData / homeFix | 住宅修繕データ |
| getgolddata | 金価格データ |
| getFundRecord / getFund | 投資信託記録 |
| getWellsRecord / getWells | ウェルス記録 |
| getBalanceSheetRecord / balanceSheetRecord | バランスシート |
| getITFRecord / getITFPrice | ITF 記録・価格 |
| getDataStock / getStockPrice / getStockDetail | 株データ取得 |
| getDataShintaku / getShintakuDetail | 投資信託詳細 |
| getAllBenefit / benefit | 損益データ |
| getYearSpend / getYearSpendSummay | 年間支出 |
| getSamedaySpend / getSameYearMonthDay | 同日支出比較 |
| everydaySpendSearch | 毎日支出検索 |
| getSpendCheckItem / inputSpendCheckItem | 支出チェック |
| getTaxPaymentItem | 税金支払い |
| getcompanycredit | 会社クレジット |
| insertSpend / spendItemInsert | 支出登録 |
| creditCompanySearch / bankSearch | クレジット・銀行検索 |

### 📈 株式 (stock)

| エンドポイント | 説明 |
|---|---|
| stockdataexists | 株データ存在確認 |
| stockdatedata | 日付別株データ |
| stockgradedata / stockcodedata | グレード・コード別 |
| stockindustrylistdata / stockindustrydata | 業種別 |
| stockpricedata / stockalldata | 株価・全データ |
| insertDailyStockData / getStockName / getAllStockData | 株データ登録・取得 |
| getAllToushiShintakuData / updateToushiShintakuRelationalId | 投資信託 |

### 💼 労働・勤怠 (worktime / work)

| エンドポイント | 説明 |
|---|---|
| worktimemonthdata / worktimeinsert | 労働時間月次・登録 |
| workinggenbaname / worktimesummary | 現場名・集計 |
| getGenbaWorkTime | 現場別労働時間 |
| getWorkContract / getWorkTruth / getWorkAnken | 職務経歴データ |

### 🔮 占い (uranai)

| エンドポイント | 説明 |
|---|---|
| dailyuranai / monthlyuranai / yearlyuranai | 日次・月次・年次占い |
| monthlyuranaidetail / getMonthlyUranaiData | 月次占い詳細 |
| leofortune | レオの運勢 |
| getGooUranai | goo 占い |

### 🃏 タロット (tarot)

| エンドポイント | 説明 |
|---|---|
| tarotcard / tarotcategory | カード・カテゴリ |
| tarotselect / tarothistory / tarotthree | 選択・履歴・スリーカード |
| getAllTarot / getCatTarot | 全カード・カテゴリ別 |
| getCategoryRate / updateTarotFeeling | レート・感触更新 |

### ⛩️ お寺 (temple)

| エンドポイント | 説明 |
|---|---|
| getAllTemple / getDateTemple | 全寺・日付別 |
| getTempleLatLng / getTempleListTemple | 位置情報 |
| insertTempleRoute / insertTempleRank | ルート・ランク登録 |
| templeNotReached / getTempleNotReachTrain | 未訪問寺院 |
| nearStation / notReachedTempleStation | 最寄り駅 |
| getTempleDatePhoto | 寺院写真 |
| tokyoJinjachouTempleList | 東京神社庁一覧 |
| getComplementTempleVisitedDate | 訪問日補完 |
| getTempleListNavitimeTemple | Navitime 連携 |

### 🚃 電車・駅 (train)

| エンドポイント | 説明 |
|---|---|
| getTrain / getTrainStation / getTrainCompany | 路線・駅・会社 |
| updateTrainFlag | 乗車フラグ更新 |
| getStationStamp / getStationStampNotGet | 駅スタンプ |
| getTokyoTrainStation / getAllStation | 東京駅・全駅 |
| getPrefecture / getPrefectureTrainCompany | 都道府県別 |
| getPrefTrainStation | 都道府県別駅 |
| getTokyoBorderGeoloc | 都境位置情報 |
| getMetroStampPokePoke | 地下鉄スタンプ |

### 🎥 YouTube (youtube)

| エンドポイント | 説明 |
|---|---|
| getYoutubeList / bunruiYoutubeData | 動画一覧・分類別 |
| getBunruiName / getSpecialVideo | 分類名・特別動画 |
| getOrderedVideo / getDeletedVideo | 並び順・削除動画 |
| updateVideoPlayedAt | 再生日時更新 |
| getYoutubeCategoryTree / updateYoutubeCategoryTree | カテゴリツリー |
| getBlankBunruiVideo / oneBunruiInput | 未分類動画 |
| searchYoutubeId | YouTube ID 検索 |

### 📍 位置情報 (geoloc)

| エンドポイント | 説明 |
|---|---|
| insertGeoloc / getGeoloc / getAllGeoloc | 位置情報登録・取得 |
| getGeolocReflection | リフレクション用位置情報 |

### 🚶 ウォーキング (walk)

| エンドポイント | 説明 |
|---|---|
| getWalkRecord / getWalkRecord2 / getWalkRecord3 | 歩行記録取得 |
| insertWalkRecord | 歩行記録登録 |

### その他のカテゴリ

| カテゴリ | エンドポイント例 |
|---|---|
| ことわざ | getkotowazacount / getkotowaza / changekotowazaflag |
| 季語 | getKigoSeasonList / getKigoSeasonRandomList / getKigoSearchedList |
| アート施設 | getNearArtFacilities / getArtCity / getArtGenre |
| 公園 | getMetropolitanPark |
| 御朱印 | goshuin |
| バス | getBusInfo / getBusTotalInfo / getBusStopAddress |
| 天気 | getAllWeather |
| 投資 | getInvestLastRecord / getAllInvestNames / getAllInvestRecords |
| ライフログ | getLifetimeRecordItem / insertLifetime / getAllLifetimeRecord |
| 日記 (BrainLog) | getOnedayArticle |
| エージェント | getAgentName / getAgentDocument |
| ダイス | dice |
| 祝日 | getholiday |

---

## Eloquent モデル

| モデル | テーブル | 説明 |
|---|---|---|
| Money | money | 現金・手持ち資産 |
| Spend | spend | 支出記録 |
| Credit | credit | クレジットカード明細 |
| Salary | salary | 給与データ |
| Timeplace | timeplace | 時刻・場所記録 |
| User | users | ユーザー管理 |

---

## ディレクトリ構成

```
app/
├── Console/            Artisan コマンド
├── Http/
│   ├── Controllers/
│   │   └── Api/        ApiController / ApiControllerSecond / ApiControllerThird
│   │                   ApiControllerBrainLog / ApiControllerReflection
│   └── Middleware/     認証・CORS ミドルウェア
├── Models/             Eloquent モデル
├── MyClass/            独自ユーティリティクラス
├── Providers/          サービスプロバイダ
└── Swagger/            Swagger アノテーション定義

routes/
├── api.php             REST API ルート定義（全エンドポイント）
└── web.php             Web ルート

database/
├── migrations/         マイグレーション
└── seeds/              シーダー
```

---

## セットアップ

```bash
# 依存パッケージのインストール
composer install

# 環境変数の設定
cp .env.example .env
php artisan key:generate

# .env を編集して DB 接続情報・外部 API キーを設定

# マイグレーション実行
php artisan migrate

# 開発サーバー起動
php artisan serve
```

### .env の主な設定項目

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=brainlog2
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_API_KEY=
STRIPE_KEY=
STRIPE_SECRET=
```

---

## API ドキュメント

L5-Swagger による API ドキュメントが自動生成されます。

```bash
php artisan l5-swagger:generate
```

生成後は `/api/documentation` でアクセスできます。

---

## 動作環境

- PHP: ^7.2.5 | ^8.0
- Laravel: 5.5.*
- MySQL（推奨）
