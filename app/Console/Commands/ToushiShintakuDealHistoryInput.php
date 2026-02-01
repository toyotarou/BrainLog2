<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class ToushiShintakuDealHistoryInput extends Command
{

    protected $signature = 'ToushiShintakuDealHistoryInput';

    protected $description = 'Command description';

    public function handle()
    {

$str = "
111		積立購入				2026/1/17	完了（約定）	2026/1/21	eMAXIS Slim 全世界株式(オール・カントリー)(オルカン)	NISAつみたて投資枠	6,667	6,667	0	(楽天キャッシュ)				4:04			652					-				2026/1/27	再投資型	#NAME?	1,974 口	33,774
114		積立購入				2026/1/15	完了（約定）	2026/1/16	eMAXIS Slim 米国株式(S&P500)	NISA成長投資枠	10,000	10,000	0	(証券口座)				4:03			644					-				2026/1/21	再投資型	#NAME?	2,479 口	40,337
115		積立購入				2026/1/15	完了（約定）	2026/1/16	iFree S&P500インデックス	NISA成長投資枠	10,000	10,000	0	(証券口座)				4:03			645					-				2026/1/21	再投資型	#NAME?	2,236 口	44,733
117		積立購入				2026/1/17	完了（約定）	2026/1/21	iFree S&P500インデックス	NISAつみたて投資枠	30,000	30,000	0	(証券口座)				4:01			650					-				2026/1/26	再投資型	#NAME?	6,875 口	43,641
118		積立購入				2026/1/10	完了（約定）	2026/1/14	iFree S&P500インデックス	特定	20,000	20,000	0	(証券口座)				4:05			638					-				2026/1/19	再投資型	#NAME?	4,446 口	44,993
119		積立購入				2026/1/15	完了（約定）	2026/1/16	たわらノーロード 先進国株式	NISA成長投資枠	10,000	10,000	0	(楽天キャッシュ)				4:08			647					-				2026/1/21	再投資型	#NAME?	2,245 口	44,546
120		積立購入				2026/1/10	完了（約定）	2026/1/14	楽天・全米株式インデックス・ファンド(楽天・VTI)	NISA成長投資枠	20,000	20,000	0	(証券口座)				4:05			639					-				2026/1/19	再投資型	#NAME?	4,879 口	40,996
121		積立購入				2026/1/16	完了（約定）	2026/1/19	楽天・プラス・オールカントリー株式インデックス・ファンド(楽天・プラス・オールカントリー)	NISA成長投資枠	10,000	10,000	0	(証券口座)				4:06			648					-				2026/1/26	再投資型	#NAME?	5,671 口	17,634
124		積立購入				2026/1/14	完了（約定）	2026/1/15	S&P500(マルチアイ搭載)	特定	10,000	10,000	0	(証券口座)				4:29			642					-				2026/1/20	再投資型	#NAME?	7,241 口	13,810
";

        $ex_str = explode("\n", $str);

        foreach ($ex_str as $k=>$v) {
            if(trim($v)==""){continue;}

//if($k>3){break;}

            $ex_v = explode("\t", trim($v));

            $insert = [];

            $insert['relational_id'] = trim($ex_v[0]);
            $insert['deal_kind'] = trim($ex_v[2]);

            $order_date = trim($ex_v[6]);
            $insert['order_date'] = sprintf("%04d", trim(explode("/", $order_date)[0])) . "-" . sprintf("%02d", trim(explode("/", $order_date)[1])) . "-" . sprintf("%02d", explode("/", $order_date)[2]);

            $order_time = trim($ex_v[18]);
            $insert['order_time'] = sprintf("%02d", trim(explode(":", $order_time)[0])) . ":" . sprintf("%02d", explode(":", $order_time)[1]);

            $insert['order_status'] = trim($ex_v[7]);

            $contract_date = trim($ex_v[8]);
            $insert['contract_date'] = sprintf("%04d", trim(explode("/", $contract_date)[0])) . "-" . sprintf("%02d", trim(explode("/", $contract_date)[1])) . "-" . sprintf("%02d", explode("/", $contract_date)[2]);

            $insert['fund_name'] = trim($ex_v[9]);
            $insert['account_kind'] = trim($ex_v[10]);

            $insert['order_price'] = trim(strtr($ex_v[11], [","=>""]));
            if($insert['order_price'] == "-"){
                $insert['order_price'] = 0;
            }
            $insert['pay_price'] = trim(strtr($ex_v[12], [","=>""]));
            if($insert['pay_price'] == "-"){
                $insert['pay_price'] = 0;
            }

            $insert['keihi'] = trim(strtr($ex_v[13], [","=>""]));

            $insert['pay_method'] = trim(strtr($ex_v[14], ["("=>"", ")"=>""]));

            $insert['order_number'] = trim($ex_v[21]);
            if($insert['order_number'] == ""){
                $insert['order_number'] = 0;
            }

            $receive_date = trim($ex_v[30]);
            $insert['receive_date'] = sprintf("%04d", trim(explode("/", $receive_date)[0])) . "-" . sprintf("%02d", trim(explode("/", $receive_date)[1])) . "-" . sprintf("%02d", explode("/", $receive_date)[2]);

            $insert['course'] = trim($ex_v[31]);
            $insert['suuryou'] = trim(strtr($ex_v[33], [","=>"", "口"=>""]));
            if($insert['suuryou'] == "-"){
                $insert['suuryou'] = 0;
            }
            $insert['kijun_price'] = trim(strtr($ex_v[34], [","=>""]));
            if($insert['kijun_price'] == "-"){
                $insert['kijun_price'] = 0;
            }

print_r($insert);

            DB::table('t_toushi_shintaku_deal_history')->insert($insert);
        }

    }
}
