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
        $uri = "https://blog.rsywx.net/wp-json/wp/v2/posts?per_page=$count";

        $out = Unirest\Request::get($uri)->body;
        
        $res = [];
        foreach ($out as $o) {
            
            $title = $o->title->rendered;
            $excerpt = $o->excerpt->rendered;
            $link = $o->link;

            $fm = $o->featured_media;
            $media=$this->_getMedia($fm);

            $res[] = [
                'title' => $title,
                'excerpt' => $excerpt,
                'link' => $link,
                'media' => $media,
            ];
        }
        return response()->json([
                    'data' => ($res),
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
    public function blogsToday(){
        $today = new \DateTime();
        $m = $today->format('m');
        $d = $today->format('d');
        $y = $today->format('Y');
        
        $sql = "select p.ID from wp_posts p where p.post_status='publish' and p.post_type='post' and month(p.post_date) = ? and day(p.post_date)=? and year(p.post_date)<>?";
        $res1=DB::connection('blog')->select($sql, [$m, $d, $y]);
        
        $res=[];
        
        foreach ($res1 as &$blog)
        {
            $id=$blog->ID;
            $uri="https://blog.rsywx.net/wp-json/wp/v2/posts/$id";
            
            $blog= Unirest\Request::get($uri)->body;
            
            $viewSql="select meta_value pv from wp_postmeta where post_id=? and meta_key='post_view'";
            $res2=DB::connection('blog')->select($viewSql, [$id]);
            
            $vc=$res2[0]->pv;
            $title=$blog->title->rendered;
            $link=$blog->link;
            $year=$blog->date;
            
            $res[]=[
                'title'=>$title,
                'link'=>$link,
                'vc'=>$vc,
                'year'=> substr($year,0,4),
            ];
        }
        return $res;
    }
}
