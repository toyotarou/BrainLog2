<?php

namespace App\Console\Commands;

use DB;
use Illuminate\Console\Command;

class getPrefTrain extends Command
{

    protected $signature = 'getPrefTrain';

    protected $description = 'Command description';

    public function handle()
    {

        try {

$str = "
北海道
青森県
岩手県
宮城県
秋田県
山形県
福島県
茨城県
栃木県
群馬県
埼玉県
千葉県
東京都
神奈川県
新潟県
富山県
石川県
福井県
山梨県
長野県
岐阜県
静岡県
愛知県
三重県
滋賀県
京都府
大阪府
兵庫県
奈良県
和歌山県
鳥取県
島根県
岡山県
広島県
山口県
徳島県
香川県
愛媛県
高知県
福岡県
佐賀県
長崎県
熊本県
大分県
宮崎県
鹿児島県
沖縄県
";

            $ex_str = explode("\n", $str);

            foreach($ex_str as $k=>$v){

                if(trim($v) == ""){continue;}

//if($k>2){break;}

                $searchPref = trim($v);

                $url = "https://express.heartrails.com/api/json?method=getLines&prefecture={$searchPref}";
                $content = file_get_contents($url);
                $jsonStr = json_decode($content);
                $response = $jsonStr->response;
                $lineList = $jsonStr->response->line;

                foreach($lineList as $v2){
                    $insert = [];

                    $insert['pref'] = trim($v);
                    $insert['train'] = trim($v2);
                    $insert['finish'] = 0;

print_r($insert);
echo "\n";echo "\n";echo "\n";

                    DB::table('t_pref_train')->insert($insert);
                }

            }

        } catch (Exception $e) {
        }

    }
}
