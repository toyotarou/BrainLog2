<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class BusTotalInfoInput extends Command
{

    protected $signature = 'BusTotalInfoInput';

    protected $description = 'Command description';

    public function handle()
    {

        /////////////////////////////////////////////////////////////////////
        $busStops = [];
        $result4 = DB::table('t_bus_total_info_stops')->get();
        foreach($result4 as $v4){
            $busStops[$v4->id] = $v4;
        }
        /////////////////////////////////////////////////////////////////////

        $sql = " select * from t_bus_total_info_routes ";
        $result = DB::select($sql);

        foreach($result as $v){
            $sql2 = " select * from t_bus_total_info_route_stops where route_id = " . $v->id . " order by seq ";
            $result2 = DB::select($sql2);

            foreach($result2 as $v2){
                $busStopInfo = $busStops[$v2->stop_id];

                $insert = [];
                $insert['operator'] = $v->operator;
                $insert['line'] = $v->line;
                $insert['order_num'] = $v2->seq;
                $insert['name'] = $busStopInfo->name;
                $insert['lat'] = $busStopInfo->lat;
                $insert['lon'] = $busStopInfo->lon;

print_r($insert);

                DB::table('t_bus_total_info')->insert($insert);
            }
        }
    }
}
