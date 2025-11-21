<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use DB;
use Exception;
use Illuminate\Http\Request;

class ApiControllerThird extends Controller
{

    /**
     *
     */
    public function getPrefecture()
    {
        $response = [];

        $area = [
            "北海道・東北" => "北海道|青森県|秋田県|岩手県|山形県|宮城県|福島県",
            "関東" => "群馬県|栃木県|茨城県|埼玉県|千葉県|東京都|神奈川県",
            "中部" => "新潟県|富山県|石川県|福井県|長野県|岐阜県|山梨県|愛知県|静岡県",
            "近畿" => "京都府|滋賀県|大阪府|奈良県|三重県|和歌山県|兵庫県",
            "中国" => "鳥取県|岡山県|島根県|広島県|山口県",
            "四国" => "香川県|愛媛県|徳島県|高知県",
            "九州・沖縄" => "福岡県|佐賀県|長崎県|大分県|熊本県|宮崎県|鹿児島県|沖縄県"
        ];

        $ary = [];

        $i = 0;
        foreach ($area as $ar => $v) {
            $ex_v = explode("|", $v);

            $j = 0;
            foreach ($ex_v as $v2) {
                $ary[] = ['areaNo' => $i, 'area' => $ar, 'prefecture' => trim($v2), 'prefNo' => $j];

                $j++;
            }

            $i++;
        }

        $response = $ary;

        return response()->json(['data' => $response]);
    }

    /**
     *
     */
    public function getPrefectureTrainCompany(Request $request)
    {
        $response = [];

        $result = DB::table('t_station')
            ->where('prefecture', '=', $request->prefecture)
            ->get();

        $station_trainNumber = [];
        foreach ($result as $k => $v) {
            $station_trainNumber[$v->train_number] = "";
        }
        $s_trainNumber = array_keys($station_trainNumber);

        sort($s_trainNumber);

        $ary = [];

        $result2 = DB::table('t_train')
            ->whereIn('train_number', $s_trainNumber)
            ->get();

        $trainCompanyId = [];
        foreach ($result2 as $k2 => $v2) {
            $trainCompanyId[$v2->company_id] = "";
        }
        $tcId = array_keys($trainCompanyId);

        $result3 = DB::table('t_train_company')
            ->whereIn('company_id', $tcId)
            ->get();

        foreach ($result3 as $v3) {
            $ary[] = [
                'company_id' => $v3->company_id,
                'company_name' => $v3->company_name,
                'flag' => $v3->flag,
                'train' => [],
            ];
        }

        $response = $ary;

        return response()->json(['data' => $response]);
    }


    /**
     * @param Request $request
     * @return void
     */
    public function getLifetimeRecordItem(Request $request)
    {
        $response = [];

        $result = DB::table('t_lifetime_item')->get();

        $ary = [];
        foreach ($result as $v) {
            $ary[] = ['item' => $v->item];
        }

        $response = $ary;

        return response()->json(['data' => $response]);
    }


