<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class checkTrainPrefecture extends Command
{

    protected $signature = 'checkTrainPrefecture';

    protected $description = 'Command description';

    public function handle()
    {




            /*

            mysql> desc t_train;
            +--------------+-------------+------+-----+---------+----------------+
            | Field        | Type        | Null | Key | Default | Extra          |
            +--------------+-------------+------+-----+---------+----------------+
            | id           | int(11)     | NO   | PRI | NULL    | auto_increment |
| train_number | varchar(20) | YES  |     | NULL    |                |
| train_name   | text        | YES  |     | NULL    |                |
            | company_id   | int(11)     | YES  |     | NULL    |                |
            | order_number | int(11)     | YES  |     | NULL    |                |
            | pickup       | char(1)     | YES  |     | NULL    |                |
            +--------------+-------------+------+-----+---------+----------------+
            6 rows in set (0.00 sec)

            mysql> desc t_station;
            +--------------+--------------+------+-----+---------+----------------+
            | Field        | Type         | Null | Key | Default | Extra          |
            +--------------+--------------+------+-----+---------+----------------+
            | id           | int(11)      | NO   | PRI | NULL    | auto_increment |
| train_number | varchar(20)  | YES  |     | NULL    |                |
            | station_name | text         | YES  |     | NULL    |                |
            | address      | text         | YES  |     | NULL    |                |
            | lat          | varchar(100) | YES  |     | NULL    |                |
            | lng          | varchar(100) | YES  |     | NULL    |                |
| prefecture   | varchar(100) | YES  |     | NULL    |                |
            +--------------+--------------+------+-----+---------+----------------+
            7 rows in set (0.00 sec)

            */


            $station_trainNumber = [];
            $sql = " select * from t_station where prefecture is null ";
            $result = DB::select($sql);
            foreach ($result as $k => $v) {
                $station_trainNumber[$v->train_number] = "";
            }
            $s_trainNumber = array_keys($station_trainNumber);

            $train_trainNumber = [];
            $sql = " select * from t_train ";
            $result = DB::select($sql);
            foreach ($result as $k => $v) {
                $train_trainNumber[$v->train_number] = "";
            }
            $t_trainNumber = array_keys($train_trainNumber);

            $diff = array_diff($s_trainNumber, $t_trainNumber);

            print_r($diff);








//         foreach ($result as $k => $v) {

// //            if ($k > 0) {
// //                break;
// //            }

//             $_lat = substr($v->lat, 0, 4);
//             $_lng = substr($v->lng, 0, 5);


//             try {


//                 $ch = curl_init();

//                 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
//                 curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);




// $url = "https://geoapi.heartrails.com/api/json?method=searchByGeoLocation&x={$_lng}&y={$_lat}";
// echo $url;
// echo "\n";
// echo "\n";
// echo "\n";



//                 curl_setopt($ch, CURLOPT_URL, $url);

//                 $result = curl_exec($ch);

//                 $jsonStr = json_decode($result);




//                 if (is_null($jsonStr)){
//                     continue;
//                 }




//                 $prefecture = [];
//                 foreach ($jsonStr->response->location as $v2) {
//                     $prefecture[] = $v2->prefecture;
//                 }
// //            print_r($prefecture);


//                 $update = [];
//                 $update['prefecture'] = $prefecture[0];

//                 print_r($v);
//                 echo "\n";
//                 print_r($update);
//                 echo "\n";
//                 echo "\n";
//                 echo "\n";

//                 DB::table('t_station')
//                     ->where('id', $v->id)
//                     ->update($update);


//             } catch (Exception $e) {
//             }


//        }
    }
}
