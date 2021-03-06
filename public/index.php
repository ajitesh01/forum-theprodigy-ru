<?php
define('PROJECT_ROOT', dirname(__DIR__));

require_once PROJECT_ROOT . '/vendor/autoload.php';

define('SITE_ROOT', dirname(dirname($_SERVER['PHP_SELF'])));

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
    //$service->render('error.php');
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
    $service->httphost = "$protocol$host";
    $service->siteurl = $protocol . $host . SITE_ROOT; // with install prefix dir
    //$service->baseHref = "$protocol$host{$service->appDir}";
    define('SITE_URL', $service->siteurl);
    
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
            array('dumb',     '\Prodigy\Respond\Dumb'),
            array('main',     '\Prodigy\Respond\Main'),
            array('board',    '\Prodigy\Respond\Board'),
            array('thread',   '\Prodigy\Respond\Threads'),
            array('comments', '\Prodigy\Respond\Comments'),
            array('stats',    '\Prodigy\Respond\Stats'),
            array('errors',   '\Prodigy\Errors\Errors'),
            array('subs',     '\Prodigy\Subs'),
            array('security', '\Prodigy\Security'),
            array('locale',   '\Prodigy\Localization'),
            array('im',       '\Prodigy\Respond\InstantMessages'),
            array('calendar', '\Prodigy\Respond\Calendar'),
            array('profile',  '\Prodigy\Respond\Profile'),
            array('karma',    '\Prodigy\Respond\Karma'),
            array('register', '\Prodigy\Respond\Register'),
            array('example',  '\Prodigy\Respond\Example'),
        )
    );
    
    $app->register('db', function() use ($router, $app) {
        // Lazy class init
        $db = new \Prodigy\PDOWrapper($router);
        $app->conf->db_ready = $db->connect();
        return $db;
    });
    
    // these are from /feed/, should be moved somewhere.
    //$service->displayFilterLnk = false;
    //$service->unfiltered = false;
    
    $service->ajax = $request->param('requesttype') || $request->headers()->get('X_REQUESTED_WITH', false);
    
    // Our custom PHP error page
    set_error_handler(array($app->errors, 'handler'), E_ALL);
    //set_error_handler(array($app->errors, 'handler'), E_STRICT);
});

//// Defaults for POST requests TODO
//$router->respond('POST', null, function($request, $response, $service, $app) {
    //$app->srvc->sessionRequire();
    //// Check for CSRF
    //$service->validateParam('csrf', 'CSRF token missing.')->isAlnum();
    //if ($request->param('csrf') != $service->sessid) $app->srvc->abort('Error', 'Session error.');
//});

//// Defaults for GET requests
$router->respond('GET', null, function($request, $response, $service, $app) {
    if ($response->isSent())
        return;
    
    $service->before = intval($request->param('before', null));
    $service->pageNext = 0;
    $service->pagePrev = 0;
    $service->paginateBy = 25;
    $service->next_page_available = false;
    $service->post_view = false;
});

// Registering routes
$router->respond('GET', '/', 'main->index');
$router->respond(array('GET', 'POST'), '/login/', 'profile->loginform');
$router->respond('GET', '/logout/', 'profile->logout');

$router->respond('GET', '/[i:msg]/', 'thread->gotomsg');

$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/', 'thread->display');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/[i:page]/', 'thread->display');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/msg[i:startmsg]/', 'thread->display');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/[new|all|next|prev:start]/', 'thread->display');
$router->respond('POST', '/preview/', 'thread->preview');

$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/reply/[i:quote]?/', 'thread->reply');
$router->respond(array('GET', 'POST'), '/b[i:board]/post/', 'thread->newThread');

$router->respond(array('GET', 'POST'), '/b[i:board]/[i:start]?/', 'board->index');
$router->respond(array('GET', 'POST'), '/b[i:board]/[all:start]/', 'board->index');

