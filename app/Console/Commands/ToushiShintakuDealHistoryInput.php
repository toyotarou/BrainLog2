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

$relationalIds = [];
$orderNumbers = [];

$result = DB::table('t_toushi_shintaku_deal_history')->get();

foreach($result as $v){
$relationalIds['{$v->account_kind}|{$v->fund_name}'] = $v->relational_id;

$orderNumbers[] = $v->order_number;
}



$str = "
積立購入	2026/4/25	完了（約定）	2026/4/28	iFree S&P500インデックス	NISAつみたて投資枠	10,000	10,000	0	(楽天キャッシュ)	3:58			750					-	2026/5/7	再投資型	#NAME?	2,149 口	46,536
積立購入	2026/4/24	完了（約定）	2026/4/27	(アムンディ・インデックスシリーズ)オールカントリー・高配当株	NISA成長投資枠	10,000	10,000	0	(証券口座)	3:57			748					-	2026/5/1	再投資型	#NAME?	7,945 口	12,586
積立購入	2026/4/22	完了（約定）	2026/4/23	iFreeNEXT FANG+インデックス	NISA成長投資枠	10,000	10,000	0	(証券口座)	4:01			746					-	2026/4/28	再投資型	#NAME?	1,145 口	87,369
積立購入	2026/4/18	完了（約定）	2026/4/21	eMAXIS Slim 全世界株式(オール・カントリー)(オルカン)	NISAつみたて投資枠	6,667	6,667	0	(楽天キャッシュ)	3:56			745					-	2026/4/27	再投資型	#NAME?	1,864 口	35,766
積立購入	2026/4/18	完了（約定）	2026/4/21	eMAXIS Slim 先進国債券インデックス(除く日本)	NISA成長投資枠	10,000	10,000	0	(証券口座)	3:51			742					-	2026/4/24	再投資型	#NAME?	6,392 口	15,646
積立購入	2026/4/18	完了（約定）	2026/4/20	eMAXIS Slim 国内債券インデックス	NISA成長投資枠	10,000	10,000	0	(証券口座)	3:51			741					-	2026/4/23	再投資型	#NAME?	11,562 口	8,649
積立購入	2026/4/18	完了（約定）	2026/4/21	iFree S&P500インデックス	NISAつみたて投資枠	30,000	30,000	0	(証券口座)	3:51			743					-	2026/4/24	再投資型	#NAME?	6,527 口	45,963
積立購入	2026/4/16	完了（約定）	2026/4/17	楽天・プラス・オールカントリー株式インデックス・ファンド(楽天・プラス・オールカントリー)	NISA成長投資枠	10,000	10,000	0	(証券口座)	4:01			739					-	2026/4/24	再投資型	#NAME?	5,457 口	18,325
積立購入	2026/4/15	完了（約定）	2026/4/16	たわらノーロード 先進国株式	NISA成長投資枠	10,000	10,000	0	(楽天キャッシュ)	4:00			738					-	2026/4/21	再投資型	#NAME?	2,203 口	45,404
積立購入	2026/4/15	完了（約定）	2026/4/16	eMAXIS Slim 米国株式(S&P500)	NISA成長投資枠	10,000	10,000	0	(証券口座)	3:55			735					-	2026/4/21	再投資型	#NAME?	2,445 口	40,910
積立購入	2026/4/15	完了（約定）	2026/4/16	iFree S&P500インデックス	NISA成長投資枠	10,000	10,000	0	(証券口座)	3:55			736					-	2026/4/21	再投資型	#NAME?	2,205 口	45,351
積立購入	2026/4/11	完了（約定）	2026/4/14	楽天・全米株式インデックス・ファンド(楽天・VTI)	NISA成長投資枠	20,000	20,000	0	(証券口座)	3:51			733					-	2026/4/17	再投資型	#NAME?	4,921 口	40,648
積立購入	2026/4/11	完了（約定）	2026/4/14	iFree S&P500インデックス	特定	20,000	20,000	0	(証券口座)	3:51			732					-	2026/4/17	再投資型	#NAME?	4,487 口	44,579
積立購入	2026/4/9	完了（約定）	2026/4/10	Tracers MSCIオール・カントリー・インデックス(全世界株式)	NISA成長投資枠	10,000	10,000	0	(証券口座)	4:04			730					-	2026/4/16	再投資型	#NAME?	4,989 口	20,046
積立購入	2026/4/8	完了（約定）	2026/4/9	iFree S&P500インデックス	NISAつみたて投資枠	20,000	20,000	0	(クレジットカード決済)	3:57			728					-	2026/4/14	再投資型	#NAME?	4,565 口	43,819
積立購入	2026/4/8	完了（約定）	2026/4/9	iFreeNEXT インド株インデックス	特定	10,000	10,000	0	(クレジットカード決済)	3:57			725					-	2026/4/15	再投資型	#NAME?	6,942 口	14,405
積立購入	2026/4/8	完了（約定）	2026/4/9	iTrust インド株式	特定	10,000	10,000	0	(クレジットカード決済)	3:57			726					-	2026/4/16	再投資型	#NAME?	4,509 口	22,181
積立購入	2026/4/8	完了（約定）	2026/4/9	eMAXIS Slim 米国株式(S&P500)	NISAつみたて投資枠	10,000	10,000	0	(クレジットカード決済)	3:57			729					-	2026/4/14	再投資型	#NAME?	2,530 口	39,527
積立購入	2026/4/8	完了（約定）	2026/4/9	NZAM・ベータ S&P500	NISA成長投資枠	30,000	30,000	0	(クレジットカード決済)	3:57			727					-	2026/4/14	再投資型	#NAME?	9,715 口	30,880
積立購入	2026/4/8	完了（約定）	2026/4/9	楽天・全米株式インデックス・ファンド(楽天・VTI)	特定	10,000	10,000	0	(クレジットカード決済)	3:57			723					-	2026/4/14	再投資型	#NAME?	2,502 口	39,974
積立購入	2026/4/8	完了（約定）	2026/4/9	eMAXIS Slim 米国株式(S&P500)	特定	10,000	10,000	0	(クレジットカード決済)	3:57			724					-	2026/4/14	再投資型	#NAME?	2,530 口	39,527
積立購入	2026/4/4	完了（約定）	2026/4/7	たわらノーロード S&P500	NISAつみたて投資枠	3,333	3,333	0	(証券口座)	3:50			720					-	2026/4/10	再投資型	#NAME?	1,651 口	20,188
積立購入	2026/4/4	完了（約定）	2026/4/7	eMAXIS Slim 米国株式(S&P500)	NISAつみたて投資枠	20,000	20,000	0	(証券口座)	3:50			721					-	2026/4/10	再投資型	#NAME?	5,161 口	38,757
積立購入	2026/3/25	完了（約定）	2026/3/26	iFree S&P500インデックス	NISAつみたて投資枠	10,000	10,000	0	(楽天キャッシュ)	4:08			715					-	2026/3/31	再投資型	#NAME?	2,340 口	42,739
積立購入	2026/3/24	完了（約定）	2026/3/25	(アムンディ・インデックスシリーズ)オールカントリー・高配当株	NISA成長投資枠	10,000	10,000	0	(証券口座)	4:14			713					-	2026/3/30	再投資型	#NAME?	8,233 口	12,146

