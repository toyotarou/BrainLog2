<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Carbon\Carbon;
use DB;
class LeoFortuneGet extends Command
{
    protected $signature = 'LeoFortuneGet';
    protected $description = 'LeoFortuneGet';
    public function __construct()
    {
        parent::__construct();
    }
    public function handle()
    {
        $url = "https://sp.m.jiji.com/horoscope/show/leo";

        $guzzle = new \GuzzleHttp\Client(['verify' => false]);
        $client = new \Goutte\Client();
        $client->setClient($guzzle);

        $crawler = $client->request('GET', $url);

        $rankText = $crawler->filter('.horoscopeDetailRank__number span')->text();
        $daily_ranking = trim($rankText);

        $love  = trim($crawler->filter('.horoscopeDetail__item--love .horoscopeDetail__body')->text());
        $money = trim($crawler->filter('.horoscopeDetail__item--money .horoscopeDetail__body')->text());
        $work  = trim($crawler->filter('.horoscopeDetail__item--work .horoscopeDetail__body')->text());

        $insert = [
            'year'  => date("Y"),
            'month' => date("m"),
            'day'   => date("d"),
            'rank'  => $daily_ranking,
            'love'  => $love,
            'money' => $money,
            'work'  => $work,
        ];
        print_r($insert);
        $file = "/var/www/html/BrainLog/public/mySetting/leofortune.data";
        $fp = fopen($file, "a+");
        fwrite($fp, mb_convert_encoding(implode("|", $insert) . "|", "utf-8") . "\n");
        fclose($fp);
    }
}
