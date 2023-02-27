<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Laravel\Lumen\Http\Request;

/**
 * This is the Controller for Book related operations
 */
class BlogController extends BaseController {

    private $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function latest($count = 1) {
        $uri = "https://blog.rsywx.net/wp-json/wp/v2/posts?per_page=$count";

        $out = json_decode(file_get_contents($uri));
        
        $res=[];
        foreach($out as $o){
            $title=$o->title->rendered;
            $excerpt=$o->excerpt->rendered;
            $link=$o->link;
            
            $fm=$o->featured_media;
            $fm_uri="https://blog.rsywx.net/wp-json/wp/v2/media/".$fm;
            
            $media_query= \json_decode(\file_get_contents($fm_uri));
            $media=$media_query->guid->rendered;
            
            $res[]=[
                'title'=>$title,
                'excerpt'=>$excerpt,
                'link'=>$link,
                'media'=>$media,
            ];
        }
        return response()->json([
                    'data' => ($res),
        ]);
    }
}
