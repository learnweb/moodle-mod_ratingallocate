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

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../locallib.php');

abstract class algorithm {

    protected abstract function compute_distribution($choicerecords, $ratings, $usercount);

    /**
     * Entry-point for the \ratingallocate object to call a solver
     * @param \ratingallocate $ratingallocate
     */
    public function distribute_users(\ratingallocate $ratingallocate) {

        // Load data from database.
        $choicerecords = $ratingallocate->get_rateable_choices();
        $ratings = $ratingallocate->get_ratings_for_rateable_choices();

        // Randomize the order of the enrties to prevent advantages for early entry.
        shuffle($ratings);

        $usercount = count($ratingallocate->get_raters_in_course());

        $distributions = $this->compute_distribution($choicerecords, $ratings, $usercount);

        // Perform all allocation manipulation / inserts in one transaction.
        $transaction = $ratingallocate->db->start_delegated_transaction();

        $ratingallocate->clear_all_allocations();

        foreach ($distributions as $choiceid => $users) {
            foreach ($users as $userid) {
                $ratingallocate->add_allocation($choiceid, $userid);
            }
        }
        $transaction->allow_commit();
    }
}