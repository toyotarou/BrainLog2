<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusDataInput2 extends Command
{

    protected $signature = 'busDataInput2';

    protected $description = 'Command description';

    public function handle()
    {

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'timeout' => 10,
                'header'  => implode("\r\n", [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) ' .
                    'AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language: ja-JP,ja;q=0.9,en-US;q=0.8,en;q=0.7',
                ]),
            ],
        ]);

        $url_base = "https://ekitan.com/timetable/route-bus/station/[NUMBER]";

        for($i=8500; $i<20000; $i++){

echo $i;
echo "\n";echo "\n";

            $url = strtr($url_base, ['[NUMBER]' => $i]);

            $content = file_get_contents($url, false, $context);
            $ex_content = explode("\n", $content);

            $a = 0;
            $b = [];
            foreach ($ex_content as $k => $v) {
                if (preg_match("/ttl-type07/", trim($v))) {
                    $a = $k;
                }
            }

            $st = trim(strip_tags($ex_content[$a]));

            preg_match("/(.+)駅のバス情/", $st, $m);

            if(count($m) > 1){
                $start = $m[1];

                $result2 = DB::table('t_station')->where('station_name', '=', $start)->first();

                if($result2 != null){
                    $str = "";
                    foreach($ex_content as $v){
                        $str .= trim($v);
                    }

                    $ex_str = explode("|", strtr($str, ["><" => ">|<"]));

                    $destination = [];

                    foreach($ex_str as $v){
                        if (preg_match("/destination-col/", trim($v))){
                            $destination[] = trim($v);
                        }
                    }

                    $destination2 = [];

                    foreach($destination as $v){
                        $dest = trim(strip_tags($v));

                        $ex_dest = explode(",", $dest);
                        foreach($ex_dest as $v2){
                            $ex_dest2 = explode("・", trim($v2));
                            foreach($ex_dest2 as $v3){


                                $ex_dest3 = explode("(", trim($v3));
                                foreach($ex_dest3 as $v4){

                                    if (preg_match("/(.+)駅/", trim($v4), $m2)) {
                                        if(count($m2)>1){
                                            if(!in_array(trim($m2[1]), $destination2)){
                                                $destination2[] = trim($m2[1]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    $destination3 = [];

                    foreach($destination2 as $v){
                        $result = DB::table('t_station')->where('station_name', '=', $v)->first();
                        if($result != null){
                            if($v != $start){
                                $destination3[] = $v;
                            }
                        }
                    }

                    if(count($destination3)>0){
echo $start;
echo "\n";echo "\n";
print_r($destination3);
echo "\n";echo "\n";



                        foreach($destination3 as $v2){
                            $result3 = DB::table('t_bus_info2')->where('end_a', '=', $start)->where('end_b', '=', trim($v2))->first();
                            $result4 = DB::table('t_bus_info2')->where('end_a', '=', trim($v2))->where('end_b', '=', $start)->first();

                            if($result3 == null && $result4 == null){
                                $insert = [];
                                $insert['end_a'] = $start;
                                $insert['end_b'] = trim($v2);

                                print_r($insert);
                                echo "\n";echo "\n";

                                DB::table('t_bus_info2')->insert($insert);
                            }
                        }
                    }
                }
            }
        }
    }
}
