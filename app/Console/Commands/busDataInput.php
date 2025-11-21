<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusDataInput extends Command
{

    protected $signature = 'busDataInput';

    protected $description = 'Command description';

    public function handle()
    {

        $url = "https://www.tokyobus.or.jp/app/navi/navimap";
        $content = file_get_contents($url);
        $ex_content = explode("\n", $content);

        $a = 0;
        $b = [];
        foreach ($ex_content as $k => $v) {
            if (preg_match("/terminal_map/", trim($v))) {
                $a = $k;
            }

            if (preg_match("/<\/ul>/", trim($v))) {
                $b[] = $k;
            }
        }

        $str = "";
        for($i=$a; $i<$b[0]; $i++){
            $str .= trim($ex_content[$i]);
        }

        $ex_str = explode("|", strtr($str, ["><" => ">|<"]));

        $startPoint = [];

        foreach($ex_str as $v){
            if (preg_match("/<a/", trim($v))) {

                $ex_v = explode("'", trim($v));
                for($i=0; $i<count($ex_v); $i++){
                    if(trim($ex_v[$i]) == 'start'){
                        $st2 = trim($ex_v[$i+2]);
                    }
                }

                $st = trim(strip_tags($v));
print_r($st);
echo "\n";echo "\n";



                $result = DB::table('t_station')->where('station_name', '=', $st)->first();

                if($result != null){
                    $startPoint[] = $st2;
                }
            }
        }

print_r($startPoint);
echo "\n";echo "\n";



        $goalPoint = [];

        $url2_base = "https://www.tokyobus.or.jp/app/navi/navilist/start/[START]/dest//pageID/[PAGE]/";

        foreach($startPoint as $k=>$v){

            preg_match("/(.+)駅/", trim($v), $m);

            if(count($m)>0){
                $startStation = $m[1];

                $ary = [];

                for($i=1; $i<=3; $i++){

                    $url2 = strtr($url2_base, ['[START]'=>$v, '[PAGE]'=>$i]);
                    $content2 = file_get_contents($url2);
                    $ex_content2 = explode("\n", $content2);

                    $c = 0;
                    $d = [];
                    foreach ($ex_content2 as $k2 => $v2) {
                        if (preg_match("/result_table/", trim($v2))) {
                            $c = $k2;
                        }

                        if (preg_match("/<\/table>/", trim($v2))) {
                            $d[] = $k2;
                        }
                    }

                    $str2 = "";
                    for($j=$c; $j<$d[1]; $j++){
                        $str2 .= trim($ex_content2[$j]);
                    }

                    $ex_str2 = explode("|", strtr($str2, ["><" => ">|<"]));

                    $ary2 = [];
                    foreach($ex_str2 as $v2){
                        if (preg_match("/goal/", trim($v2))) {
                            if (!preg_match("/".$startStation."/", trim($v2))) {

                                $goal = trim(strip_tags($v2));

                                preg_match("/(.+)駅/", $goal, $m2);

                                if($m2 != null){
                                    $goal2 = $m2[1];

                                    $result2 = DB::table('t_station')->where('station_name', '=', $goal2)->first();

                                    if($result2 != null){
                                        if(!in_array($goal2, $ary2)){
                                            $ary2[] = $goal2;
                                        }
                                    }
                                }
                            }
                        }
                    }

                    if(count($ary2)>0){

echo $startStation;
echo "\n";
print_r($ary2);
echo "\n";echo "\n";echo "\n";echo "\n";echo "\n";echo "\n";



                        foreach($ary2 as $v2){
                            $result3 = DB::table('t_bus_info2')->where('end_a', '=', $startStation)->where('end_b', '=', trim($v2))->first();
                            $result4 = DB::table('t_bus_info2')->where('end_a', '=', trim($v2))->where('end_b', '=', $startStation)->first();

                            if($result3 == null && $result4 == null){
                                $insert = [];
                                $insert['end_a'] = $startStation;
                                $insert['end_b'] = trim($v2);

                                print_r($insert);
                                echo "\n";echo "\n";echo "\n";echo "\n";echo "\n";echo "\n";

                                DB::table('t_bus_info2')->insert($insert);
                            }
                        }
                    }
                }
            }
        }
    }
}
