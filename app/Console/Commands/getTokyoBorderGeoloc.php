<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class getTokyoBorderGeoloc extends Command
{

    protected $signature = 'getTokyoBorderGeoloc';

    protected $description = 'Command description';

    public function handle()
    {
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
//         curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
// //        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
//         curl_setopt($ch, CURLOPT_URL, 'https://overpass-api.de/api/interpreter?data=%5Bout%3Ajson%5D%3Barea%5Bname%3D%27東京都%27%5D%3B%28way%28area%29%5B%27boundary%27%3D%27administrative%27%5D%3B%29%3Bout%20body%3B');
//         $result = curl_exec($ch);

//         $result = json_decode($result, true);

//         curl_close($ch);

        

//         print_r($result);
//         print_r(count($result['elements']));











//         $file = "/var/www/html/BrainLog/storage/tokyo_border_geoloc";
//         $content = file_get_contents($file);
//         $ex_content = explode("\n", $content);

//         $a = [];
//         $b = [];

//         foreach ($ex_content as $k => $v) {
//             if (trim($v) == "") {
//                 continue;
//             }

//             if(preg_match("/\[nodes\] => Array/", trim($v))){
//                 $a[] = $k;
//             }

//             if(preg_match("/\[tags\] => Array/", trim($v))){
//                 $b[] = $k;
//             }
//         }

//         $nodes = [];
//         for($i=0; $i<count($a); $i++){
//             for($j=$a[$i]; $j<$b[$i]; $j++){
//                 if(preg_match("/\[.+\] => (.+)/", $ex_content[$j], $m)){
//                     if(count($m) > 1){
//                         if(trim($m[1]) != "Array"){
//                             $nodes[] = trim($m[1]);
//                         }
//                     }
//                 }
//             }
//         }

// //        print_r($nodes);





// foreach($nodes as $v){

//     $result = DB::table('t_tokyo_border_geoloc')->where('node', '=', trim($v))->first();

//     if($result == null){
//         $insert = [];
//         $insert['node'] = trim($v);

//         print_r($insert);

//         DB::table('t_tokyo_border_geoloc')->insert($insert);
//     }
// }



        









/*
curl -X GET "https://overpass-api.de/api/interpreter?data=%5Bout%3Ajson%5D%3Bnode%28id%3A12775780183%29%3Bout%3B"

curl -X GET "https://overpass-api.de/api/interpreter?data=%5Bout%3Ajson%5D%3Bnode%28id%3A
12775780183
%29%3Bout%3B"

URLエンコードされた部分の説明
%5B は [ のエンコード
%5D は ] のエンコード
%28 は ( のエンコード
%29 は ) のエンコード
%27 は '（シングルクォート）のエンコード
%3B は ; のエンコード
%20 は スペースのエンコード

*/







$curl_url_base = "https://overpass-api.de/api/interpreter?data=%5Bout%3Ajson%5D%3Bnode%28id%3A{NODE}%29%3Bout%3B";

$sql = "select * from t_tokyo_border_geoloc where latitude is null or latitude = ''";
$result = DB::select($sql);
foreach ($result as $k => $v) {
//    if($k>1){break;}

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, strtr($curl_url_base, ['{NODE}' => $v->node]));
    $result = curl_exec($ch);

    $result = json_decode($result, true);

    curl_close($ch);

    if(($result['elements'][0]['lat'] != "") && ($result['elements'][0]['lon'] != "")){
        $update = [];
        $update['latitude'] = substr($result['elements'][0]['lat'], 0, 5);
        $update['longitude'] = substr($result['elements'][0]['lon'], 0, 6);

        echo $v->node;
        echo "\n";
        print_r($update);
        echo "\n";echo "\n";echo "\n";

        DB::table('t_tokyo_border_geoloc')->where('node', '=', $v->node)->update($update);
    }
}












    }
}
