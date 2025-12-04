<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class navitimeTempleLatLngIdUpdate extends Command
{

    protected $signature = 'navitimeTempleLatLngIdUpdate';

    protected $description = 'Command description';

    public function handle()
    {
        ////////////////////////////////////////////
        $templeLatLngIds = [];
        $result = DB::table('t_temple_latlng')->get();
        foreach($result as $v){
            $templeLatLngIds[$v->temple] = $v->id;
        }
        ////////////////////////////////////////////

        $result = DB::table('t_temple_list_navitime')->get();
        foreach($result as $v){

            if(isset($templeLatLngIds[$v->name])){
                $update = [];
                $update['temple_lat_lng_id'] = $templeLatLngIds[$v->name];

print_r($update);

                DB::table('t_temple_list_navitime')->where('id', '=', $v->id)->update($update);
            }
        }
    }
}
