<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;
use Unirest;

/**
 * This is the Controller for Book related operations
 */
class AdminController extends BaseController {

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }
    /**
     * Returns visit count in the past days
     * @param int $day
     * @return JSON
     */
    public function visit($day=14) {
        $sql="SELECT count(vid) vc, date(visitwhen) vd
FROM book_visit 
where date(visitwhen) >=date_sub(now(), interval $day day)
group by vd
order by vd desc ";
    
    $res=DB::connection('rsywx')->select($sql);
        
    return response()->json([
            'data'=>$res,
        ]
    );
    }
        
    
}
