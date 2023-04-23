<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;

/**
 * This is the Controller for Book related operations
 */
class ReadController extends BaseController {

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    /**
     * Return latest book reviews
     * @param int $count
     * @return JSON
     */
    public function latest($count) {
        $sql = 'select h.*, r.*, b.title as book from book_headline as h, book_review as r, book_book as b where r.hid=h.hid and h.bid=b.id  order by r.datein desc limit 0,  ' . $count;
        $results = DB::connection('rsywx')->select($sql);

        return response()->json([
                    'data' => $results,
        ]);
    }

    /**
     * Return reading summary
     * @return JSON
     */
    public function summary() {
        $sql = 'select count(r.id) rc, count(distinct(h.hid)) hc from book_review r, book_headline h where r.hid=h.hid';
        $results = DB::connection('rsywx')->select($sql);

        return response()->json([
                    'data' => $results,
        ]);
    }

    /**
     * Return a list of readings
     * @param int $page
     * @return JSON
     */
    public function list($page) {
        $countSql = 'select count(*) rc from book_review';
        $countRes = DB::connection('rsywx')->select($countSql);
        $count = $countRes[0]->rc;

        $hpp = env('HPP');
        $total = 0;
        if ($count / $hpp != ceil($count / $hpp)) {
            $total = ceil($count / $hpp);
        } else {
            $total = $count / $hpp;
        }
        $start = ($page - 1) * $hpp;
        
        $sql = "select r.*, h.reviewtitle, h.create_at, b.title booktitle, b.bookid, b.author from book_book b, book_headline h, book_review r where h.bid=b.id and h.hid=r.hid order by r.id desc limit $start, $hpp";
        
        $res=DB::connection('rsywx')->select($sql);
        foreach($res as &$r){
            $r->img="/books/image/{$r->bookid}/{$r->author}/{$r->booktitle}/300";
        }
        return response()->json([
                    'data' => [
                        'pages'=>$total, 
                        'reviews'=>$res,
                ],
        ]);
    }

}
