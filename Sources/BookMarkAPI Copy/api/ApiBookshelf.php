<?php

require_once ("Api.php") ;
require_once ("ApiBook.php") ;

class ApiBookshelf extends Api
{
    private $_user ;
    private $_response ;
    private $_avgBookWidth = 3;

    public function __construct($url, $method)
    {
        switch (strtolower($method)) {
            case "patch":
                $this->_response[] = $this->sortShelf($url) ;
                break ;
            case "post":
                $this->_response[] = $this->getUserShelf() ;
                break ;
            case "put":
                $this->_response[] = $this->addBookToShelf();
                break ;
            default:
                $this->_response[] = ["METHOD NOT ALLOWED"] ;
                http_response_code(405) ;
        }
        echo json_encode($this->_response) ;
    }

    private function getUserShelf () {
        $this->resetParams();
        $user = $this->getJsonArray() ;

        $columns = ["bookshelf.id as id", "width", "height", "depth", "shelves_number", "sorting"] ;

        self::$_join = [
            [
                'type' => 'inner',
                'table' => 'user',
                'onT1' => 'user.bookshelf_id',
                'onT2' => 'bookshelf.id'
            ]
        ] ;

        self::$_where = ["user.token = ?", "user.id = ?"] ;
        self::$_params = [$user['tok'], $user['usr']] ;

        $bookshelf = $this->get("bookshelf", $columns) ;
        if (count($bookshelf) == 1) {
            $bookshelf = $bookshelf[0] ;
            $bookshelf["shelves"] = $this->getShelving($bookshelf['id']) ;
            return $this->formatBookshelfValues($bookshelf);
        }

        http_response_code(404) ;
        return "NOT FOUND";
    }

    private function getShelving ($bookshelf_id)
    {
        $this->resetParams();
        $columns = ["book_id", "shelf_number", "shelf_position"];

        self::$_where = ["bookshelf_id = ?"];
        self::$_params = [$bookshelf_id];
        self::$_order = ['shelf_number', 'shelf_position'];

        return $this->get("book_in_shelf", $columns);
    }

    private function formatBookshelfValues ($bookshelf) {
        $bookshelf['id'] = intval($bookshelf['id']) ;
        $bookshelf['width'] = intval($bookshelf['width']) ;
        $bookshelf['height'] = intval($bookshelf['height']) ;
        $bookshelf['depth'] = intval($bookshelf['depth']) ;
        $bookshelf['shelves_number'] = intval($bookshelf['shelves_number']) ;
        if ($bookshelf['sorting'] == null) $bookshelf['sorting'] = "" ;

        if (isset($bookshelf['shelves'])) {
            foreach ($bookshelf['shelves'] as $sid => $s) {
                $bookshelf['shelves'][$sid]['shelf_number'] = intval($s['shelf_number']);
                $bookshelf['shelves'][$sid]['shelf_position'] = intval($s['shelf_position']);
            }
        }
        return $bookshelf ;
    }

    private function addBookToShelf () {
        //add check to verify user is allowed to insert in that shelf

        $data = $this->getJsonArray() ;

        //find correct place in sorting to replace $data["shelf"] and $data["position"]
        $bookshelf = $this->getUserShelf() ;
        if ($bookshelf == null) {
            http_response_code(403) ;
            return [] ;
        }

        $this->resetParams() ;
        self::$_columns = ['book_id', 'bookshelf_id', 'shelf_number', 'shelf_position'] ;
        self::$_params = [$data['book'], $bookshelf['id'], 0, 0] ;
        $this->add("book_in_shelf");
        return $this->sortShelfProcess($bookshelf, $bookshelf['sorting']) ;
    }

    private function sortShelf ($url) {
        $userData = $this->getJsonArray() ;
        if (count($url) < 1 || !isset($userData['usr'], $userData['tok'])) {
            http_response_code(412) ;
            return $this->errorResponse("MISSING DATA") ;
        }
        $sortBy = $url[0];

        if (!$this->checkAllowed([$sortBy], ['author', 'title', 'genre', 'authord', 'titled', 'genred', 'none'])) {
            http_response_code(405) ;
            return $this->errorResponse("METHOD NOT ALLOWED") ;
        }

        $bookshelf = $this->getUserShelf() ;
        if ($bookshelf == "NOT FOUND" ) {//|| $bookshelf['sorting'] == $sortBy
            return $bookshelf ;
        }

        return $this->sortShelfProcess($bookshelf, $sortBy) ;
    }

