<?php
define('PROJECT_ROOT', __DIR__);

require_once __DIR__ . '/lib/autoload.php';

define('SITE_ROOT', dirname($_SERVER['PHP_SELF']));

define('TIME_START', microtime(true));

// Update request when we have a subdirectory    
if(ltrim(SITE_ROOT, '/')){ 
    $_SERVER['REQUEST_URI'] = substr($_SERVER['REQUEST_URI'], strlen(SITE_ROOT));
}

//$router = new \Klein\Klein();
$router = new \Prodigy\Router();

$router->onError(function ($router, $msg, $type, $err) {
    //fatal_error($err_msg);
    $service = $router->service();
    $response = $router->response();
    if ($response->isSent()) return;
    $app = $router->app();
    error_log("__ERROR__ onError $msg");
    //$app->errors->abort('ERROR!', $err_msg, 400);
    return $app->errors->backtrace('Fatal Error', $msg, $type, $err);
});

//// Setting up http error handler
$router->onHttpError(function ($code, $router) {
    $response = $router->response();
    $service = $router->service();
    if ($response->isSent()) return;
    $app = $router->app();
    //$service->title = 'Error';
    switch ($code) {
        case 404:
            //$service->message = 'Page not found.';
            $app->errors->abort('Not found', "[$code] Page not found.", $code);
            break;
        case 405:
            $app->errors->abort('Error 405', "[$code] You can't do that!");
            break;
        default:
            $app->errors->abort('Error', "Oh no, a bad error happened that caused a $code.");
    }
    //$service->render('templates/error.php');
});

$router->respond(function($request, $response, $service, $app, $router) {

    $app->router = $router;
    
    //$service->appDir = getConfig('forum_feed_url', '');
    
    //$service->static_root = getConfig('static_root', '');
    
    $service->app = $app;
    
    $protocol = 'http://';
    if ($request->isSecure())
        $protocol = 'https://';
    $host = $request->server()->get('HTTP_HOST');
    
    $service->protocol = $protocol;
    $service->host = $host;
    $service->siteurl = $protocol . $host . SITE_ROOT;
    //$service->baseHref = "$protocol$host{$service->appDir}";
    
    $request_path = $request->pathname();
    $request_uri = $request->uri();
    error_log("__DEBUG__: REQUEST_URI: $request_uri");
    
    //// Redirect all index.php requests to the main forum script
    //// Probably all this requests came here by mistake
    $indexphppos = strpos($request_path, 'index.php');
    if ($indexphppos !== false) {
        $new_uri = $protocol . $host . '/' . substr($request_uri, $indexphppos);
        $response->redirect($new_uri)->send();
        return;
    }
    
    //// Auto enclose all urls with trailing slash
    $path_len = strlen($request_path) - 1;
    if (strrpos($request_path, '.xml') !== ($path_len - 3) && strrpos($request_path, '/') !== $path_len) {
        //// request path doesn't ends witn '/', fix the url and redirect
        $new_uri = str_replace($request_path, "$request_path/", $request_uri);
        //// redirect to url with fixed path
        return $response->redirect($protocol.$host.SITE_ROOT.$new_uri)->send();
    }
    
    $router->registerServices(
        array(
            array('conf',     '\Prodigy\Config'),
            array('session',  '\Prodigy\Session'),
            array('user',     '\Prodigy\User'),
            //array('respond',  '\Prodigy\Respond\Respond'),
            array('main',     '\Prodigy\Respond\Main'),
            array('board',    '\Prodigy\Respond\Board'),
            array('thread',   '\Prodigy\Respond\Threads'),
            array('comments', '\Prodigy\Respond\Comments'),
            array('errors',   '\Prodigy\Errors\Errors'),
            array('subs',     '\Prodigy\Subs'),
            array('security', '\Prodigy\Security'),
            array('locale',   '\Prodigy\Localization'),
            array('im',       '\Prodigy\Respond\InstantMessages'),
            array('calendar', '\Prodigy\Respond\Calendar')
        )
    );
    
    $app->register('db', function() use ($router, $app) {
        // Lazy class init
        $db = new \Prodigy\MySQLDatabase($router);
        $app->conf->db_ready = $db->connect();
        return $db;
    });
    
    // these are from /feed/, should be moved somewhere.
    //$service->displayFilterLnk = false;
    //$service->unfiltered = false;
    
    $service->ajax = $request->param('requesttype') || $request->headers()->get('X_REQUESTED_WITH', false);
    
    // Our custom PHP error page
    set_error_handler(array($app->errors, 'handler'), E_ALL);
});

//// Defaults for POST requests
$router->respond('POST', null, function($request, $response, $service, $app) {
    //$app->srvc->sessionRequire();
    //// Check for CSRF
    //$service->validateParam('csrf', 'CSRF token missing.')->isAlnum();
    //if ($request->param('csrf') != $service->sessid) $app->srvc->abort('Error', 'Session error.');
});

//// Defaults for GET requests
$router->respond('GET', null, function($request, $response, $service, $app) {
    if ($response->isSent()) return;
    //$app->srvc->build_menu();
    
    $service->before = intval($request->param('before', null));
    $service->pageNext = 0;
    $service->pagePrev = 0;
    $service->paginateBy = 25;
    $service->next_page_available = false;
    $service->post_view = false;
});

//// Root view
$router->respond('GET', '/anon-function-respond/', function($request, $response, $service, $app) {
    //// Goto default category
    //$app->render->root();
    $app->errors->log('__DEBUG__: GET /');
    //var_dump($app->db);
    //var_dump($app->db->db_prefix);

});

$router->respond('GET', '/main/', function($request, $response, $service, $app) {
    //$app->respond->qwerty();
    //$app->respond('QWERTY');
    
    $respond = new \Prodigy\Respond\Main ($app);
    //var_dump($respond);
    $respond('QWERTY'); // run __invoke()
    $respond->aaaaa();  // run __call()
    //$respond->display();
});

$router->respond('GET', '/errortest/', function($request, $response, $service, $app) {
    //$app->errors->abort('Error Title', 'Error Message');
    //$app->errors->backtrace('Manual Error');
    //throw new Prodigy\Errors\MySQLException('Fuck!', 666);
    //$app->db->query('selectttt');
    //return call_user_func(array($app->errors, 'handler'));
    //set_error_handler(array($app->errors, 'handlerr'));
    $x = $AAA;
    error_log("__DEBUG__: afer error handler");
    //$app->errors->handler();
});

// Registering routes
$router->respond('GET', '/', 'main->index');
$router->respond(array('GET', 'POST'), '/login/', 'user->loginform');
$router->respond('GET', '/logout/', 'user->logout');

$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/', 'thread->display');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/[i:page]/', 'thread->display');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/msg[i:startmsg]/', 'thread->display');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/[new|all|next|prev:start]/', 'thread->display');
$router->respond('POST', '/preview/', 'thread->preview');

$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/reply/[i:quotemsg]?/', 'thread->reply');
//$router->respond('GET', '/b[i:board]/t[i:thread]/quote/[i:quotemsg]/', 'thread->reply');

$router->respond(array('GET', 'POST'), '/b[i:board]/[i:start]?/', 'board->index');
$router->respond(array('GET', 'POST'), '/b[i:board]/[all:start]/', 'board->index');

//$router->respond('GET', '/main-static-call/', '\Prodigy\Router::Main');


$router->respond('GET', '/example/', 'main->example');
$router->respond('GET', '/example/', 'main->example2');
$router->respond('GET', '/test/', 'main->testResponse');

$router->dispatch();

//var_dump($router->app()->conf);

?>