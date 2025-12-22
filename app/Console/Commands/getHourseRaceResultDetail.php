<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;

class getHourseRaceResultDetail extends Command
{

    protected $signature = 'getHourseRaceResultDetail';

    protected $description = 'Command description';

    public function handle()
    {

        $result = DB::table('t_hourse_race_list')->where('finish', '=', 0)->get();

        foreach($result as $k=>$v){

//if($k>0){break;}

            $content = file_get_contents($v->result_url);
            $ex_content = explode("\n", $content);

            $str = "";
            foreach($ex_content as $v2){
                $str .= trim(mb_convert_encoding(strip_tags($v2, "<tbody><th><td>"), 'UTF-8', 'SJIS'));
            }

            $ex_str = explode("|", strtr($str, ["><"=>">|<"]));

            $a = [];
            $b = [];
            foreach($ex_str as $k2=>$v2){
                if(preg_match("/単勝人気/", trim($v2))){$a[] = $k2;}
                if(preg_match("/ハロンタイム/", trim($v2))){$b[] = $k2;}
            }





            if(count($a) == 1 && count($b) == 1){

                $result = [];
                $hourse_name = [];
                $age = [];
                $jockey_name = [];
                $race_time = [];

                for ($j=$a[0]; $j<$b[0]; $j++){

                    if(preg_match("/class=\"place\"/", trim($ex_str[$j]))){
                        $result_ = trim(strip_tags($ex_str[$j]));
                        $result[] = $result_;
                    }

                    if(preg_match("/class=\"horse\"/", trim($ex_str[$j]))){
                        $horse_name_ = trim(strip_tags($ex_str[$j]));
                        $hourse_name[] = $horse_name_;
                    }

                    if(preg_match("/class=\"age\"/", trim($ex_str[$j]))){
                        $age_ = trim(strip_tags($ex_str[$j]));
                        $age[] = $age_;
                    }

                    if(preg_match("/class=\"jockey\"/", trim($ex_str[$j]))){
                        $jockey_ = trim(strip_tags($ex_str[$j]));
                        $jockey_name[] = $jockey_;
                    }

                    if(preg_match("/class=\"time\"/", trim($ex_str[$j]))){
                        $time_ = trim(strip_tags($ex_str[$j]));
                        $race_time[] = $time_;
                    }
                }

                for($j=0; $j<count($result); $j++){
                    if($result[$j] != "中止" && $result[$j] != "取消" && $result[$j] != "除外"){

                        $insert = [
                            'year' => $v->year,
                            'month' => $v->month,
                            'day' => $v->day,
                            'grade' => $v->grade,
                            'race_name' => $v->race_name,

                            'result' => $result[$j],
                            'hourse_name' => $hourse_name[$j],
                            'age' => $age[$j],
                            'jockey_name' => $jockey_name[$j],
                            'race_time' => $race_time[$j]
                        ];

                        print_r($insert);

                        DB::table('t_hourse_race_result')->insert($insert);

                        $update = ['finish' => 1];
                        DB::table('t_hourse_race_list')->where('id', '=', $v->id)->update($update);
                    }
                }




            }










        }
    }
}
