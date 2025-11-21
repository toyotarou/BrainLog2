<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class MoneyDailyInput extends Command
{

    protected $signature = 'moneyDailyInput';

    protected $description = 'Command description';

    public function handle()
    {

$str = "
556,2025-07-08,12,0,1,1,8,8,1,6,3,7
557,2025-07-09,12,0,1,0,8,15,5,14,5,17
558,2025-07-10,12,0,1,0,8,15,5,14,5,17
559,2025-07-11,12,0,1,0,8,14,7,13,6,22
560,2025-07-12,12,0,1,0,8,5,6,3,6,25
561,2025-07-13,9,3,1,8,7,1,0,1,0,0
562,2025-07-14,9,3,1,7,8,4,2,9,2,10
563,2025-07-15,9,3,1,7,8,4,2,9,2,10
564,2025-07-16,9,3,1,7,8,4,2,9,2,10
";

$ex_str = explode("\n", $str);

foreach($ex_str as $v){
    if(trim($v) == ""){continue;}

    $ex_v = explode(",", trim($v));

    $ex_date = explode("-", trim($ex_v[1]));

    $input = [];
    $input['year'] = trim($ex_date[0]);
    $input['month'] = trim($ex_date[1]);
    $input['day'] = trim($ex_date[2]);

    $input['yen_10000'] = trim($ex_v[2]);
    $input['yen_5000'] = trim($ex_v[3]);
    $input['yen_2000'] = trim($ex_v[4]);
    $input['yen_1000'] = trim($ex_v[5]);
    $input['yen_500'] = trim($ex_v[6]);
    $input['yen_100'] = trim($ex_v[7]);
    $input['yen_50'] = trim($ex_v[8]);
    $input['yen_10'] = trim($ex_v[9]);
    $input['yen_5'] = trim($ex_v[10]);
    $input['yen_1'] = trim($ex_v[11]);

    $input['bank_a'] = "0";
    $input['bank_b'] = "0";
    $input['bank_c'] = "0";

    print_r($input);

    DB::table('t_money')->insert($input);
}

    }
}
