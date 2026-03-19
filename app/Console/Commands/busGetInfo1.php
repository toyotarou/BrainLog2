<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusGetInfo1 extends Command
{

    protected $signature = 'BusGetInfo1';

    protected $description = 'Command description';

    public function handle()
    {
        $url = "https://www.navitime.co.jp/bus/route/busrouteCList?aCode=13";

        $options = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: Mozilla/5.0\r\n"
            ]
        ];

        $context = stream_context_create($options);
        $html = file_get_contents($url, false, $context);

        $ex_content = explode("\n", $html);

        foreach($ex_content as $v){
            if(preg_match("/busroutelist/", $v)){

                $insert = [];

                $get_data_1_url = "https://www.navitime.co.jp";
                $ex_v = explode('"', $v);
                foreach($ex_v as $v2){
                    if(preg_match("/route/", $v2)){
                        $get_data_1_url .= trim($v2);
                    }
                }

                $bus_company_name = trim(strip_tags($v));

                $insert = [
                    'get_data_1_url' => $get_data_1_url,
                    'bus_company_name' => $bus_company_name,
                    'finish' => 0
                ];

print_r($insert);
echo "\n\n\n";

                DB::table('t_bus_get_data_1')->insert($insert);

            }
        }
    }
}
