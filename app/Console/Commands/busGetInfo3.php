<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusGetInfo3 extends Command
{

    protected $signature = 'BusGetInfo3';

    protected $description = 'Command description';

    public function handle()
    {

        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0\r\n"
            ]
        ];

        $result = DB::table('t_bus_get_data_2')->where('finish', '=', 0)->get();

        foreach($result as $k=>$v){
//if($k>0){break;}

            $url = $v->get_data_2_url;

            $context = stream_context_create($options);
            $html = file_get_contents($url, false, $context);

            $ex_content = explode("\n", $html);

            $str = "";
            foreach($ex_content as $v2){
                $str .= trim($v2);
            }

            $ex_str = explode("|", strtr($str, ["><" => ">|<"]));

$insert = [];
            foreach($ex_str as $v2){
                if(preg_match("/node_frame/", $v2)){

                    preg_match("/data-no=\"(.+)\".+data-name/", $v2, $m1);
                    preg_match("/data-name=\"(.+)\".+data-lat/", $v2, $m2);
                    preg_match("/data-lat=\"(.+)\"data-lon/", $v2, $m3);
                    preg_match("/data-lon=\"(.+)\">/", $v2, $m4);

                    $insert[] = [
                        'bus_company_name' => $v->bus_company_name,
                        'bus_route_name' => $v->bus_route_name,
                        'bus_stop_number' => trim($m1[1]),
                        'bus_stop_name' => trim($m2[1]),
                        'bus_stop_lat' => trim($m3[1]),
                        'bus_stop_lon' => trim($m4[1]),
                        'get_data_1_url' => $v->get_data_1_url,
                        'get_data_2_url' => $v->get_data_2_url
                    ];
                }
            }

print_r($insert);

DB::table('t_bus_get_data_3')->insert($insert);

DB::table('t_bus_get_data_2')->where('id', '=', $v->id)->update(['finish' => 1]);

        }
    }
}
