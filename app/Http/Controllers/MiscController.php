<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;

/**
 * This is the Controller for Book related operations
 */
class MiscController extends BaseController {

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Returns a random quote
     * @return JSON
     */
    public function qotd() {
        $sql = 'select * from qotd order by rand() limit 0, 1';
        $result = DB::connection('rsywx')->select($sql);

        return response()->json([
                    'data' => $result,
        ]);
    }

    public function weather() {
        $city = "CN101190401"; // CN101190401 is the code for Suzhou, Jiangsu, China
        $key = "df35dd302a1949faac063b497caf8dfc";
        $uri1 = "https://free-api.heweather.net/s6/weather/now?location=$city&key=$key";
        $res1 = json_decode(file_get_contents($uri1));
        //$res1 = Request::get($uri)->body->HeWeather6[0];

        $uri2 = "https://free-api.heweather.net/s6/weather/lifestyle?key=$key&location=$city";
        $res2 = json_decode(file_get_contents($uri2));

        $res = ['weather' => $res1, 'lifestyle' => $res2];

        return response()->json([
                    'data' => $res,
        ]);
    }

    /**
     * Returns Lakers score summary in a season 
     * @return JSON
     */
    public function lakers() {
        $year = env('SEASON');

        $sql = "SELECT count(id) winlose FROM rsywx.lakers where year=? and id>0 and winlose = 'W'
            union
            SELECT count(id) winlose FROM rsywx.lakers where year=? and id>0 and winlose = 'L'
            ";

        $result = DB::connection('rsywx')->select($sql, [$year, $year]);
        $win = $result[0]->winlose;
        $lose = $result[1]->winlose;
        $per=0;
        if($win+$lose<>0){
            $per= number_format($win/($win+$lose)*100, 1);
        }
        $res = [
            'win' => $win,
            'lose' => $lose,
            'per'=>$per,
            'year'=>$year,
        ];
        return response()->json([
                    'data' => $res,
                        ]
        );
    }

}
