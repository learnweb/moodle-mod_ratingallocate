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

namespace mod_ratingallocate;
use mod_ratingallocate\strategy_yesno\strategy;

/**
 * mod_ratingallocate processor tests
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers ::validate_settings()
 */

/**
 * Strategy test
 *
 * @package mod_ratingallocate
 */
final class mod_ratingallocate_strategy_test extends \advanced_testcase {

    /**
     * Test for correct validation of settings
     *
     * @return void
     * @covers \strategy\strategy01_yes_no
     */
    public function test_yes_no_validation(): void {
        // Attribute required.
        $settings = [strategy::MAXCROSSOUT => null];
        $strategy = new strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy::MAXCROSSOUT => -1];
        $strategy = new strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy::MAXCROSSOUT => 1];
        $strategy = new strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }

    /**
     * Test for correct validation of settings
     *
     * @covers \strategy\strategy02_yes_maybe_no
     */
    public function test_yes_maybe_no_validation(): void {
        // Attribute required.
        $settings = [strategy_yesmaybeno\strategy::MAXNO => null];
        $strategy = new strategy_yesmaybeno\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_yesmaybeno\strategy::MAXNO => -1];
        $strategy = new strategy_yesmaybeno\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_yesmaybeno\strategy::MAXNO => 1];
        $strategy = new strategy_yesmaybeno\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }

    /**
     * Test for correct validation of settings
     *
     * @covers \strategy\strategy03_lickert
     */
    public function test_lickert_validation(): void {
        // Attribute required.
        $settings = [strategy_lickert\strategy::COUNTLICKERT => null];
        $strategy = new strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute required.
        $settings = [strategy_lickert\strategy::MAXNO => null];
        $strategy = new strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_lickert\strategy::COUNTLICKERT => 1];
        $strategy = new strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_lickert\strategy::MAXNO => -1];
        $strategy = new strategy_lickert\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_lickert\strategy::COUNTLICKERT => 3];
        $strategy = new strategy_lickert\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_lickert\strategy::MAXNO => 1];
        $strategy = new strategy_lickert\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }

    /**
     * Test for correct validation of settings
     *
     * @covers \strategy\strategy04_points
     */
    public function test_points_validation(): void {
        // Attribute required.
        $settings = [strategy_points\strategy::MAXZERO => null];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute required.
        $settings = [strategy_points\strategy::TOTALPOINTS => null];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute required.
        $settings = [strategy_points\strategy::MAXPERCHOICE => null];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_points\strategy::MAXZERO => -1];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_points\strategy::TOTALPOINTS => 0];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_points\strategy::MAXPERCHOICE => 0];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_points\strategy::MAXZERO => 0];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_points\strategy::TOTALPOINTS => 1];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_points\strategy::MAXPERCHOICE => 1];
        $strategy = new strategy_points\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }

    /**
     * Test for correct validation of settings
     *
     * @covers \strategy\strategy05_order
     */
    public function test_order_validation(): void {
        // Attribute required.
        $settings = [strategy_order\strategy::COUNTOPTIONS => null];
        $strategy = new strategy_order\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [strategy_order\strategy::COUNTOPTIONS => 0];
        $strategy = new strategy_order\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // No validation error.
        $settings = [strategy_order\strategy::COUNTOPTIONS => 1];
        $strategy = new strategy_order\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }

    /**
     * Test for correct validation of settings
     *
     * @covers \strategy\strategy06_tickyes
     */
    public function test_tickyes_validation(): void {
        // Attribute required.
        $settings = [\mod_ratingallocate\strategy_tickyes\strategy::MINTICKYES => null];
        $strategy = new \mod_ratingallocate\strategy_tickyes\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // Attribute minimum error.
        $settings = [\mod_ratingallocate\strategy_tickyes\strategy::MINTICKYES => 0];
        $strategy = new \mod_ratingallocate\strategy_tickyes\strategy($settings);
        $this->assertCount(1, $strategy->validate_settings());
        // No validation error.
        $settings = [\mod_ratingallocate\strategy_tickyes\strategy::MINTICKYES => 1];
        $strategy = new \mod_ratingallocate\strategy_tickyes\strategy($settings);
        $this->assertCount(0, $strategy->validate_settings());
    }

}
