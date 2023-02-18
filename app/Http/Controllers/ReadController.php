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
        $sql='select h.*, r.*, b.title as book from book_headline as h, book_review as r, book_book as b where r.hid=h.hid and h.bid=b.id  order by r.datein desc limit 0,  '.$count;
        $results=DB::connection('rsywx')->select($sql);
        
        return response()->json([
                    'data' => $results,
        ]);
    }
    
    public function summary() {
        $sql='select count(r.id) rc, count(distinct(h.hid)) hc from book_review r, book_headline h where r.hid=h.hid';
        $results=DB::connection('rsywx')->select($sql);
        
        return response()->json([
                    'data' => $results,
        ]);
    }
}
