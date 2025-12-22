<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class getHourseRaceResult extends Command
{

    protected $signature = 'getHourseRaceResult';

    protected $description = 'Command description';

    public function handle()
    {

        for($year=2020; $year<=date('Y'); $year++){

//            if($year>2020){break;}

            $url = "https://www.jra.go.jp/datafile/seiseki/replay/" . $year . "/jyusyo.html";
            $content = file_get_contents($url);

            $a = [];
            $b = [];
            $ex_content = explode("\n", $content);
            foreach($ex_content as $k=>$v){
                if(preg_match("/<table/", trim($v))){$a[] = $k;}
                if(preg_match("/<\/table>/", trim($v))){$b[] = $k;}
            }

            $month = [];
            $day = [];
            $grade = [];
            $race_name = [];
            $place = [];
            $age_rate = [];
            $course_kind = [];
            $course_length = [];
            $result_url = [];
            for ($j=$a[0]; $j<$b[0]; $j++){

                if(preg_match("/class=\"date\"/", trim($ex_content[$j]))){
                    $date = mb_convert_encoding(trim(strip_tags($ex_content[$j])), 'UTF-8', 'SJIS');
                    preg_match("/([0-9]+)月([0-9]+)日/", $date, $m);
                    $month[] = $m[1];
                    $day[] = $m[2];
                }

                if(preg_match("/class=\"race\"/", trim($ex_content[$j]))){
                    $race = mb_convert_encoding($ex_content[$j], 'UTF-8', 'SJIS');
                    $ex_race = explode('"', $race);
                    foreach($ex_race as $v2){
                        if(preg_match("/grade_icon/", trim($v2))){
                            $grade[] = trim(strtr($v2, ["grade_icon"=>""]));
                        }
                    }

                    $racename = strip_tags($race);
                    $ex_racename = explode(";", $racename);
                    $race_name[] = trim((count($ex_racename)>1) ? $ex_racename[1] : $ex_racename[0]);
                }

                if(preg_match("/class=\"place\"/", trim($ex_content[$j]))){
                    $place_ = mb_convert_encoding($ex_content[$j], 'UTF-8', 'SJIS');
                    $place[] = trim(strip_tags($place_));
                }

                if(preg_match("/class=\"age\"/", trim($ex_content[$j]))){
                    $age_ = mb_convert_encoding($ex_content[$j], 'UTF-8', 'SJIS');
                    $age_rate[] = trim(strip_tags($age_));
                }

                if(preg_match("/class=\"course\"/", trim($ex_content[$j]))){
                    $course_ = mb_convert_encoding($ex_content[$j], 'UTF-8', 'SJIS');
                    $course__ = trim(strip_tags(strtr($course_, [","=>"", " "=>""])));
                    preg_match('/^(\D+)(\d+)/u', $course__, $m);
                    $course_kind[] = trim($m[1]);
                    $course_length[] = trim($m[2]);
                }

                if(preg_match("/class=\"result\"/", trim($ex_content[$j]))){
                    $result_ = mb_convert_encoding($ex_content[$j], 'UTF-8', 'SJIS');
                    preg_match("/datafile(.+)html/", $result_, $m);
                    $result_url[] = (isset($m[1]))?"https://www.jra.go.jp/datafile" . trim($m[1]) . "html":"skip";
                }
            }

            for($j=0; $j<count($month); $j++){
                if($result_url[$j] != "skip"){

                    $answer = DB::table('t_hourse_race_list')
                    ->where('year', '=', $year)
                    ->where('month', '=', sprintf("%02d", $month[$j]))
                    ->where('day', '=', sprintf("%02d", $day[$j]))
                    ->where('race_name', '=', $race_name[$j])
                    ->first();

                    if(!isset($answer)){

                        $insert = [
                            'year'=>$year,
                            'month'=>sprintf("%02d", $month[$j]),
                            'day'=>sprintf("%02d", $day[$j]),
                            'grade'=>$grade[$j],
                            'race_name'=>$race_name[$j],
                            'place'=>$place[$j],
                            'age_rate'=>$age_rate[$j],
                            'course_kind'=>$course_kind[$j],
                            'course_length'=>$course_length[$j],
                            'result_url'=>$result_url[$j],
                            'finish'=>0
                        ];

                        print_r($insert);

                        DB::table('t_hourse_race_list')->insert($insert);
                    }
                }
            }
        }

    }
}
