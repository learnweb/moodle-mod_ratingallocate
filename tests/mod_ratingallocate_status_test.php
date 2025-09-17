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

use PHP_CodeSniffer\Generators\Generator;
use PhpOffice\PhpSpreadsheet\Worksheet\Iterator;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/generator/lib.php');
require_once(__DIR__ . '/../locallib.php');

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;

/**
 * Tests the method get_status()
 *
 * @package    mod_ratingallocate
 * @category   test
 * @group mod_ratingallocate
 * @copyright  reischmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(ratingallocate::class)]
#[CoversMethod(ratingallocate::class, 'get_status')]
final class mod_ratingallocate_status_test extends \advanced_testcase {
    public function setUp(): void {
        global $PAGE;
        parent::setUp();
        $PAGE->set_url('/');
        $this->resetAfterTest();
    }

    /**
     * Provider
     *
     * @return array
     */
    public static function ratingallocate_provider(): array {
        return [
                'Rating phase is not started.' => [
                        3, 6, false, false, ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY],
                'Rating phase is not started, but some allocations exist.' => [
                        3, 6, false, true, ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY],
                'Rating phase is not started, but allocation is published.' => [
                        3, 6, true, false, ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY],
                'Rating phase is not started, but allocations exist and are published.' => [
                        3, 6, true, true, ratingallocate::DISTRIBUTION_STATUS_TOO_EARLY],
                'The rating phase is running' => [
                        -1, 6, false, false, ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS],
                'The rating phase is running, but allocations exist.' => [
                        -1, 6, false, true, ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS],
                'The rating phase is running, but allocation is published.' => [
                        -1, 6, true, false, ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS],
                'The rating phase is running, but allocations exist and are published.' => [
                        -1, 6, true, true, ratingallocate::DISTRIBUTION_STATUS_RATING_IN_PROGRESS],
                'The rating phase is running.' => [
                        -7, -6, false, false, ratingallocate::DISTRIBUTION_STATUS_READY],
                'The rating phase is running and some allocations exist.' => [
                        -7, -6, false, true, ratingallocate::DISTRIBUTION_STATUS_READY_ALLOC_STARTED],
                'The rating phase is running and allocation is published.' => [
                        -7, -6, true, false, ratingallocate::DISTRIBUTION_STATUS_PUBLISHED],
                'The rating phase is running and allocations exist and are published.' => [
                        -7, -6, true, true, ratingallocate::DISTRIBUTION_STATUS_PUBLISHED],
        ];
    }

    /**
     * Tests under different conditions if the returned status object is correct.
     *
     * @dataProvider ratingallocate_provider
     * @param int $addtostart
     * @param int $addtostop
     * @param bool $published
     * @param bool $hasallocations
     * @param \stdClass $expected
     * @return void
     * @throws \coding_exception
     * @covers \mod_ratingallocate\ratingallocate::get_status
     */
    public function test_get_status($addtostart, $addtostop, $published, $hasallocations, $expected): void {
        $record = [
                'name' => 'Rating Allocation',
                'accesstimestart' => time() + ($addtostart * 24 * 60 * 60),
                'accesstimestop' => time() + ($addtostop * 24 * 60 * 60),
                'strategyopt' => ['strategy_yesno' => ['maxcrossout' => '1']],
                'strategy' => 'strategy_yesno'];
        if ($hasallocations) {
            $genmod = new \mod_ratingallocate_generated_module($this, $record);
            $moddb = $genmod->moddb;
        } else {
            $course = $this->getDataGenerator()->create_course();
            $record['course'] = $course;
            $moddb = $this->getDataGenerator()->create_module(RATINGALLOCATE_MOD_NAME, $record);
        }

        $ratingallocate = \mod_ratingallocate_generator::get_ratingallocate($moddb);
        $ratingallocate->ratingallocate->__set("published", $published);

        $status = $ratingallocate->get_status();
        $this->assertEquals($expected, $status);
    }
}
