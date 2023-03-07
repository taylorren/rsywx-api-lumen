<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Laravel\Lumen\Http\Request;
use Illuminate\Support\Facades\DB;
use Unirest;

/**
 * This is the Controller for Book related operations
 */
class BlogController extends BaseController {

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Return latest $count of blogs
     * @param int $count Number of latest blogs to return 
     * @return JSON
     */
    public function latest($count = 1) {
        $sql = "select wp.post_date pd, wp.post_excerpt excerpt, wpm2.meta_value media, wp.guid link, wp.post_title title
from wp_postmeta wpm1, wp_postmeta wpm2, wp_posts wp
where wpm1.meta_value=wpm2.post_id
and wpm1.meta_key like '%thumb%'
and wpm2.meta_key like '%attached%'
and wp.ID=wpm1.post_id
order by wp.post_date desc
limit 0," . $count;
        $res=DB::connection('blog')->select($sql);
        
        return response()->json([
                    'data' => $res,
        ]);
    }

    private function _getMedia($fm) {
        $fm_uri = "https://blog.rsywx.net/wp-json/wp/v2/media/" . $fm;

        $media_query = Unirest\Request::get($fm_uri)->body;
        return $media_query->guid->rendered;
    }

    /**
     * Returns blogs for today in history
     * @return JSON
     */
    public function blogsToday() {
        $today = new \DateTime();
        $m = $today->format('m');
        $d = $today->format('d');
        $y = $today->format('Y');

        $sql = "
SELECT wpp.post_title title, wpm.meta_value pv, wpp.id link, year(wpp.post_date) year from wp_posts wpp, wp_postmeta wpm
where wpp.ID=wpm.post_id
and month(wpp.post_date)=?
and day(wpp.post_date)=?
and year(wpp.post_date)<>?
and wpm.meta_key='post_view'
and wpp.post_type='post'
and wpp.post_status='publish'
order by wpp.id
";
        $res = DB::connection('blog')->select($sql, [$m, $d, $y]);
        return response()->json([
                    'data' => $res,
        ]);
    }

}
