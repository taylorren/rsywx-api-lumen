<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;
use Laravel\Lumen\Http\Request;

/**
 * This is the Controller for Book related operations
 */
class BookController extends BaseController {

    private $request;
    private $rpp;
    private $all_key;

    public function __construct(Request $request) {
        $this->request = $request;
        $this->rpp = env('RPP');

        $this->all_key = env('ALL_KEY');
    }

    /**
     * Retrieve the summary for book collection status.
     *
     * @return JSON
     */
    public function summary() {
        $result = DB::connection('rsywx')->select('select count(b.id) bc, sum(b.kword) wc, sum(b.page) pc from book_book b');
        $res = $result[0];
        $result2 = DB::connection('rsywx')->select('select count(vid) vc from book_visit');
        $res2 = $result2[0];
        $ret = [
            'bc' => $res->bc,
            'wc' => $res->wc,
            'pc' => $res->pc,
            'vc' => $res2->vc,
        ];
        return response()->json([
                    'data' => $ret,
        ]);
    }

    /**
     * Helper to generate a book image uri
     * @param String $id Book id
     * @param String $author Author
     * @param String $title Title
     * @param int $size Image width, default=600
     * @return String The image uri
     */
    private function _generateImgUri($id, $author, $title, $size = 600): string {
        return route('cover', [
            'bookid' => $id,
            'author' => $author,
            'title' => $title,
            'size' => $size,
        ]);
    }

    /**
     * Retrieve $count latest books
     * 
     * @param int $count Specifies the number of latest books to retrieve
     * @return JSON
     */
    public function latest(int $count = 1) {
        $result = DB::connection('rsywx')->select("select * from book_book order by id desc limit 0, $count");
        //var_dump($result[0]);die();
        foreach ($result as &$r) {
            $id = $r->bookid;
            $size = 600;
            $author = $r->author;
            $title = $r->title;

            $img = $this->_generateImgUri($id, $author, $title, $size);

            $r->img = $img;
        }
        return response()->json([
                    'data' => $result,
        ]);
    }

    /**
     * Retrieve book detail 
     * 
     * @param String $bookid The 5-digit bookid of the book to retrieve the details
     * @return JSON
     */
    public function detail(string $bookid) {
        $result = DB::connection('rsywx')->select("select b.*, pub.name pu_name, pl.name pu_place, count(v.vid) vc, max(v.visitwhen) lvt from book_book b, book_visit v, book_publisher pub, book_place pl where b.bookid=? and pub.id=b.publisher and pl.id=b.place and v.bookid=b.id group by v.bookid", [$bookid]);
        $r = $result[0];
        $r->img = $this->_generateImgUri($r->bookid, $r->author, $r->title, 600);
        $id = $r->id;
        $this->_updateVc($id);
        return response()->json([
                    'data' => $r,
        ]);
    }

    /**
     * Generate a cover image for a book
     * 
     * @param String $bookid The 5-digit bookid of the book to retrieve the details
     * @param int $size Image width
     * @param String $author Author of the book
     * @param String $title Title of the book
     * @return Image
     */
    public function cover($bookid, $size, $author, $title) {
        $id = $bookid;
        $width = $size;

        // Construct image file name based on 
        $path = 'covers/';
        $ext = '.jpg';
        $filename = $path . $id . $ext;

        $default = false;

        // Check if the file exists
        if (!file_exists($filename)) {
            $filename = $path . 'default' . $ext;
            $default = true;
        }

        list($w, $h) = getimagesize($filename);

        $nw = $width;
        $nh = (int) ($nw / $w * $h); // Propotionally change the width/height
        // Resize image

        $oimg = imagecreatefromjpeg($filename);
        $nimg = imagecreatetruecolor($nw, $nh);

        // Print copyright texts
        $copytext1 = "???????????????";
        $copytext2 = "????????????";
        $copytext3 = "1989-" . date("Y");

        $color = imagecolorallocate($oimg, 255, 255, 255);
        $color2 = imagecolorallocate($oimg, 0, 0, 0);
        $font = $path . 'msyh.ttf';

        imagettftext($oimg, 14, 0, 10, 26, $color, $font, $copytext1);
        imagettftext($oimg, 14, 0, 10, 48, $color, $font, $copytext2);
        imagettftext($oimg, 14, 0, 10, 70, $color, $font, $copytext3);
        if ($default) {
            //Print title
            imagettftext($oimg, 24, 0, 10, 240, $color2, $font, urldecode($title));
            // Print author
            imagettftext($oimg, 32, 0, 10, 480, $color, $font, urldecode($author));
        }
        imagecopyresized($nimg, $oimg, 0, 0, 0, 0, $nw, $nh, $w, $h);

        imagedestroy($oimg);

        //https://odan.github.io/2020/05/07/slim4-working-with-images.html#creating-images-using-the-gd-and-image-functions
        //Raised a question in Slim framework forum and got the above solution

        ob_start();
        imagepng($nimg);
        $data = ob_get_clean();
        imagedestroy($nimg);

        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $data);

