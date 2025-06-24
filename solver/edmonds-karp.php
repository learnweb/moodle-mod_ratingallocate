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
 *
 * Contains the algorithm for the distribution
 *
 * @package    mod_ratingallocate
 * @subpackage mod_ratingallocate
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__) . '/../locallib.php');
require_once(dirname(__FILE__) . '/solver-template.php');

/**
 * Solver edmonds-karp.
 *
 * @package mod_ratingallocate
 */
class solver_edmonds_karp extends distributor {

    /**
     * Return name.
     *
     * @return string
     */
    public function get_name() {
        return 'edmonds_karp';
    }

    /**
     * Compute distribution.
     *
     * @param array $choicerecords
     * @param array $ratings
     * @param int $usercount
     * @return array
     * @throws moodle_exception
     */
    public function compute_distribution($choicerecords, $ratings, $usercount) {
        $choicedata = [];
        foreach ($choicerecords as $record) {
            $choicedata[$record->id] = $record;
        }

        $choicecount = count($choicedata);
        // Index of source and sink in the graph.
        $source = 0;
        $sink = $choicecount + $usercount + 1;

        list($fromuserid, $touserid, $fromchoiceid, $tochoiceid) = $this->setup_id_conversions($usercount, $ratings);

        $this->setup_graph($choicecount, $usercount, $fromuserid, $fromchoiceid, $ratings, $choicedata, $source, $sink, -1);

        // Now that the datastructure is complete, we can start the algorithm
        // This is an adaptation of the Ford-Fulkerson algorithm
        // with Bellman-Ford as search function (see: Edmonds-Karp in Introduction to Algorithms)
        // http://stackoverflow.com/questions/6681075/while-loop-in-php-with-assignment-operator
        // Look for an augmenting path (a shortest path from the source to the sink).
        while ($path = $this->find_shortest_path_bellf($source, $sink)) { // If the function returns null, the while will stop.
            // Reverse the augmentin path, thereby distributing a user into a group.
            $this->augment_flow($path);
            unset($path); // Clear up old path.
        }
        return $this->extract_allocation($touserid, $tochoiceid);
    }

    /**
     * Bellman-Ford acc. to Cormen
     *
     * @param int $from index of starting node
     * @param int $to index of end node
     * @return array with the of the nodes in the path
     */
    private function find_shortest_path_bellf($from, $to) {
        // Table of distances known so far.
        $dists = [];
        // Table of predecessors (used to reconstruct the shortest path later).
        $preds = [];

        // Number of nodes in the graph.
        $count = $this->graph['count'];

        // Step 1: initialize graph.
        for ($i = 0; $i < $count; $i++) { // For each vertex v in vertices.
            if ($i == $from) {// If v is source then weight[v] := 0.
                $dists[$i] = 0;
            } else {// Else set weight[v] to infinity.
                $dists[$i] = INF;
            }
            $preds[$i] = null; // Set predecessor[v] to null.
        }

        // Step 2: relax edges repeatedly.
        for ($i = 0; $i < $count; $i++) { // For i from 1 to size(vertices)-1:.
            $updatedsomething = false;
            foreach ($this->graph as $key => $edges) { // For each edge (u, v) with weight w in edges:.
                if (is_array($edges)) {
                    foreach ($edges as $key2 => $edge) {
                        if ($dists[$edge->from] + $edge->weight < $dists[$edge->to]) { // If weight[u] + w < weight[v]:.
                            $dists[$edge->to] = $dists[$edge->from] + $edge->weight; // Set weight[v] := weight[u] + w.
                            $preds[$edge->to] = $edge->from; // Set predecessor[v] := u.
                            $updatedsomething = true;
                        }
                    }
                }
            }
            if (!$updatedsomething) {
                break; // Leave.
            }
        }

        // If there is no path to $to, return null.
        if (is_null($preds[$to])) {
            return null;
        }

        // Cleanup dists to save some space.
        unset($dists);

        // Use the preds table to reconstruct the shortest path.
        $path = [];
        $p = $to;
        while ($p != $from) {
            $path[] = $p;
            $p = $preds[$p];
        }
        $path[] = $from;
        return $path;
    }

}