";

        $ex_str = explode("\n", $str);

        foreach ($ex_str as $k=>$v) {
            if(trim($v)==""){continue;}

//if($k>3){break;}

            $ex_v = explode("\t", trim($v));

            $insert = [];

            $insert['deal_kind'] = trim($ex_v[0]);

            $order_date = trim($ex_v[1]);
            $insert['order_date'] = sprintf("%04d", trim(explode("/", $order_date)[0])) . "-" . sprintf("%02d", trim(explode("/", $order_date)[1])) . "-" . sprintf("%02d", explode("/", $order_date)[2]);

            $insert['order_status'] = trim($ex_v[2]);

            $contract_date = trim($ex_v[3]);
            $insert['contract_date'] = sprintf("%04d", trim(explode("/", $contract_date)[0])) . "-" . sprintf("%02d", trim(explode("/", $contract_date)[1])) . "-" . sprintf("%02d", explode("/", $contract_date)[2]);

            $insert['fund_name'] = trim($ex_v[4]);
            $insert['account_kind'] = trim($ex_v[5]);

            $key = $insert['account_kind'] . '|' . $insert['fund_name'];

            if(isset($relationalIds[$key])){
                $insert['relational_id'] = $relationalIds[$key];
            }

            $insert['order_price'] = trim(strtr($ex_v[6], [","=>""]));
            if($insert['order_price'] == "-"){
                $insert['order_price'] = 0;
            }

            $insert['pay_price'] = trim(strtr($ex_v[7], [","=>""]));
            if($insert['pay_price'] == "-"){
                $insert['pay_price'] = 0;
            }

            $insert['keihi'] = trim(strtr($ex_v[8], [","=>""]));

            $insert['pay_method'] = trim(strtr($ex_v[9], ["("=>"", ")"=>""]));

            $order_time = trim($ex_v[10]);
            $insert['order_time'] = sprintf("%02d", trim(explode(":", $order_time)[0])) . ":" . sprintf("%02d", explode(":", $order_time)[1]);

            $insert['order_number'] = trim($ex_v[13]);
            if($insert['order_number'] == ""){
                $insert['order_number'] = 0;
            }

            $receive_date = trim($ex_v[19]);
            $insert['receive_date'] = sprintf("%04d", trim(explode("/", $receive_date)[0])) . "-" . sprintf("%02d", trim(explode("/", $receive_date)[1])) . "-" . sprintf("%02d", explode("/", $receive_date)[2]);

            $insert['course'] = trim($ex_v[20]);

            $insert['suuryou'] = trim(strtr($ex_v[22], [","=>"", "口"=>""]));
            if($insert['suuryou'] == "-"){
                $insert['suuryou'] = 0;
            }

            $insert['kijun_price'] = trim(strtr($ex_v[23], [","=>""]));
            if($insert['kijun_price'] == "-"){
                $insert['kijun_price'] = 0;
            }

            if(!in_array($insert['order_number'], $orderNumbers)){
echo $insert['order_number'];
echo "\n";
print_r($insert);
DB::table('t_toushi_shintaku_deal_history')->insert($insert);
echo "\n";echo "\n";echo "\n";
            }

/*

select * from `t_toushi_shintaku_deal_history` where fund_name = (select fund_name from `t_toushi_shintaku_deal_history` where relational_id ='104' limit 1) order by account_kind,order_price

shintaku	特定口座	エマージング・ボンド・ファンド・南アフリカランドコース（毎月分配型）	1	104
shintaku	特定口座	楽天・全米株式インデックス・ファンド(楽天・VTI)	2	105
shintaku	特定口座	eMAXIS Slim 米国株式(S&P500)	3	106
shintaku	特定口座	iFreeNEXT インド株インデックス	6	107
shintaku	特定口座	iTrust インド株式	7	108
shintaku	NISAつみたて投資枠	たわらノーロード S&P500 - NISAつみたて投資枠	8	109
shintaku	NISAつみたて投資枠	eMAXIS Slim 米国株式(S&P500)	9	110
shintaku	NISAつみたて投資枠	eMAXIS Slim 全世界株式(オール・カントリー)	10	111
shintaku	NISAつみたて投資枠	iFree S&P500インデックス - NISAつみたて投資枠	11	112
shintaku	NISA成長投資枠	NZAM・ベータ S&P500	12	113
shintaku	NISA成長投資枠	eMAXIS Slim 米国株式(S&P500)	17	114
shintaku	NISA成長投資枠	iFree S&P500インデックス	18	115
shintaku	つみたてNISA	たわらノーロード S&P500	21	116
shintaku	つみたてNISA	iFree S&P500インデックス	22	117
shintaku	特定口座	iFree S&P500インデックス	4	118
shintaku	NISA成長投資枠	たわらノーロード 先進国株式	13	119
shintaku	NISA成長投資枠	楽天・全米株式インデックス・ファンド(楽天・VTI)	14	120
shintaku	NISA成長投資枠	楽天・プラス・オールカントリー株式インデックス・ファンド(楽天・プラス・オールカントリー)	15	121
shintaku	NISA成長投資枠	iFreeNEXT FANG+インデックス	19	122
shintaku	NISA成長投資枠	(アムンディ・インデックスシリーズ)オールカントリー・高配当株	20	123
shintaku	特定口座	S&P500(マルチアイ搭載)	5	124
shintaku	NISA成長投資枠	Tracers MSCIオール・カントリー・インデックス(全世界株式)	16	125
*/

        }
    }
}
