<?php

namespace \Tests;
use App\Http\Controllers\BookController;
use PHPUnit\Framework\TestCase;

class BookListTest extends TestCase
{
    private $request;
    function __construct(Request $r) {
        
        $this->request=$r;
}
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_total_page_count()
    {
        $c=new BookController($this->request);
        
        $this->assertEquals(1, $c->_getTotalPages(4));
    }
}
