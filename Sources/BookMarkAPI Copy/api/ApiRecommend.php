<?php

require_once ("Api.php") ;

class ApiRecommend extends Api
{
    public function __construct ($url, $method) {
        self::$_columns = ['isbn13', 'book_id'];
        self::$_join = [
            [
                'type' => 'right',
                'table' => 'book',
                'onT1' => 'book.isbn13',
                'onT2' => 'book_in_shelf.book_id'
            ]
        ];
        self::$_order = ['book_id desc'] ;
        $list = $this->get("book_in_shelf", self::$_columns) ;

        $recommended = [] ;
        $i = 0;
        $j = 0 ;
        while ($i < count($list) && $j < 10) {
            if ($list[$i]['book_id'] == null) {
                $recommended[] = $list[$i]['isbn13'];
                $j++ ;
            }
            $i++;
        }

//        $list = [
//            "1569871213",
//            "1575663937",
//            "1841721522",
//            "1853260053",
//            "1853262404",
//            "1881320189",
//            "2070423204",
//            "3404921038",
//            "3442353866",
//            "3442410665",
//            "3442446937"
//          ];
        echo json_encode($recommended);
    }
}