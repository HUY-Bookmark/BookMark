<?php

require_once("Api.php") ;
ini_set("display_errors", 1) ;

class ApiBook extends Api
{
    private $_response ;
    private $_book_join = [
        [
            'type' => 'left',
            'table' => 'genre',
            'onT1' => 'genre.id',
            'onT2' => 'book.genre_id'
        ],
        [
            'type' => 'left',
            'table' => 'book_author',
            'onT1' => 'book_author.book_id',
            'onT2' => 'book.isbn13'
        ],
        [
            'type' => 'inner',
            'table' => 'author',
            'onT1' => 'book_author.author_id',
            'onT2' => 'author.id'
        ]
    ];

    public function __construct ($url, $method, $innerCall = false)
    {
        switch (strtolower($method)) {
            case "get":
                $this->_response = $this->getBook($url) ;
                break ;
            case "post":
                if (isset($url[0]) && $url[0] === "multiple")
                    $this->_response = $this->addMultipleBooks() ;
                else
                    $this->_response = $this->addBook() ;
                break ;
            default:
                $this->_response = $this->errorResponse("METHOD NOT ALLOWED") ;
                http_response_code(405) ;
        }
        if ($innerCall) return $this->_response ;

        echo json_encode($this->_response) ;
    }

    public function getBook ($url) {
        $columns = ["isbn13", "isbn11", "series_name", "series_position", "book_name", "pages", "genre.genre"] ;
        if (count($url) > 1 && $url[0] === 'search') {
            $searchwords = array_slice($url,1,count($url) - 1);
            return $this->searchBook($searchwords, $columns);
        }

        $id = $url[1] ;
        self::$_where = ["isbn13 = ? OR isbn11 LIKE ?"] ;
        self::$_params = [$id, "%$id%"] ;

        self::$_join = $this->_book_join ;

        $book = $this->get("book", $columns) ;
        if (count($book) > 0) {
            $book = $this->okResponse(array_merge(['id' => 1], $this->formatBookValues($book[0]))) ;
            $book["authors"] = $this->getAuthors($book['isbn13']) ;
            return [$book] ;
        }
        http_response_code(404) ;
        return $this->errorResponse('NOT FOUND') ;
    }

    public function searchBook ($searchwords, $columns) {
        $books = [] ;
        foreach ($searchwords as $i => $search) {
            self::$_where = [
                "(series_name LIKE ? OR book_name LIKE ? OR author.name LIKE ?)"
            ];
            self::$_params = ["%$search%", "%$search%", "%$search%"];
            self::$_join = $this->_book_join;

            $books = array_merge($books, $this->get("book", $columns));
            $this->resetParams();
        }
        if (count($books) > 0) {
            $list = [] ;
            foreach ($books as $position => $b) {
                if (in_array($b['isbn13'], $list)) {
                    unset($books[$position]) ;
                } else {
                    $list[] = $b['isbn13'] ;
                    $books[$position]['authors'] = $this->getAuthors($b['isbn13']) ;
                    $books[$position] = $this->okResponse(array_merge(['id' => $position + 1], $this->formatBookValues($books[$position]))) ;
                }
            }
            return $books;
        } //else search internet ?

        http_response_code(404) ;
        return "NO RESULTS" ;
    }

    private function formatBookValues ($book) {
        $book['series_position'] = intval($book['series_position']);
        $book['pages'] = intval($book['pages']);
        if ($book['genre'] == null) $book['genre'] = "";
        if ($book['isbn11'] == null) $book['isbn11'] = "";
        if ($book['series_name'] == null) $book['series_name'] = "";
        return $book ;
    }

    private function addMultipleBooks () {
        $books = $this->getJsonArray();
        $res = [] ;
        $errors = [] ;
        if ($books != null) {
            foreach ($books as $b) {
                try {
                    $insert = $this->addBook($b);
                    if (isset($insert['response']) && $insert['response'] == "error") {
                        $errors[] = $insert['error'];
                    } else {
                        $res[] = $b['isbn13'];
                    }
                } catch (Exception $e) {
                    $errors[] = $insert['error'];
                }
            }
            if (count($res) == 0) {
                http_response_code(400);
                return "BAD REQUEST";
            }
        }
        return ['ok' => $res, 'errors' => $errors] ;
    }

    private function addBook ($book = null) {
        if ($book == null) {
            $book = $this->getJsonArray();
        }

        if (!isset($book['isbn13'], $book['book_name'], $book['authors']) && !empty($book['authors'])) {
            http_response_code(412) ;
            return $this->errorResponse("MISSING PARAMETERS") ;
        }

        self::$_columns = ['isbn13', 'book_name'] ;
        self::$_params = [$book['isbn13'], $book['book_name']] ;
        $this->additionalData($book, ['series_name', 'series_position', 'date_published', 'pages']) ; ;

        $this->add('book') ;
        $this->addBookAuthors($book['authors'], $book['isbn13']) ;

        if (isset($book['cover'])) $this->addBookCover($book['isbn13'], $book['cover']) ;

        return [$book['isbn13']];
    }

    private function additionalData ($data, $values) {
        foreach ($values as $v) {
            if (isset($data[$v])) {
                self::$_columns[] = $v ;
                self::$_params[] = $data[$v] ;
            }
        }
    }

    private function addBookAuthors ($authors, $isbn) {
        foreach ($authors as $a) {
            $this->resetParams() ;
            $columns = ['id'] ;
            self::$_where = ['name = ?'] ;
            self::$_params = [$a] ;
            $author = $this->get('author', $columns) ;

            if (count($author) < 1) {
                $this->resetParams() ;
                self::$_columns = ['name'] ;
                self::$_params = [$a] ;
                $author = $this->add('author') ;
            } else {
                $author = $author[0]['id'] ;
            }

            $this->resetParams();
            self::$_columns = ['book_id', 'author_id'] ;
            self::$_params = [$isbn, $author] ;
            $this->add('book_author') ;
        }
    }

    private function addBookCover ($isbn, $coverPath) {
        $this->resetParams() ;
        self::$_columns = ['image_path', 'book_id'] ;
        self::$_params = [$coverPath, $isbn] ;
        $this->add('cover') ;
    }

    public function getAuthors ($bookid) {
        $this->resetParams() ;
        $columns = ['author.name'] ;

        self::$_where = ['book_id = ?'] ;
        self::$_params = [$bookid] ;
        self::$_join = [
            [
                'type' => 'inner',
                'table' => 'author',
                'onT1' => 'book_author.author_id',
                'onT2' => 'author.id'
            ]
        ] ;

        $auth = $this->get("book_author", $columns) ;
        $authors = [];
        if ($auth != null) {
            foreach ($auth as $a) {
                $authors[] = $a['name'] ;
            }
        } else $authors[] = "" ;
        
        return $authors ;
    }
}