<?php

require_once(dirname(__FILE__).'/weighter.php');

foreach(glob(dirname(__FILE__).'/weighters/*.php') as $weighter)
    require_once($weighter);