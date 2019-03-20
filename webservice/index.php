<?php

require_once(dirname(__FILE__).'/config.php');

if(!RATINGALLOCATE_WEBSERVICE_BACKEND_ENABLED)
    die;

require_once(dirname(__FILE__).'/../classes/local/lp/engine.php');
require_once(dirname(__FILE__).'/../classes/local/lp/engines/scip.php');
require_once(dirname(__FILE__).'/../classes/local/lp/engines/cplex.php');
require_once(dirname(__FILE__).'/../classes/local/lp/executor.php');
require_once(dirname(__FILE__).'/../classes/local/lp/executors/local.php');
require_once(dirname(__FILE__).'/../classes/local/lp/executors/webservice/backend.php');

$engine_path = '\\mod_ratingallocate\\local\\lp\\engines\\'.RATINGALLOCATE_ENGINE;
$engine = new $engine_path();

$webservice = new \mod_ratingallocate\local\lp\executors\webservice\backend($engine,
                                                                            RATINGALLOCATE_SECRET);

$webservice->main();