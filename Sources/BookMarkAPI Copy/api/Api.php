<?php


class Api
{

    private static $_db;
    protected static $_columns;
    protected static $_where;
    protected static $_set;
    protected static $_params;
    protected static $_offset = 0;
    protected static $_limit = 1;
    protected static $_order;
    protected static $_join;
    protected static $_group;

    private function setDB()
    {
        try {
            $confFile = $_SERVER['DOCUMENT_ROOT'] . '/.conf.json';

            if (file_exists($confFile)) {
                $db = json_decode(file_get_contents($confFile), true) ;
                $db = $_SERVER['HTTP_HOST'] == "localhost:8888" ? $db['local'] : $db['remote'];
                $host = $db['host'] ;
                $dbn = $db['dbn'];
                $port = $db['port'];
                $usr = $db['usr'];
                $pwd = $db['pwd'];
            }

            $pdo = new PDO("mysql:host=$host;dbname=$dbn", $usr, $pwd);
            // set the PDO error mode to exception
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            self::$_db = $pdo;
        } catch (PDOException $e) {
            echo "Connection failed: " . $e->getMessage();
        }
    }

    protected function getDB(): PDO
    {
        if (self::$_db == null)
            self::setDb();
        return self::$_db;
    }

    private function exec (string $sql, string $returnCase) {
//        prettyPrint(self::$_params);

        $connect = $this->getDB() ;
        $stmt = $connect->prepare($sql);
        if ($stmt) {
            try {
                $success = $stmt->execute(self::$_params);
            } catch (Exception $error) {
                http_response_code(400);
                prettyPrint($error);
                if ($returnCase === "get") return [] ;
                return 0 ;
            }

            if ($success) {
                switch ($returnCase) {
                    case "add": return $connect->lastInsertId() ;
                    case "get": return $stmt->fetchAll(PDO::FETCH_ASSOC);
                    case "patch": return $stmt->rowCount();
                    default: return 1;
                }
            } else {
                http_response_code(417) ;
            }
        }
        http_response_code(500);
        if ($returnCase === "get") return [] ;
        return 0 ;
    }

    private function getColumns ($columns)
    {
        if ($columns === null) return [];
        if (isset($_GET['fields']) && !empty($_GET['fields'])) {
            $fields = explode(',', $_GET['fields']);
            self::$_columns = array_intersect($fields, $columns);

            if (count(self::$_columns) === 0) {
                http_response_code(400);
                return [];
            }
        } else {
            self::$_columns = $columns;
        }
    }

    // SELECT
    protected function get($table, $columns = null): array
    {
        // COLUMNS
        $this->getColumns($columns);
        $sql = "SELECT " . join(', ', self::$_columns) . " FROM $table";

        /// JOINS
        /* $_join = [[
         *    'type' => inner, left ou right
         *    'table' => TABLE2
         *    'onT1' => TABLE1.col
         *    'onT2' => TABLE2.col
         * ]]
        */
        if (isset(self::$_join) && !empty(self::$_join)) {
            foreach (self::$_join as $join) {
                $joinClause = ' ' . strtoupper($join['type']) . ' JOIN ' . $join['table'] . ' ON ' . $join['onT1'] . ' = ' . $join['onT2'];
                $sql .= $joinClause;
            }
        }

        // WHERE
        if (isset(self::$_where) && count(self::$_where) > 0) {
            $whereClause = join(' AND ', self::$_where);
            $sql .= ' WHERE ' . $whereClause;
        }

        // ORDER BY
        if (isset(self::$_order) && count(self::$_order) > 0) {
            $sql .= ' ORDER BY ' . implode(', ', self::$_order);
        }

        if (isset(self::$_group)) {
            $sql .= ' GROUP BY ' . implode(', ', self::$_group);
        }

        // LIMIT
        self::$_offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
        self::$_limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
//        self::$_limit = self::$_limit > 50 ? 50 : self::$_limit;
//        $sql .= " LIMIT " . self::$_offset . ', ' . self::$_limit;

        //echo $sql ; var_dump(self::$_params); //die () ;

        return $this->exec($sql, "get") ;
    }


    // INSERT
    protected function add($table)
    {

        $cols = join(', ', self::$_columns);
        $values = [];
        foreach (self::$_columns as $col) $values[] = '?';
        $values = join(', ', $values);
        $sql = "INSERT INTO $table ($cols) VALUES ($values)";

        return $this->exec($sql, "add") ;
    }

    // UPDATE
    protected function patch(string $table, int $id = 0): int
    {
        // UPDATE `CLIENT` SET name = ?, website = ? WHERE CLIENT.id = 2
        $sql = "UPDATE " . $table;

        // SET
        if (isset(self::$_set) && count(self::$_set) > 0) {
            $setClause = join(', ', self::$_set);
            $sql .= ' SET ' . $setClause;
        } else {
            // bad parameters
            http_response_code(400);
            return -1;
        }

        if ($id !== 0) {
            self::$_where[] = 'id = ?';
            self::$_params[] = $id;
        }

        $sql .= ' WHERE ' . implode(" AND ", self::$_where);

        return $this->exec($sql, "patch") ;
    }

    protected function delete($table, $id)
    {
        $sql = "DELETE FROM $table WHERE id = ?";
        self::$_params[] = $id;

        $this->exec($sql, "delete");

    }

    protected function resetParams()
    {
        self::$_columns = [];
        self::$_where = [];
        self::$_set = [];
        self::$_params = [];
        self::$_offset = 0;
        self::$_limit = 1;
        self::$_order = [];
        self::$_join = [];
    }

    protected function getJsonArray()
    {
        return json_decode(stripslashes(file_get_contents('php://input')), true);
    }

    protected function checkParams(array $required, array $array): bool
    {
        foreach ($required as $value) {
            if (empty($array[$value])) return false;
        }
        return true;
    }

    protected function checkAllowed(array $keys, array $allowed): bool
    {
        foreach ($keys as $value) {
            if (!in_array($value, $allowed)) return false;
        }
        return true;
    }

    protected function generateRandom(int $length)
    {
        $minuscules = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z');
        $majuscules = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        $code = '';
        for ($i = 1; $i <= $length; $i++) {
            $type = rand(0, 2);
            $rndNb = rand(0, 25);
            switch ($type) {
                case 0: $code .= rand(0, 9) ; break ;
                case 1: $code .= $majuscules[$rndNb]; break ;
                case 2: $code .= $minuscules[$rndNb]; break ;
            }
        }
        return $code;
    }

    protected function setUserSession ($authToken) {
        self::$_where = [
            'active = 1',
            'session.token = ?',
            '(endban IS NULL OR endban < NOW())'
        ];
        self::$_join = [
            [
                "type" => "inner",
                "table" => "users",
                "onT1" => "users.id",
                "onT2" => "session.user"
            ]
        ] ;
        self::$_params = [$authToken];
        $columns = ['users.id', 'role'];

        $res = $this->get('session', $columns);
        if (count($res) == 0) {
            http_response_code(401);
            exit() ;
        }

        $_SESSION['uid'] = $res[0]['id'] ;
        $_SESSION['role'] = $res[0]['role'] ;
        $this->resetParams();
    }

    protected function errorResponse ($errMsg) {
        return [
            'response' => "error",
            'error' => $errMsg
        ] ;
    }

    protected function okResponse (array $data) {
        return array_merge(['response' => 'ok'], $data) ;
    }
}