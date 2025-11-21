<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class getPrefTrainStation extends Command
{

    protected $signature = 'getPrefTrainStation';

    protected $description = 'Command description';

    public function handle()
    {

        $result = DB::table('t_pref_train')->where('finish', '=', 0)->get();

        foreach($result as $k=>$v){

//if($k>0){break;}

            $searchTrain = trim($v->train);

            $url = "https://express.heartrails.com/api/json?method=getStations&line={$searchTrain}";
            $content = file_get_contents($url);
            $jsonStr = json_decode($content);
            $response = $jsonStr->response;

            $stationList = $jsonStr->response->station;

            $insertList = [];

            foreach($stationList as $v2){
                $insert = [];

                $insert['pref'] = trim($v->pref);
                $insert['train_id'] = trim($v->id);
                $insert['train_name'] = trim($v->train);

                $insert['station_name'] = trim($v2->name);
                $insert['latitude'] = trim($v2->y);
                $insert['longitude'] = trim($v2->x);
                $insert['prev_station'] = trim($v2->prev);
                $insert['next_station'] = trim($v2->next);

                $insertList[] = $insert;

            }

            foreach($insertList as $v2){

print_r($v2);
echo "\n";echo "\n";echo "\n";

                DB::table('t_pref_train_station')->insert($v2);
            }

            $update = [];
            $update['finish'] = 1;

            DB::table('t_pref_train')->where('id', '=', $v->id)->update($update);

        }
    }
}