        return response($data)
                        ->header('content-type', $mime);

        //$response = $this->response->withHeader('content-type', $mime);
        //return $response->withBody((new \Slim\Psr7\Factory\StreamFactory())->createStream($data));
    }

    /**
     * Randomly return one book from database
     * 
     * @param int $count Number of random books to return 
     * @return JSON
     */
    public function random(int $count = 1) {
        $results = DB::connection('rsywx')->select("select b.*, count(v.vid) vc, max(v.visitwhen) lvt from book_book b, book_visit v where b.id=v.bookid group by b.id order by rand() limit 0, $count");
        //$results=DB::select('select b.*, count(v.vid) vc, max(v.visitwhen) lvt from book_book b, book_visit v where b.id=v.bookid and b.id= 1585 group by b.id order by rand() limit 0, 1');
        foreach ($results as &$r) {
            $title = str_replace('?', '', $r->title);
            $r->img = $this->_generateImgUri($r->bookid, $r->author, $title, 600);
        }
        return response()->json([
                    'data' => $results,
        ]);
    }

    /**
     * Returns the count and list of books that were registered into database today in history
     * 
     * @param int $m Month
     * @param int $d Day
     * @return JSON
     */
    public function today($m, $d) {
        $countSql = 'select year(purchdate) y, count(bookid) bc from book_book'
                . ' where month(purchdate)=? and day(purchdate)=?'
                . ' group by month(purchdate), day(purchdate), year(purchdate)'
                . ' order by year(purchdate)';

        $results = DB::connection('rsywx')->select($countSql, [$m, $d]);

        $bookSql = 'select * from book_book '
                . 'where month(purchdate)=? '
                . 'and day(purchdate)=? '
                . 'and year(purchdate)=? '
                . 'order by year(purchdate) ';

        foreach ($results as &$r) {
            $y = $r->y;
            $bResults = DB::connection('rsywx')->select($bookSql, [$m, $d, $y]);

            $r->books = $bResults;
           
        }
        return response()->json([
                    'data' => $results,
        ]);
    }

    /**
     * Returns the tag list for a given book
     * @param int $id Book id 
     * @return JSON
     */
    public function tags($id) {
        $sql = 'select tag from book_taglist where bid=?';
        $results = DB::connection('rsywx')->select($sql, [$id]);

        $tags = [];
        foreach ($results as $r) {
            $t = $r->tag;
            $tags[] = $t;
        }

        return response()->json([
                    'data' => $tags,
        ]);
    }

    /**
     * Insert visit record for a book
     * @param int $id Book id
     * @return JSON
     */
    private function _updateVc($id) {
        $when = new \DateTime();

        $sql = 'insert into book_visit (bookid, visitwhen) values (?, ?)';
        $results = DB::connection('rsywx')->insert($sql, [$id, $when]);

        return response()->json([
                    'data' => $results,
        ]);
    }

    /**
     * Get reviews for a book
     * @param int $bid Bookid 
     * @return JSON
     */
    public function reviews($bid) {
        $sql = 'select r.* from book_review r, book_headline h where h.bid=? and h.hid =r.hid';

        $results = DB::connection('rsywx')->select($sql, [$bid]);
        return response()->json([
                    'data' => $results,
        ]);
    }

    /**
     * Insert a tag associated with a book
     * @param type $id
     * @param type $tag
     * @return bool True is insertion is success
     */
    public function addTag($id, $tag) {
        $sql = 'insert into book_taglist (bid, tag) values (?, ?)';

        $results = DB::connection('rsywx')->insert($sql, [$id, htmlspecialchars(urldecode($tag))]);

        return response()->json([
                    'data' => $results,
        ]);
    }

    public function list($type, $key, $page) {
        $res = match ($type) {
            'title' => $this->_searchByTitle($key, $page),
            'author' => $this->_searchByAuthor($key, $page),
            'tag' => $this->_searchByTag($key, $page),
            'misc' => $this->_searchByMisc($key, $page),
        };

        $books = $res[0];
        foreach ($books as &$b) {
            $b->img = $this->_generateImgUri($b->bookid, $b->author, $b->title);
        }

        return response()->json([
                    'data' => [
                        'books'=>$res[0], 
                        'pages'=>$res[1],
                    ],
        ]);
    }

    private function _searchByTitle($key, $page): array {
        $nkey = htmlspecialchars(urldecode($key));
        $resSql = "";
        $countSql = "";

        if ($key === $this->all_key) {
            $countSql = 'select count(id) as bc from book_book';
            $resSql = 'select * from book_book order by id desc';
        } else {
            $countSql = "select count(id) as bc from book_book where title like '%$nkey%'";
            $resSql = "select * from book_book where title like '%$nkey%' order by id desc";
        }

        [$res, $count] = $this->_executeSql($countSql, $resSql, $page);
        $bc = $count[0];
        $tpc = $this->_getTotalPages($bc->bc);
        return [$res, $tpc];
    }

    private function _getTotalPages($bc) {
        $rpp = $this->rpp;
        if ($bc % $rpp === 0) {
            return $bc / $rpp;
        } else {
            return ceil($bc / $rpp);
        }
    }

    private function _searchByAuthor($key, $page): array {
        $nkey = htmlspecialchars(urldecode($key));
        $resSql = "";
        $countSql = "";

        if ($key === $this->all_key) {
            $countSql = 'select count(id) as bc from book_book';
            $resSql = 'select * from book_book order by id desc';
        } else {
            $countSql = "select count(id) as bc from book_book where author like '%$nkey%'";
            $resSql = "select * from book_book where author like '%$nkey%' order by id desc";
        }

        [$res, $count] = $this->_executeSql($countSql, $resSql, $page);

        $bc = $count[0];
        $tpc = $this->_getTotalPages($bc->bc);
        return [$res, $tpc];
    }

    private function _searchByTag($key, $page): array {
        if ($key === $this->all_key) {
            return $this->_searchByTitle($key, $page);
        } else {
            return "Tag: $key+$page";
        }
    }

    private function _searchByMisc($key, $page): array {
        if ($key === $this->all_key) {
            return $this->_searchByTitle($key, $page);
        } else {
            return "Misc: $key+$page";
        }
    }

    private function _executeSql($countSql, $resSql, $page) {
        $rpp = $this->rpp;
        $resSql = $resSql . ' limit ' . ($page - 1) * $rpp . ', ' . $rpp;
        $books = DB::connection('rsywx')->select($resSql);
        $count = DB::connection('rsywx')->select($countSql);

        return [$books, $count];
    }
    
    public function hot($count=10) {
        $sql='select b.*, count(v.vid) vc from book_book b, book_visit v where v.bookid=b.id group by b.id order by vc desc '."limit 0, $count";
                
        $results=DB::connection('rsywx')->select($sql);
        return response()->json(['data'=>$results]);
        
        
    }

}
