<?php

namespace ratingallocate\lp\weighters;

require_once('polynomial_weighter.php');

/**
 * Class which represents an identity weighter
 */
class identity_weighter extends polynomial_weighter {
    
    /**
     * Creates an identity weighter
     */
    public function __construct() {
        parent::__construct([1, 1]);
    }

};
     