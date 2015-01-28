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
 * Internal library of functions for module groupdistribution.
 *
 * Contains the algorithm for the group distribution 
 *
 * @package    mod_ratingallocate
 * @subpackage mod_ratingallocate originally mod_groupdistribution
 * @copyright  2014 M Schulze
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/solver-template.php');

class solver_ford_fulkerson extends distributor {

    /**
     * Starts the distribution algorithm.
     * Uses the users' ratings and a minimum-cost maximum-flow algorithm
     * to distribute the users fairly into the groups.
     * (see http://en.wikipedia.org/wiki/Minimum-cost_flow_problem)
     * After the algorithm is done, users are removed from their current
     * groups (see clear_all_groups_in_course()) and redistributed
     * according to the computed distriution.
     *
     */
    public function compute_distribution($choicerecords, $ratings, $usercount) {
        $groupdata = array();
        foreach ($choicerecords as $record) {
            $groupdata[$record->id] = $record;
        }

        $groupcount = count($groupdata);
        // Index of source and sink in the graph
        $source = 0;
        $sink = $groupcount + $usercount + 1;
        list($fromuserid, $touserid, $fromgroupid, $togroupid) = $this->setup_id_conversions($usercount, $ratings);

        $this->setup_graph($groupcount, $usercount, $fromuserid, $fromgroupid, $ratings, $groupdata, $source, $sink);

        // Now that the datastructure is complete, we can start the algorithm
        // This is an adaptation of the Ford-Fulkerson algorithm
        // (http://en.wikipedia.org/wiki/Ford%E2%80%93Fulkerson_algorithm)
        for ($i = 1; $i <= $usercount; $i++) {
            // Look for an augmenting path (a shortest path from the source to the sink)
            $path = $this->find_shortest_path_bellmanf_koegel($source, $sink);
            // If ther is no such path, it is impossible to fit any more users into groups.
            if (is_null($path)) {
                // Stop the algorithm
                continue;
            }
            // Reverse the augmenting path, thereby distributing a user into a group
            $this->augment_flow($path);
        }

        return $this->extract_allocation($touserid, $togroupid);
    }

    /**
     * Uses a modified Bellman-Ford algorithm to find a shortest path
     * from $from to $to in $graph. We can't use Dijkstra here, because
     * the graph contains edges with negative weight.
     *
     * @param $from index of starting node
     * @param $to index of end node
     * @return array with the of the nodes in the path
     */
    public function find_shortest_path_bellmanf_koegel($from, $to) {

        // Table of distances known so far
        $dists = array();
        // Table of predecessors (used to reconstruct the shortest path later)
        $preds = array();
        // Stack of the edges we need to test next
        $edges = $this->graph[$from];
        // Number of nodes in the graph
        $count = $this->graph['count'];

        // To prevent the algorithm from getting stuck in a loop with
        // with negative weight, we stop it after $count ^ 3 iterations
        $counter = 0;
        $limit = $count * $count * $count;

        // Initialize dists and preds
        for ($i = 0; $i < $count; $i++) {
            if ($i == $from) {
                $dists[$i] = 0;
            } else {
                $dists[$i] = -INF;
            }
            $preds[$i] = null;
        }

        while (!empty($edges) and $counter < $limit) {
            $counter++;

            /* @var e edge */
            $e = array_pop($edges);

            $f = $e->from;
            $t = $e->to;
            $dist = $e->weight + $dists[$f];

            // If this edge improves a distance update the tables and the edges stack
            if ($dist > $dists[$t]) {
                $dists[$t] = $dist;
                $preds[$t] = $f;
                foreach ($this->graph[$t] as $newedge) {
                    $edges[] = $newedge;
                }
            }
        }

        // A valid groupdistribution graph can't contain a negative edge
        if ($counter == $limit) {
            print_error('negative_cycle', 'ratingallocate');
        }

        // If there is no path to $to, return null
        if (is_null($preds[$to])) {
            return null;
        }

        // Use the preds table to reconstruct the shortest path
        $path = array();
        $p = $to;
        while ($p != $from) {
            $path[] = $p;
            $p = $preds[$p];
        }
        $path[] = $from;

        return $path;
    }

    public function get_name() {
        return "ford-fulkerson Koegel2014";
    }

}