    /**
     * @param Request $request
     * @return void
     */
    public function insertLifetime(Request $request)
    {
        try {
            DB::beginTransaction();

            list($year, $month, $day) = explode("-", $request->date);

            DB::table('t_lifetime')
                ->where('year', '=', $year)
                ->where('month', '=', $month)
                ->where('day', '=', $day)
                ->delete();

            $exLifetime = explode("|", $request->lifetime);

            $insert = [];
            $insert['year'] = $year;
            $insert['month'] = $month;
            $insert['day'] = $day;

            foreach ($exLifetime as $k => $v) {
                $hour = sprintf("%02d", $k);
                $insert["hour{$hour}"] = $v;
            }

            DB::table('t_lifetime')->insert($insert);

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }


    /**
     * @param Request $request
     * @return void
     */
    public function getLifetimeDateRecord(Request $request)
    {

        $response = [];

        list($year, $month, $day) = explode("-", $request->date);

        $result = DB::table('t_lifetime')
            ->where('year', '=', $year)
            ->where('month', '=', $month)
            ->where('day', '=', $day)
            ->first();

        $ary = $result;

        $response = $ary;

        return response()->json(['data' => $response]);
    }


    /**
     * @param Request $request
     * @return mixed
     */
    public function getLifetimeYearlyRecord(Request $request)
    {

        $response = [];

        list($year, $month, $day) = explode("-", $request->date);

        $result = DB::table('t_lifetime')
            ->where('year', '=', $year)
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $ary = [];
        foreach ($result as $v) {
            $ary[] = $v;
        }

        $response = $ary;

        return response()->json(['data' => $response]);
    }

    /**
     *
     */
    public function getAllLifetimeRecord()
    {

        $response = [];

        $result = DB::table('t_lifetime')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        $ary = [];
        foreach ($result as $v) {
            $ary[] = $v;
        }

        $response = $ary;

        return response()->json(['data' => $response]);
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getWalkRecord3(Request $request)
    {
        $response = [];

        ////////////////////////////////////////////////
        $mercari = [];

        $result2 = DB::table('t_mercari')
            ->where('buy_sell', '=', 'sell')
            ->orderBy('departured_at')
            ->get();

        foreach ($result2 as $v2) {
            $ex_dep = explode(" ", $v2->departured_at);
            $mercari[trim($ex_dep[0])] = "";
        }
        ////////////////////////////////////////////////

        ////////////////////////////////////////////////
        $temple = [];

        $result3 = DB::table('t_temple')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();

        foreach ($result3 as $v3) {
            $date = "{$v3->year}-{$v3->month}-{$v3->day}";
            $tpl = (trim($v3->memo) != "") ? "{$v3->temple}、{$v3->memo}" : $v3->temple;
            $temple[$date] = $tpl;
        }
        ////////////////////////////////////////////////

        ////////////////////////////////////////////////
        $timeplace = [];

        $result4 = DB::table('t_timeplace')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->orderBy('time')
            ->get();

        $ary4 = [];
        $before = "";
        $thisDate = "";
        foreach ($result4 as $v4) {
            $date = "{$v4->year}-{$v4->month}-{$v4->day}";

            if ($thisDate == $date) {
                if ($before == $v4->place) {
                    continue;
                }
            }

            $ary4[$date][] = $v4->place;

            $thisDate = $date;
            $before = $v4->place;
        }

        foreach ($ary4 as $date => $v4) {
            $timeplace[$date] = implode(" - ", $v4);
        }
        ////////////////////////////////////////////////

        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>
        $result6 = DB::table('t_walk_record')
            ->orderBy('id', 'desc')
            ->first();
        $maxId = $result6->id;
        //>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>

        //****************************************************//
        $snd = [];

        $salary = [];
        $result7 = DB::table('t_salary')
            ->orderBy('year')
            ->orderBy('month')
            ->orderBy('day')
            ->get();
        foreach ($result7 as $v7) {
            $date = "{$v7->year}-{$v7->month}-{$v7->day}";
            $salary[$date] = $v7->salary;
        }

        //------------

        $file = public_path() . "/mySetting/MoneyTotal.data";
        $content = file_get_contents($file);
        $ex_content = explode("\n", mb_convert_encoding($content, "utf8", "sjis-win"));
        foreach ($ex_content as $v) {
            if (trim($v) == "") {
                continue;
            }

            list($date, $x, $total, $spend) = explode("|", trim($v));
            $spd = (isset($salary[$date])) ? ($salary[$date] + $spend) : $spend;
            $snd[$date] = "{$spd} 円";
        }
        //****************************************************//

        $result = DB::table('t_walk_record')
            ->orderBy('id')
            ->get();

        $a_year = [];

        $hasNext = true;

        $ary = [];
        foreach ($result as $v) {
            $month = sprintf("%02d", $v->month);
            $day = sprintf("%02d", $v->day);
            $date = "{$v->year}-{$month}-{$day}";

            $ary[$date] = [
                'step' => $v->step,
                'distance' => $v->distance
            ];

            $a_year[$v->year][] = "";

            if ($v->id == $maxId) {
                $hasNext = false;
            }
        }

        //-------------------------------//
        $train = [];

        $article_year = array_keys($a_year);
        foreach ($article_year as $year) {
            $table = "t_article{$year}";
            $result5 = DB::table($table)
                ->where('tag', '=', '電車乗車')
                ->orderBy('year')
                ->orderBy('month')
                ->orderBy('day')
                ->get();

            foreach ($result5 as $v5) {
                $date = "{$v5->year}-{$v5->month}-{$v5->day}";
                $ex_article = explode("\n", trim($v5->article));
                $acl = [];
                foreach ($ex_article as $ac) {
                    $acl[] = trim($ac);
                }
                $train[$date] = implode("、", $acl);
            }
        }
        //-------------------------------//

        $ary2 = [];
        foreach ($ary as $date => $v) {
            $ary2[] = [
                'date' => $date,
                'step' => $v['step'],
                'distance' => $v['distance'],
                'timeplace' => (isset($timeplace[$date])) ? $timeplace[$date] : "",
                'temple' => (isset($temple[$date])) ? $temple[$date] : "",
                'mercari' => (isset($mercari[$date])) ? "メルカリ販売" : "",
                'train' => (isset($train[$date])) ? $train[$date] : "",
                'spend' => (isset($snd[$date])) ? $snd[$date] : 0
            ];
        }


        list($pYear, $pMonth, $pDay) = explode("-", $request->date);


        $dt = new Carbon($request->date);
        $nextMonth = $dt->addMonth();
        $nm = explode(" ", $nextMonth);


        $ary3 = [];
        foreach ($ary2 as $v) {
            list($lYear, $lMonth, $lDay) = explode("-", $v['date']);

            if ($lYear == $pYear && $lMonth == $pMonth) {
                $ary3[] = $v;
            }
        }


        $response = [
            'next' => $nm[0],
            'record' => $ary3,
        ];

        return response()->json(['data' => $response]);
    }

    /**
     *
     */
    public function insertWalkRecord(Request $request)
    {

        try {
            DB::beginTransaction();

            list($year, $month, $day) = explode("-", $request->date);

            DB::table('t_walk_record')
                ->where('year', '=', $year)
                ->where('month', '=', $month*1)
                ->where('day', '=', $day*1)
                ->delete();

            $insert = [
                "year" => $year,
                "month" => $month*1,
                "day" => $day*1,
                "step"=>$request->step,
                "distance"=>$request->distance,
            ];

            DB::table("t_walk_record")->insert($insert);

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    /**
     *
     */
    public function getTokyoBorderGeoloc()
    {
        $response = [];

        $sql = "select latitude,longitude from t_tokyo_border_geoloc group by latitude,longitude order by latitude,longitude;";

        $result = DB::select($sql);

        foreach ($result as $k => $v) {
            if($v->latitude != null && $v->longitude !=null){
                $response[] = [
                    'latitude' => $v->latitude,
                    'longitude' => $v->longitude,
                ];
            }
        }

        return response()->json(['data' => $response]);
    }

    ///
    public function getInvestLastRecord()
    {
        $result = DB::table('t_toushi_shintaku_datas')->orderBy('year', 'desc')->orderBy('month', 'desc')->orderBy('day', 'desc')->first();

        $result2 = DB::table('t_toushi_shintaku_datas')->where('year', '=', $result->year)->where('month', '=', $result->month)->where('day', '=', $result->day)->get();

        $ary = [];

        foreach($result2 as $v){

            $date = "{$v->year}-{$v->month}-{$v->day}";
            $num = strtr($v->hoyuu_suuryou, [',' => '', '口' => '']);
            $shutoku = strtr($v->heikin_shutoku_kagaku, [',' => '', '円' => '']);
            $cost = strtr($v->shutoku_sougaku, [',' => '', '円' => '']);
            $price = strtr($v->jika_hyoukagaku, [',' => '', '円' => '']);
            $kijunKagaku = strtr($v->kijun_kagaku, [',' => '', '円' => '']);

            $ary[] = [
                'name' => trim($v->name),
                'date' => $date,
                'num' => trim($num),
                'shutoku' => trim($shutoku),
                'cost' => trim($cost),
                'price' => trim($price),
                'diff' => (trim($price) - trim($cost)),
                'kijun' => trim($kijunKagaku),
            ];
        }

        $response = $ary;

        return response()->json(['data' => $response]);
    }

    ///
    public function getBusStopAddress()
    {
        $result = DB::table('t_bus_stop_address')->get();

        return response()->json(['data' => $result]);
    }

    ///
    public function getDupSpot()
    {
        $result = DB::table('t_dup_spot_name_limit')->get();

        return response()->json(['data' => $result]);
    }

    ///
    public function getBusInfo()
    {
        $result = DB::table('t_bus_info2')->get();

        return response()->json(['data' => $result]);
    }

    ///
    public function getAllDailySpend()
    {
        $result = DB::table('t_dailyspend')->where('year', '>=', '2023')->get();

        return response()->json(['data' => $result]);
    }

    ///
    public function getAllCredit()
    {
        $result = DB::table('t_credit')->where('year', '>=', '2023')->get();

        return response()->json(['data' => $result]);
    }

/*
    ///
    public function insertDailySpend(Request $request)
    {

        try {
            DB::beginTransaction();

            list($date, $koumoku, $price) = explode("|", trim($request->record));
            list($year, $month, $day) = explode("-", trim($date));
            $ymd = "{$year}{$month}{$day}";

DB::table('t_dailyspend')
->where('year', '=', $year)
->where('month', '=', $month)
->where('day', '=', $day)
->delete();

            $insert = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'koumoku' => $koumoku,
                'price' => $price,
                'ymd' => $ymd,
                'created_at' => $date,
                'updated_at' => $date,
                'flag' => ''
            ];

            DB::table('t_dailyspend')->insert($insert);

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }

    }

    ///
    public function insertCredit(Request $request)
    {

        try {
            DB::beginTransaction();

            list($date, $item, $price) = explode("|", trim($request->record));
            list($year, $month, $day) = explode("-", trim($date));
            $ymd = "{$year}{$month}{$day}";

DB::table('t_credit')
->where('year', '=', $year)
->where('month', '=', $month)
->where('day', '=', $day)
->delete();

            $insert = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'item' => $item,
                'price' => $price,
                'ymd' => $ymd,
                'created_at' => $date,
                'updated_at' => $date
            ];

            DB::table('t_credit')->insert($insert);

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }

    }
*/

    ///
    public function getAllWeather()
    {

        $weatherList = [];

        /////////////////////////////////
        $file = public_path() . "/mySetting/weather.data_bk";

        $content = file_get_contents($file);
        $ex_content = explode("\n", $content);

        foreach($ex_content as $v){
            list($date, $weather) = explode("|", trim($v));

            $weatherList[] = ["date" => $date, "weather" => $weather];
        }
        /////////////////////////////////

        /////////////////////////////////
        $file = public_path() . "/mySetting/weather.data";

        $content = file_get_contents($file);
        $ex_content = explode("\n", $content);

        foreach($ex_content as $v){
            list($date, $weather) = explode("|", trim($v));

            $weatherList[] = ["date" => $date, "weather" => $weather];
        }
        /////////////////////////////////

        $response = $weatherList;

        return response()->json(['data' => $response]);
    }

    ///
    public function getMoneySpendItem()
    {

        $result = DB::table('t_money_spend_item')->orderBy('order_no')->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [
                'id' => $v->id,
                'name' => $v->name,
                'order_no' => $v->order_no,
            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);
    }

    ///
    public function insertDailyStockData(Request $request)
    {

        try {

            DB::beginTransaction();

            //---------------------------------------//
            $result = DB::table('t_stock_datas')->orderBy('id')->limit(30)->get();

            $ary = [];
            foreach($result as $v){
                $ary[$v->ticker] = [
                    'ticker' => $v->ticker,
                    'name' => $v->name,
                    'hoyuu_suuryou' => $v->hoyuu_suuryou,
                    'heikin_shutoku_kagaku' => $v->heikin_shutoku_kagaku
                ];
            }
            //---------------------------------------//

            list($year, $month, $day) = explode("-", trim($request->date));

            foreach($ary as $k=>$v){
                $insert = [
                    'ticker' => $v['ticker'],
                    'name' => $v['name'],
                    'hoyuu_suuryou' => $v['hoyuu_suuryou'],
                    'heikin_shutoku_kagaku' => $v['heikin_shutoku_kagaku'],

                    'year' => $year,
                    'month' => $month,
                    'day' => $day,
                    'jika_hyoukagaku' => $request->data[$k],
                ];

                DB::table('t_stock_datas')->insert($insert);
            }

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }

    }

    ///
    public function getStockName()
    {

        $result = DB::table('t_stock_datas')->orderBy('id')->limit(30)->get();

        $ary = [];
        foreach($result as $v){
            $ary[$v->ticker] = [
                'ticker' => $v->ticker,
                'name' => $v->name,
                'hoyuu_suuryou' => $v->hoyuu_suuryou,
                'heikin_shutoku_kagaku' => $v->heikin_shutoku_kagaku
            ];
        }

        $ary2 = [];
        foreach($ary as $v){
            $ary2[] = $v;
        }

        $response = $ary2;
        return response()->json(['data' => $response]);
    }

    ///
    public function getAllStockData()
    {

        $result = DB::table('t_stock_datas')
        ->orderBy('year')->orderBy('month')->orderBy('day')->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [
                'id' => $v->id,
                'year' => $v->year,
                'month' => $v->month,
                'day' => $v->day,

                'ticker' => $v->ticker,
                'name' => $v->name,

                'hoyuu_suuryou' => $v->hoyuu_suuryou,
                'heikin_shutoku_kagaku' => $v->heikin_shutoku_kagaku,
                'jika_hyoukagaku' => $v->jika_hyoukagaku,
            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);
    }

    ///
    public function getAllToushiShintakuData()
    {

        $result = DB::table('t_toushi_shintaku_datas')
        ->orderBy('year')->orderBy('month')->orderBy('day')->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [
                'id' => $v->id,
                'year' => $v->year,
                'month' => $v->month,
                'day' => $v->day,

                'name' => $v->name,

                'shutoku_sougaku' => $v->shutoku_sougaku,
                'jika_hyoukagaku' => $v->jika_hyoukagaku,

                'relational_id' => $v->relational_id,
                'hoyuu_suuryou' => trim(strtr($v->hoyuu_suuryou, [',' => '', '口' => ''])),

            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);

    }

    ///
    public function getCreditSummary()
    {

        $result = DB::table('t_credit_summary')
        ->orderBy('year')->orderBy('month')->orderBy('use_date')->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [
                'id' => $v->id,
                'year' => $v->year,
                'month' => $v->month,

                "use_date" => $v->use_date,
                "item" => $v->item,
                "detail" => $v->detail,
                "price" => $v->price
            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);

    }

    ///
    public function getAllInvestNames()
    {

        $result = DB::table('t_invest_names')
        ->orderBy('kind')->orderBy('frame')->orderBy('deal_number')->orderBy('relational_id')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [
                'id' => $v->id,
                'kind' => $v->kind,
                'frame' => $v->frame,
                'name' => $v->name,
                'deal_number' => $v->deal_number,
                'relational_id' => $v->relational_id,
            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);

    }

    ///
    public function getAllInvestRecords()
    {

        $result = DB::table('t_invest_records')
        ->orderBy('date')->orderBy('relational_id')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [
                'id' => $v->id,
                'date' => $v->date,
                'relational_id' => $v->relational_id,
                'cost' => $v->cost,
                'price' => $v->price,
            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);

    }

    ///
    public function insertSpend(Request $request)
    {

        try {
            DB::beginTransaction();

            foreach($request->insertDataDaily as $k=>$v){
                list($year, $month, $day) = explode("-", $v['date']);
                $ymd = "{$year}{$month}{$day}";

                if($k==0){
                    DB::table('t_dailyspend')->where('year', '=', $year)->where('month', '=', $month)->where('day', '=', $day)->delete();
                }

                $insert = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'koumoku' => $v['koumoku'],
                'price' => $v['price'],
                'ymd' => $ymd,
                'created_at' => $v['date'],
                'updated_at' => $v['date'],
                'flag' => ''
                ];

                DB::table('t_dailyspend')->insert($insert);
            }

            foreach($request->insertDataCredit as $k=>$v){
                list($year, $month, $day) = explode("-", $v['date']);
                $ymd = "{$year}{$month}{$day}";

                if($k==0){
                    DB::table('t_credit')->where('year', '=', $year)->where('month', '=', $month)->where('day', '=', $day)->delete();
                }

                $insert = [
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'item' => $v['item'],
                'price' => $v['price'],
                'ymd' => $ymd,
                'created_at' => $v['date'],
                'updated_at' => $v['date']
                ];

                DB::table('t_credit')->insert($insert);
            }

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    ///
    public function getAllTimePlaceRecord()
    {

        $result = DB::table('t_timeplace')
        ->orderBy('year')->orderBy('month')->orderBy('day')->orderBy('time')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [

                'year' => $v->year,
                'month' => $v->month,
                'day' => $v->day,
                'time' => $v->time,

                'place' => $v->place,
                'price' => $v->price,

            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);

    }

    ///
    public function updateToushiShintakuRelationalId(Request $request)
    {

        try {
            DB::beginTransaction();

            foreach($request->updateData as $k=>$v){
                $update = [];
                $update['relational_id'] = $v;

                DB::table('t_toushi_shintaku_datas')->where('id', '=', $k)->update($update);
            }

            DB::commit();

            $response = $request->all();
            return response()->json(['data' => $response]);
        } catch (Exception $e) {
            DB::rollBack();
            abort(500, $e->getMessage());
        }
    }

    ///
    public function getAmazonData()
    {

        $result = DB::table('t_amazon')
        ->orderBy('year')->orderBy('month')->orderBy('day')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [

                'year' => $v->year,
                'month' => $v->month,
                'day' => $v->day,

                'price' => $v->price,

                'item' => $v->item,

            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);

    }











    ///
    public function getWorkContract()
    {
        $result = DB::table('t_work_contract')
        ->orderBy('year')->orderBy('month')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [

                'year' => $v->year,
                'month' => $v->month,

                'name' => $v->name,
                'place' => $v->place,
                'flag' => $v->flag,
            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);
    }

    ///
    public function getWorkTruth()
    {
        $result = DB::table('t_work_truth')
        ->orderBy('year')->orderBy('month')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [

                'year' => $v->year,
                'month' => $v->month,

                'name' => $v->name,
                'contract_id' => $v->contract_id,
                'place' => $v->place,

            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);
    }

    ///
    public function getWorkAnken()
    {
        $result = DB::table('t_work_anken')
        ->orderBy('year')->orderBy('month')
        ->get();

        $ary = [];
        foreach($result as $v){
            $ary[] = [

                'year' => $v->year,
                'month' => $v->month,

                'contract_id' => $v->contract_id,
                'name' => $v->name,
                'kibo' => $v->kibo,
                'koutei' => $v->koutei,
                'os' => $v->os,
                'gengo' => $v->gengo,
                'db' => $v->db,
                'tool' => $v->tool,

            ];
        }

        $response = $ary;
        return response()->json(['data' => $response]);
    }






                    ///
                    public function getBusTotalInfo()
                    {

                    // 例：先に全件を取得（大量なら ->cursor() や ->chunk() 推奨）
                    $result = DB::table('t_bus_total_info')
                    ->orderBy('operator')->orderBy('line')->orderBy('display_num')
                    ->get();

                    // 高速・空港系など「市バスではなさそう」な停留所名の除外パターン
                    $excludePattern = '/('
                    . '中央道'                     // 例：中央道日野、中央道府中…
                    . '|東名'                      // 例：東名江田、東名川崎…
                    . '|関越|東北道|常磐道|首都高'   // 高速系
                    . '|湾岸(?:線)?|京葉道路'       // 高速（湾岸線/京葉道路）
                    . '|高速(?:入口|出口)?'         // 「高速（入口/出口）」など
                    . '|インター'                   // ～インター
                    // 英字の境界を考慮：MUSIC等に誤マッチしないように前後が英字でない時のみヒット
                    . '|(?<![A-Za-z])IC(?![A-Za-z])'
                    . '|(?<![A-Za-z])SA(?![A-Za-z])'
                    . '|(?<![A-Za-z])PA(?![A-Za-z])'
                    // 空港・リムジン・T-CAT・バスタ新宿など
                    . '|空港(?:第[1-3](?:ターミナル)?)?'   // 空港/空港第1/空港第2ターミナル等
                    . '|T-?CAT'                       // T-CAT / TCAT
                    . '|バスタ(?:新宿)?'              // バスタ/バスタ新宿
                    . '|リムジン'                     // リムジンバス
                    // 必要なら他も追加：例）'|羽田空港|成田空港'
                    . ')/u';

                    $grouped = [];
                    foreach ($result as $v) {
                    // 高速系っぽい停留所名はスキップ
                    if (preg_match($excludePattern, $v->name ?? '')) {
                    continue;
                    }

                    $key = ($v->operator ?? '') . '||' . ($v->line ?? '');
                    if (!isset($grouped[$key])) {
                    $grouped[$key] = [
                    'operator' => $v->operator,
                    'line'     => $v->line,
                    'stops'    => [],
                    ];
                    }

                    $grouped[$key]['stops'][] = [
                    'order_num' => $v->order_num,
                    'name'      => $v->name,
                    'lat'       => $v->lat,
                    'lon'       => $v->lon,
                    ];
                    }

                    // 路線として成立しているものだけにする（例：停留所10以上＝市バス寄せ）
                    $grouped = array_filter($grouped, fn($r) => count($r['stops']) >= 5);

                    $ary = array_values($grouped);
                    return response()->json($ary);

                    }








    ///
    public function getMetroStampPokePoke()
    {
        $result = DB::table('t_stamp_rally_metro_pokepoke')->get();
        $response = $result;
        return response()->json(['data' => $response]);
    }








    ///
    public function getPrefTrainStation()
    {
        $result = DB::table('t_pref_train_station')->get();
        $response = $result;
        return response()->json(['data' => $response]);
    }








}