$router->respond(array('GET', 'POST'), '/modify/[i:msg]/', 'thread->modify');
$router->respond(array('GET', 'POST'), '/delete/[i:msg]/', 'thread->deleteMsg');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/delete/', 'thread->deleteThread');

$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/editpoll/', 'thread->editpoll');
$router->respond(array('GET', 'POST'), '/b[i:board]/t[i:thread]/vote/[i:poll]/', 'thread->pollVote');

// User Profile
$router->respond('GET', '/people/[:user]/', 'profile->show');
$router->respond('GET', '/people/[:user]/messages/[i:start]?/', 'profile->messages');
$router->respond(array('GET', 'POST'), '/people/[:user]/modify/', 'profile->edit');

// Karma
$router->respond('GET', '/people/[:user]/karma/', 'karma->view');
$router->respond('GET', '/people/[:user]/karma/remove/[i:msgid]/', 'karma->remove');
$router->respond('GET', '/karma/[applaud|smite:action]/[i:msgid]/', 'karma->action');

// Comments
$router->respond(array('GET', 'POST'), '/comments/subscribed/', 'comments->subscribed');
$router->respond(array('GET', 'POST'), '/comments/to/[:user]/', 'comments->commentsTo');
$router->respond(array('GET', 'POST'), '/comments/by/[:user]/', 'comments->commentsBy');

// Instant Messages
$router->respond('GET', '/im/[i:start]?/', 'im->inbox');
$router->respond('GET', '/im/outbox/[i:start]?/', 'im->outbox');
$router->respond(array('GET', 'POST'), '/im/new/', 'im->impost');
$router->respond('GET', '/im/reply/[i:imsg]/', 'im->impost');
$router->respond('GET', '/im/quote/[i:imsg]/', 'im->quote');
$router->respond('GET', '/report/[i:msgid]/', 'thread->report');
$router->respond(array('GET', 'POST'), '/im/prefs/', 'im->prefs');
$router->respond(array('GET', 'POST'), '/im/[i:start]?/remove/[i:imid]?/', 'im->remove');
$router->respond(array('GET', 'POST'), '/im/outbox/[i:start]?/remove/[i:imid]?/', 'im->removeFromOutbox');
$router->respond('GET', '/im/[i:start]?/removeall/', 'im->removeall');
$router->respond('GET', '/im/outbox/[i:start]?/removeall/', 'im->removeallFromOutbox');

$router->respond('GET', '/stats/', 'stats->main');
$router->respond('GET', '/allmypeople/', 'stats->membersall');
$router->respond('GET', '/allmypeople/[grp|staff:sort]/', 'stats->membersByGroup');
$router->respond('GET', '/allmypeople/[top:sort]/', 'stats->topmembers');
$router->respond('GET', '/allmypeople/[alph:sort]/', 'stats->membersByLetter');
$router->respond('GET', '/people/', 'stats->allmypeople');

// Calendar
$router->respond('GET', '/calendar/', 'calendar->show');
$router->respond(array('GET', 'POST'), '/calendar/newevent/', 'calendar->postEvent');
$router->respond(array('GET', 'POST'), '/calendar/linkevent/b[i:board]/t[i:thread]/', 'calendar->linkevent');
$router->respond(array('GET', 'POST'), '/calendar/editevent/[i:eventid]/', 'calendar->editEvent');

$router->respond('GET', '/agreement/', 'register->agreement');

$router->respond('GET', '/example/', 'example->example');
//$router->respond('GET', '/example/', 'example->example2');
$router->respond('GET', '/test/', 'example->testResponse');
//$router->respond('GET', '/simple/', 'example->simple_example');
$router->respond('GET', '/phpinfo/', 'example->phpinfo');

$router->with('/feed', '../lib/Prodigy/Feed/index.php');
$router->with('/files', '../lib/Prodigy/Cloud/index.php');

$router->with('/admin', '../lib/Prodigy/Admin/index.php');

$router->dispatch();
