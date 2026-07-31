<?php

declare(strict_types=1);

use Bhitti\Http\Request;

$app = require dirname(__DIR__) . '/bootstrap/app.php';

$app->run(Request::capture());

// request remove after ending
// Bhitti\Http\RequestContext::clear();
