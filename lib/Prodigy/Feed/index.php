<?php
// Registering lazy services
$this->registerServices(
    array(
        array('feedRender', '\Prodigy\Feed\Render'),
        array('feedData',   '\Prodigy\Feed\DataQuery'),
        array('feedsrvc',   '\Prodigy\Feed\Service'),
    )
);

//// Defaults for POST requests
$this->respond('POST', null, function($request, $response, $service, $app) use ($namespace) {
    $service->namespace = SITE_ROOT . $namespace;
    //// Check for CSRF
    $service->validateParam('csrf', 'CSRF token missing.')->isAlnum();
    if ($request->param('csrf') != $app->session->id) $app->feedsrvc->abort('Error', 'Session error.');
});

//// Defaults for GET requests
$this->respond('GET', null, function($request, $response, $service, $app) use ($namespace) {
    if ($response->isSent()) return;
    $app->feedsrvc->build_menu();
    //$service->namespace = $this->route_factory->getNamespace();
    $service->before = intval($request->param('before', null));
    $service->pageNext = 0;
    $service->pagePrev = 0;
    $service->paginateBy = 25;
    $service->next_page_available = false;
    $service->post_view = false;
    $service->namespace = SITE_ROOT . $namespace;
});

$this->respond('GET', '/test/', 'main->example');


//// Show a post
$this->respond('GET', '/[i:cat]/[i:board]/[i:postid]/', 'feedRender->article');
//// Annotate a post
$this->respond(array('POST','GET'), '/[i:cat]/[i:board]/[i:postid]/edit/', 'feedRender->article_edit');
//// Show board
$this->respond('GET', '/[i:cat]/[i:board]/[all:all]?/', 'feedRender->board');
//// RSS for board
$this->respond('GET', '/[i:cat]/[i:board]/[all:all]?/rss.xml', 'feedRender->boardrss');
//// Show category
$this->respond('GET', '/[i:cat]/[all:all]?/', 'feedRender->category');
//// RSS for cat
$this->respond('GET', '/[i:cat]/[all:all]?/rss.xml', 'feedRender->catrss');
//// Articles by topic
$this->respond('GET', '/[i:cat]/[i:board]/t[i:topic]/', 'feedRender->topic');
//// RSS for topic
$this->respond('GET', '/[i:cat]/[i:board]/t[i:topic]/rss.xml', 'feedRender->topicrss');

//// Root view
$this->respond('GET', '/', 'feedRender->root');
?>
