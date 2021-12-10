<?php

require_once ("Api.php") ;

class ApiUser extends Api
{
    private $_response ;
    private $_data ;
    private $_pwd_pattern = ';^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-_]).{8,}$;' ;
    private $_email_pattern = ';[^@ \t\r\n]+@[^@ \t\r\n]+\.[^@ \t\r\n]+;' ;

    public function __construct ($url, $method)
    {
        switch (strtolower($method)) {
            case "get":
                if ($url[0] === "log") $this->_response[] = $this->login() ;
                break ;
            case "post":
                $this->_response[] = $this->signup() ;
                break ;
            default:
                $this->_response = $this->errorResponse("METHOD NOT ALLOWED") ;
                http_response_code(405) ;
        }
        echo json_encode($this->_response) ;
    }

    private function login () {
        $this->_data = $this->getJsonArray() ;

        $columns = ["id", "username", "email", "token"] ;
        self::$_where = [
            "username = ?",
            "password = ?"
        ];
        self::$_params = [
            $this->_data['usr'],
            hash('sha512', $this->_data['pwd'])
        ] ;
        $user = $this->get("user", $columns) ;
        if (count($user) > 0) {
            return $user[0];
        }

        http_response_code(404) ;
        return "WRONG CREDENTIALS" ;
    }

    private function signup () {
        $this->_data = $this->getJsonArray() ;

        if ($res = !$this->checkSignupData()) return $res ;

        self::$_columns = ['username', 'password', 'email'] ;
        self::$_params = [
            $this->_data['usr'],
            hash("sha512", $this->_data['pass']),
            $this->_data["email"]
        ] ;

        return $this->add("user") ;
    }

    private function checkSignupData ():bool {
        if (!isset($this->_data['usr'], $this->_data['pass'], $this->_data['passcheck'], $this->_data['email'])) {
            $this->_response["errors"][] = "missing_arg" ;
            http_response_code(412) ;
            return false;
        }

        //check email validity
        if (!preg_match($this->_email_pattern, $this->_data['email'])) {
            $this->_response['errors'][] = 'emailexpr' ;
            http_response_code(412) ;
            return false ;
        }

        //check password validity
        if ($this->_data['pass'] !== $this->_data['passcheck']) {
            $this->_response['errors'][] = 'passmatch' ;
            http_response_code(412) ;
            return false ;
        }
        if (!preg_match($this->_pwd_pattern, $this->_data['pass'])) {
            $this->_response['errors'][] = 'passexpr' ;
            http_response_code(412) ;
            return false ;
        }

        //check username or email already in use
        if ($this->checkValExists('username', $this->_data['usr'], 'user', 'id')
            || $this->checkValExists('email', $this->_data['email'], 'user', 'id')) {
            return false ;
        }

        return true ;
    }

    private function checkValExists (string $colName, string $value, string $table, string $countCol) :bool {
        $this->resetParams();
        self::$_where = ["$colName = ?"];
        self::$_params = [$value] ;
        $res = $this->get($table, ["count($countCol) as nb"]) ;
        if (intval($res[0]['nb']) !== 0) {
            $this->_response["errors"][] = "conflict_$colName" ;
            http_response_code(409) ;
            return true ;
        }
        return false ;
    }
}