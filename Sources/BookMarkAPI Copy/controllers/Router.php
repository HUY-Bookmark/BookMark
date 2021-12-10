<?php

function prettyPrint ($data) {
    echo "<pre>" ;
    var_dump($data) ;
    echo "</pre>" ;
}

//require_once('views/View.php');

class Router
{
    private $_ctrl;

    public function routeReq()
    {
        try {
            spl_autoload_register(function ($class) {
                if (file_exists($_SERVER['DOCUMENT_ROOT'] . "/models/" . ucfirst($class) . ".php"))
                    require_once('models/' . ucfirst($class) . '.php');
                else
                    require_once('views/' . ucfirst($class) . '.php');
            });

            $url = '';

            if (isset($_GET['url']) && strlen($_GET['url']) > 0) {
                $url = explode('/', filter_var($_GET['url'],
                    FILTER_SANITIZE_URL));

                $controller = ucfirst(strtolower($url[0]));
                $controllerClass = 'Controller' . $controller;
                $controllerFile = 'controllers/' . $controllerClass . '.php';

                if (file_exists($controllerFile)) {
                    require_once($controllerFile);
                    $this->_ctrl = new $controllerClass($url);

                } else
                    throw new Exception('<p>Page no found<p> <img src="https://http.cat/404.jpg" alt="404">');

            } else {
                require_once('controllers/ControllerHome.php');
                $this->_ctrl = new ControllerHome($url);
            }
        } catch (Exception $e) {
            header('location: /');
        }
    }
}
