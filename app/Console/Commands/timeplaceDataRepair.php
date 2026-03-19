<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class timeplaceDataRepair extends Command
{

    protected $signature = 'timeplaceDataRepair';

    protected $description = 'Command description';

    public function handle(){

$result = DB::table('t_timeplace2')->orderBy('year')->orderBy('month')->orderBy('day')->orderBy('time')->get();

foreach($result as $v){
DB::table('t_timeplace')->where('year', '=', $v->year)->where('month', '=', $v->month)->where('day', '=', $v->day)->delete();
}

foreach($result as $v){
$insert = [
'year' => $v->year,
'month' => $v->month,
'day' => $v->day,
'time' => $v->time,
'place' => $v->place,
'price' => $v->price
];

print_r($insert);

DB::table('t_timeplace')->insert($insert);

}

    }
}
