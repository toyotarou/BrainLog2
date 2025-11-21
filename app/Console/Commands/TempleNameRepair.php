<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class TempleNameRepair extends Command
{

    protected $signature = 'TempleNameRepair';

    protected $description = 'Command description';

    public function handle()
    {
        $ary = [];

        $result = DB::table('t_temple_list')->orderBy('id')->get();

        $idAry = [];

        foreach($result as $v){
            if(!in_array($v->id, $idAry)){
                $ary[] = $v->name;

                $sql = " select * from t_temple where temple='{$v->name}' or memo like '%{$v->name}%'; ";
                $result2 = DB::select($sql);

                foreach($result2 as $v2){
                    $ary[] = $v2->id;
                    $ary[] = $v2->temple;
                    $ary[] = $v2->memo;
                    $ary[] = "-----";
                }

                $ary[] = "";$ary[] = "";

                $result3 = DB::table('t_temple_latlng')->where('temple', '=', $v->name)->first();

                $ary[] = (is_null($result3))? "" : $result3->id;
                $ary[] = (is_null($result3))? "" : $result3->temple;

                $ary[] = "-------------------------------------------------";

                $ary[] = "";$ary[] = "";$ary[] = "";
                $ary[] = "";$ary[] = "";$ary[] = "";
                $ary[] = "";$ary[] = "";$ary[] = "";
                $ary[] = "";$ary[] = "";$ary[] = "";
                $ary[] = "";$ary[] = "";$ary[] = "";

print_r($ary);
echo "\n";echo "\n";echo "\n";

            }

            $idAry[] = $v->id;
        }

        $file = "/var/www/html/BrainLog/public/mySetting/TempleNameRepair.data";

        file_put_contents($file , implode("\n" , $ary));
        chmod($file , 0777);

    }
}
