<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class getTempleLatLng extends Command
{

    protected $signature = 'getTempleLatLng';

    protected $description = 'Command description';

    public function handle()
    {

$result = DB::table('t_temple_list_navitime')->where('temple_lat_lng_id', '=', NULL)->get();

$i=0;
foreach($result as $v){
//if($i>10){break;}

    $_lat = "";
    $_lng = "";

    try {

        $url9 = "https://maps.googleapis.com/maps/api/geocode/json?address=" . trim($v->address) . "&components=country:JP&key=AIzaSyDepeW7Aff-rAasSMRlPVR_KZOlcUYqoLw";

        $content9 = file_get_contents($url9);
        $jsonStr = json_decode($content9);

        if (isset($jsonStr->results[0]->geometry->location->lat) and trim($jsonStr->results[0]->geometry->location->lat) != "") {
            $_lat = $jsonStr->results[0]->geometry->location->lat;
        }

        if (isset($jsonStr->results[0]->geometry->location->lng) and trim($jsonStr->results[0]->geometry->location->lng) != "") {
            $_lng = $jsonStr->results[0]->geometry->location->lng;
        }

    } catch (Exception $e) {
    }

if(trim($_lat)!="" && trim($_lng)!=""){

print_r($v);
echo $_lat;
echo "\n";
echo $_lng;
echo "\n";
echo "\n";echo "\n";echo "\n";



$update = [];
$update['lat'] = $_lat;
$update['lng'] = $_lng;

DB::table('t_temple_list_navitime')->where('id', '=', $v->id)->update($update);

}

$i++;
}

    }
}
