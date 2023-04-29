<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;
use Unirest;

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

    /**
     * Returns the current weather in Suzhou
     * @return JSON
     */
    public function weather() {
        $city = "CN101190401"; // CN101190401 is the code for Suzhou, Jiangsu, China
        //$key = "df35dd302a1949faac063b497caf8dfc";
        $key = "c4ac49d603e04dbc876470cd617cd80b";
        //https://devapi.qweather.com/v7/weather/now?location=CN101190401&key=df35dd302a1949faac063b497caf8dfc
        //Note on Apr 1st 2023: API interface seems changed
        //$uri1 = "https://free-api.heweather.net/s6/weather/now?location=$city&key=$key";
        $uri1 = "https://devapi.qweather.com/v7/weather/now?location=$city&key=$key";
        $body1 = Unirest\Request::get($uri1)->body;
        if ($body1->code == 200) {
            $res1 = $body1->now;
        } else {
            $res1 = "Error";
        }
        /*
          全部天气指数	0	INDICES_TYPE_all	ALL
          运动指数	1	INDICES_TYPE_spt	SPT
          洗车指数	2	INDICES_TYPE_cw	CW
          穿衣指数	3	INDICES_TYPE_drsg	DRSG
          钓鱼指数	4	INDICES_TYPE_fis	FIS
          紫外线指数	5	INDICES_TYPE_uv	UV
          旅游指数	6	INDICES_TYPE_tra	TRA
          花粉过敏指数	7	INDICES_TYPE_ag	AG
          舒适度指数	8	INDICES_TYPE_comf	COMF
          感冒指数	9	INDICES_TYPE_flu	FLU
          空气污染扩散条件指数	10	INDICES_TYPE_ap	AP
          空调开启指数	11	INDICES_TYPE_ac	AC
          太阳镜指数	12	INDICES_TYPE_gl	GL
          化妆指数	13	INDICES_TYPE_mu	MU
          晾晒指数	14	INDICES_TYPE_dc	DC
          交通指数	15	INDICES_TYPE_ptfc	PTFC
          防晒指数	16	INDICES_TYPE_spi	SPI
         */

        $uri2 = "https://devapi.qweather.com/v7/indices/1d?key=$key&location=$city&type=0";
        $body2 = Unirest\Request::get($uri2)->body;

        if ($body2->code == 200) {
            $res2 = $body2->daily;
        } else {
            $res2 = "Error";
        }

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

        $sql = "SELECT count(id) winlose FROM rsywx.lakers where year=? and id>0 and winlose = 'W' union SELECT count(id) winlose FROM rsywx.lakers where year=? and id>0 and winlose = 'L'";

        $result = DB::connection('rsywx')->select($sql, [$year, $year]);
        $win = $result[0]->winlose;
        $lose = $result[1]->winlose;
        $per = 0;
        if ($win + $lose <> 0) {
            $per = number_format($win / ($win + $lose) * 100, 1);
        }
        $res = [
            'win' => $win,
            'lose' => $lose,
            'per' => $per,
            'year' => $year,
        ];
        return response()->json([
                    'data' => $res,
                        ]
        );
    }

    public function season() {
        $interval = 28*4;
        $sql = "SELECT * FROM rsywx.lakers
where dateplayed between date_sub(now(), interval $interval day) and now()
order by dateplayed";
        $res=DB::connection('rsywx')->select($sql);
        return response()->json([
            'data'=>$res,
        ]);
    }
}