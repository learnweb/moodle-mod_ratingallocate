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

defined('MOODLE_INTERNAL') || die();

class lp_weighter_test extends basic_testcase {

    private $weighter1 = null;
    private $weighter2 = null;

    /**
     * @covers \mod_ratingallocate\local\lp\weighters\polynomial_weighter::__construct
     * @covers \mod_ratingallocate\local\lp\weighters\identity_weighter::__construct
     */
    protected function setUp() {
        $this->weighter1 = new \mod_ratingallocate\local\lp\weighters\polynomial_weighter([4, 2, 0]);
        $this->weighter2 = new \mod_ratingallocate\local\lp\weighters\identity_weighter();
    }

    /**
     * @covers \mod_ratingallocate\local\lp\weighters\polynomial_weighter::apply
     */
    public function test_polynomial_apply() {
        $this->assertEquals($this->weighter1->apply(3), 4*3*3 + 2*3);
    }

    /**
     * @covers \mod_ratingallocate\local\lp\weighters\polynomial_weighter::apply
     */
    public function test_polynomial_to_string() {
        $this->assertEquals($this->weighter1->to_string('y'), '2*y+4*y^2');
    }

    /**
     * @covers \mod_ratingallocate\local\lp\weighters\identity_weighter::apply
     */
    public function test_identity_apply() {
        $this->assertEquals($this->weighter2->apply(4), 4);
    }
}