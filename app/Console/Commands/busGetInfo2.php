<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusGetInfo2 extends Command
{

    protected $signature = 'BusGetInfo2';

    protected $description = 'Command description';

    public function handle()
    {

        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0\r\n"
            ]
        ];

        $result = DB::table('t_bus_get_data_1')->where('finish', '=', 0)->get();

        foreach($result as $k=>$v){

//if($k>0){break;}

$insert = [];

            for($i=1; $i<=10; $i++){
                $url = $v->get_data_1_url . "&p=" . $i;

                $context = stream_context_create($options);
                $html = file_get_contents($url, false, $context);

                $ex_content = explode("\n", $html);

                foreach($ex_content as $v2){
                    if(preg_match("/href=\"\/bus\/route/", $v2) && !preg_match("/&p=/", $v2)){

                        $get_data_2_url = "https://www.navitime.co.jp";
                        $ex_v = explode('"', $v2);
                        foreach($ex_v as $v3){
                            if(preg_match("/route/", $v3)){
                                $get_data_2_url .= trim($v3);
                            }
                        }

                        $bus_route_name = trim(strip_tags($v2));

                        $insert[] = [
                            'bus_company_name' => $v->bus_company_name,
                            'bus_route_name' => $bus_route_name,
                            'get_data_1_url' => $v->get_data_1_url,
                            'get_data_2_url' => $get_data_2_url,
                            'finish' => 0
                        ];
                    }
                }
            }

print_r($insert);

DB::table('t_bus_get_data_2')->insert($insert);

DB::table('t_bus_get_data_1')->where('id', '=', $v->id)->update(['finish' => 1]);

        }
    }
}