    private function sortShelfProcess ($bookshelf, $sortBy) {
        $this->resetParams() ;
        $apiBook = new ApiBook(['id', ""], 'get', true);
        $books = [];
        foreach ($bookshelf['shelves'] as $book) {
            $apiBook->resetParams() ;
            $books[] = $apiBook->getBook(['id', $book['book_id']]) ;
        }
        unset($bookshelf['shelves']) ;

        $bookshelf['shelves'] = $this->computeSorting($bookshelf, $books, $sortBy);
        $bookshelf['sorting'] = $sortBy ;

        $this->updateSorting($bookshelf['id'], $bookshelf['shelves'], $sortBy) ;
        return $bookshelf ;
    }

    private function computeSorting (array $bookshelfData, array $books, string $sortBy) {
        $booksByShelf = intval($bookshelfData['width'] / $this->_avgBookWidth) - 1 ;

        $sorted = [] ;
        switch ($sortBy) {
            case "title":
                $sorted = $this->sortByTitle($books) ;
                break ;
            case "author":
                $sorted = $this->sortByAuthor($books) ;
                break ;
            case "titled":
                $sorted = array_reverse($this->sortByTitle($books)) ;
                break ;
            case "authord":
                $sorted = array_reverse($this->sortByAuthor($books)) ;
                break ;
        }

        $shelf = 0;
        $shelfPosition = 0;
        foreach ($sorted as $id => $book) {
            $sorted[$id] = [
                'book_id' => $book['book_id'],
                'shelf_number' => $shelf,
                'shelf_position' => $shelfPosition
            ];
            $shelfPosition++ ;
            if ($shelfPosition > $booksByShelf) {
                $shelf++ ;
                $shelfPosition = 0 ;
            }
        }

        return $sorted ;
    }

    private function sortByAuthor ($books) {
        $sorted = [] ;
        $toSort = [] ;
        $toSortReversed = [] ;

        foreach ($books as $b) {
            $b = $b[0] ;
            $author = $b['authors'][0] ?? "" ;
            $toSort[] = $author ;
            $toSortReversed[$author][] = $b['isbn13'] ;
        }

        sort($toSort, SORT_NATURAL | SORT_FLAG_CASE) ;

        foreach ($toSort as $author) {
            foreach ($toSortReversed[$author] as $isbn);
            $sorted[] = ['book_id' => $isbn] ;
        }
        return $sorted ;
    }

    private function sortByTitle ($books) {
        $sorted = [] ;
        $toSort = [];
        $toSortReversed = [] ;

        foreach ($books as $b) {
            $b = $b[0] ;
            $title = $b['book_name'] ;
            if ($b['series_name'] !== null) {
                if ($b['series_position'] !== null) {
                    $title = $b['series_name'] . ", " . "Book " . $b['series_position'] . ": " . $b['book_name'] ;
                } else {
                    $title = $b['series_name'] . ": " . $b['book_name'] ;
                }
            }
            $toSort[$b['isbn13']] = $title ;
            $toSortReversed[$title] = $b['isbn13'] ;
        }
        sort($toSort, SORT_NATURAL | SORT_FLAG_CASE) ;

        foreach ($toSort as $element) {
            $sorted[] = [
                'book_id' => $toSortReversed[$element]
            ];
        }
        return $sorted ;
    }

    private function updateSorting (int $id, array $shelves, string $newSort) {
        foreach ($shelves as $book) {
            $this->resetParams() ;
            self::$_columns = ["count(book_id) as nb"] ;
            self::$_where = ['book_id = ?', "bookshelf_id = ?"] ;
            self::$_params = [$book['book_id'], $id] ;
            $nb = $this->get("book_in_shelf", self::$_columns) ;

            if (intval($nb[0]["nb"]) === 1) {
                $this->resetParams() ;
                self::$_set = ['shelf_number = ?', 'shelf_position = ?'];
                self::$_where = ['book_id = ?', 'bookshelf_id = ?'];
                self::$_params = [$book['shelf_number'], $book['shelf_position'], $book['book_id'], $id];
                $this->patch('book_in_shelf');
            } else {
                $this->resetParams();
                self::$_columns = ['bookshelf_id', 'book_id', 'shelf_number', 'shelf_position'];
                self::$_params = [$id, $book['book_id'], $book['shelf_number'], $book['shelf_position']];
                $this->add('book_in_shelf', self::$_columns) ;
            }
        }

        $this->resetParams();
        self::$_set = ['sorting = ?'] ;
        self::$_params = [$newSort] ;
        $this->patch('bookshelf', $id) ;
    }
}