<?php

require_once(dirname(__FILE__).'/config.php');
require_once(dirname(__FILE__).'/../lib/lp.php');

$engine_path = '\\ratingallocate\\lp\\engines\\'.RATINGALLOCATE_ENGINE;
$engine = new $engine_path();

$webservice = new \ratingallocate\lp\executors\webservice\backend($engine,
                                                                  RATINGALLOCATE_LOCAL_PATH,
                                                                  RATINGALLOCATE_SECRET);

$webservice->main();