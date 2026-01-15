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
105		積立購入				2026/1/8	完了（約定）	2026/1/9	楽天・全米株式インデックス・ファンド(楽天・VTI)	特定	10,000	10,000	0	(クレジットカード決済)				4:44			631					-				2026/1/15	再投資型	- 口	2,488 口	40,192
106		積立購入				2026/1/8	完了（約定）	2026/1/9	eMAXIS Slim 米国株式(S&P500)	特定	10,000	10,000	0	(クレジットカード決済)				4:44			632					-				2026/1/15	再投資型	- 口	2,514 口	39,785
107		積立購入				2026/1/8	完了（約定）	2026/1/9	iFreeNEXT インド株インデックス	特定	10,000	10,000	0	(クレジットカード決済)				4:44			633					-				2026/1/16	再投資型	- 口	6,339 口	15,777
108		積立購入				2026/1/8	完了（約定）	2026/1/9	iTrust インド株式	特定	10,000	10,000	0	(クレジットカード決済)				4:44			634					-				2026/1/19	再投資型	- 口	4,033 口	24,798
109		積立購入				2025/12/31	完了（約定）	2026/1/6	たわらノーロード S&P500	NISAつみたて投資枠	3,333	3,333	0	(証券口座)				4:49			627					-				2026/1/9	再投資型	- 口	1,617 口	20,610
110		積立購入				2026/1/8	完了（約定）	2026/1/9	eMAXIS Slim 米国株式(S&P500)	NISAつみたて投資枠	10,000	10,000	0	(クレジットカード決済)				4:44			637					-				2026/1/15	再投資型	- 口	2,514 口	39,785
110		積立購入				2025/12/31	完了（約定）	2026/1/6	eMAXIS Slim 米国株式(S&P500)	NISAつみたて投資枠	20,000	20,000	0	(証券口座)				4:49			628					-				2026/1/9	再投資型	- 口	5,056 口	39,561
111		積立購入				2025/12/19	完了（約定）	2025/12/22	eMAXIS Slim 全世界株式(オール・カントリー)(オルカン)	NISAつみたて投資枠	6,667	6,667	0	(楽天キャッシュ)				4:01			620					-				2025/12/26	再投資型	- 口	2,008 口	33,206
112		積立購入				2025/12/25	完了（約定）	2025/12/29	iFree S&P500インデックス	NISAつみたて投資枠	10,000	10,000	0	(楽天キャッシュ)				4:02			625					-				2026/1/6	再投資型	- 口	2,275 口	43,973
112		積立購入				2026/1/8	完了（約定）	2026/1/9	iFree S&P500インデックス	NISAつみたて投資枠	20,000	20,000	0	(クレジットカード決済)				4:44			636					-				2026/1/15	再投資型	- 口	4,533 口	44,120
112		積立購入				2025/12/19	完了（約定）	2025/12/22	iFree S&P500インデックス	NISAつみたて投資枠	30,000	30,000	0	(証券口座)				3:58			618					-				2025/12/25	再投資型	- 口	6,868 口	43,682
113		積立購入				2026/1/8	完了（約定）	2026/1/9	NZAM・ベータ S&P500	NISA成長投資枠	30,000	30,000	0	(クレジットカード決済)				4:44			635					-				2026/1/15	再投資型	- 口	9,649 口	31,094
122		積立購入				2025/12/20	完了（約定）	2025/12/23	iFreeNEXT FANG+インデックス	NISA成長投資枠	10,000	10,000	0	(証券口座)				3:59			621					-				2025/12/26	再投資型	- 口	1,187 口	84,266
123		積立購入				2025/12/24	完了（約定）	2025/12/30	(アムンディ・インデックスシリーズ)オールカントリー・高配当株	NISA成長投資枠	10,000	10,000	0	(証券口座)				4:00			623					-				2026/1/7	再投資型	- 口	8,575 口	11,662
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
