<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * mod_ratingallocate processor tests
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */  
class mod_ratingallocate_strategy_testcase extends advanced_testcase {
    
    
    /**
     * Test for correct validation of settings
     */
    public function test_yes_no_validation(){
        //Attribute required
        $settings = array(ratingallocate\strategy_yesno\strategy::MAXCROSSOUT => null);
        $strategy = new ratingallocate\strategy_yesno\strategy($settings);       
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_yesno\strategy::MAXCROSSOUT => -1);
        $strategy = new ratingallocate\strategy_yesno\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_yesno\strategy::MAXCROSSOUT => 1);
        $strategy = new ratingallocate\strategy_yesno\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }
    
    
    /**
     * Test for correct validation of settings
     */
     public function test_yes_maybe_no_validation(){
        //Attribute required
        $settings = array(ratingallocate\strategy_yesmaybeno\strategy::MAXNO => null);
        $strategy = new ratingallocate\strategy_yesmaybeno\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_yesmaybeno\strategy::MAXNO => -1);
        $strategy = new ratingallocate\strategy_yesmaybeno\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_yesmaybeno\strategy::MAXNO => 1);
        $strategy = new ratingallocate\strategy_yesmaybeno\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }
    
    
    /**
     * Test for correct validation of settings
     */
     public function test_lickert_validation(){
         //Attribute required
        $settings = array(ratingallocate\strategy_lickert\strategy::COUNTLICKERT => null);
        $strategy = new ratingallocate\strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute required
        $settings = array(ratingallocate\strategy_lickert\strategy::MAXNO => null);
        $strategy = new ratingallocate\strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_lickert\strategy::COUNTLICKERT => 1);
        $strategy = new ratingallocate\strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_lickert\strategy::MAXNO => -1);
        $strategy = new ratingallocate\strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_lickert\strategy::COUNTLICKERT => 3);
        $strategy = new ratingallocate\strategy_lickert\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_lickert\strategy::MAXNO => 1);
        $strategy = new ratingallocate\strategy_lickert\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }
    
    /**
     * Test for correct validation of settings
     */    
    public function test_points_validation(){
        //Attribute required
        $settings = array(ratingallocate\strategy_points\strategy::MAXZERO => null);
        $strategy = new ratingallocate\strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute required
        $settings = array(ratingallocate\strategy_points\strategy::TOTALPOINTS => null);
        $strategy = new ratingallocate\strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_points\strategy::MAXZERO => -1);
        $strategy = new ratingallocate\strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_points\strategy::TOTALPOINTS => 0);
        $strategy = new ratingallocate\strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_points\strategy::MAXZERO => 0);
        $strategy = new ratingallocate\strategy_points\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_points\strategy::TOTALPOINTS => 1);
        $strategy = new ratingallocate\strategy_points\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }
    
    
     /**
     * Test for correct validation of settings
     */
    public function test_order_validation(){
        //Attribute required
        $settings = array(ratingallocate\strategy_order\strategy::COUNTOPTIONS => null);
        $strategy = new ratingallocate\strategy_order\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());        
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_order\strategy::COUNTOPTIONS => 0);
        $strategy = new ratingallocate\strategy_order\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());   
        //No validation error
        $settings = array(ratingallocate\strategy_order\strategy::COUNTOPTIONS => 1);
        $strategy = new ratingallocate\strategy_order\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }
    
    
    /**
     * Test for correct validation of settings
     */
    public function test_tickyes_validation(){
        //Attribute required
        $settings = array(ratingallocate\strategy_tickyes\strategy::MINTICKYES => null);
        $strategy = new ratingallocate\strategy_tickyes\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //Attribute minimum error
        $settings = array(ratingallocate\strategy_tickyes\strategy::MINTICKYES => 0);
        $strategy = new ratingallocate\strategy_tickyes\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        //No validation error
        $settings = array(ratingallocate\strategy_tickyes\strategy::MINTICKYES => 1);
        $strategy = new ratingallocate\strategy_tickyes\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }    
    
}