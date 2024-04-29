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

class solver_edmonds_karp extends distributor {

    public function get_name() {
        return 'edmonds_karp';
    }

    public function compute_distribution($choicerecords, $ratings, $usercount, $teamvote) {
        $choicedata = array();
        foreach ($choicerecords as $record) {
            $choicedata[$record->id] = $record;
        }

        $choicecount = count($choicedata);

        // Index of source and sink in the graph.
        $source = 0;

        if (!$teamvote) {

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

        } else {

            var_dump("Teamvote = true");
            $teamcount = count($teamvote);
            $sink = $choicecount + $teamcount + 1;

            list($fromteamid, $toteamid, $fromchoiceid, $tochoiceid) = $this->setup_id_conversions_for_teamvote($teamcount, $ratings);

            $this->setup_graph_for_teamvote($choicecount, $teamcount, $fromteamid, $fromchoiceid, $ratings, $choicedata, $source, $sink, -1);

            // Now that the datastructure is complete, we can start the algorithm
            // This is an adaptation of the Ford-Fulkerson algorithm
            // with Bellman-Ford as search function (see: Edmonds-Karp in Introduction to Algorithms)
            // http://stackoverflow.com/questions/6681075/while-loop-in-php-with-assignment-operator
            // Look for an augmenting path (a shortest path from the source to the sink).
            while ($path = $this->find_shortest_path_bellf_cspf($source, $sink, $teamvote, $toteamid)) { // If the function returns null, the while will stop.
                // Reverse the augmentin path, thereby distributing a user into a group.
                $this->augment_flow($path);
                unset($path); // Clear up old path.
            }
            return $this->extract_allocation($toteamid, $tochoiceid);

        }

    }

    /**
     * Find the shortest path with constraint (enough space for all teammembers in choice).
     * This is a modified version of the Yen Algorithm for the consstrained shortest path first problem.
     *
     * @param $from
     * @param $to
     * @param $teamvote
     * @param $toteamid
     * @return array|mixed|null array of the nodes in the path, null if no path found.
     */
    private function find_shortest_path_bellf_cspf ($from, $to, $teamvote, $toteamid) {

        // Find the first shortest path.
        $pathcandidates = array();
        $pathcandidates[0] = $this->find_shortest_path_bellf($from, $to);

        $nopathfound = is_null($pathcandidates[0]);

        // Check if the path fulfills our constraint: space in choice left >= teammembers.
        $constraintflag = true;
        $foundedge = null;
        foreach ($this->graph[$pathcandidates[0][1]] as $edge) {
            if ($edge->to == $pathcandidates[0][0]) {
                $foundedge = $edge;
                break;
            }
        }
        if ($foundedge->space <= $teamvote[$toteamid[$pathcandidates[0][2]]]) {
            $constraintflag = false;
        }

        if ($constraintflag) {
            // We just found the shortest path fulfilling the constraint.
            return $pathcandidates[0];
        }
        $constraintflag = true;

        // Array of the potential next shortest paths.
        $nextpaths = array();
        $restoreedges = array();
        $restorenodes = array();

        // Now find the next shortest path.
        $k = 1;
        // Exit if there are no more shortest paths (nopathfound=true).
        while (!$nopathfound && $k < 100) {
            for ($i = 0; $i < count($pathcandidates[$k - 1]); $i++) {

                var_dump("Im algo </br>");
                var_dump($pathcandidates);
                var_dump(":Pathcandidates </br>");

                // Spurnode ranges from first to next to last node in previous shortest path.
                $spurnode = $pathcandidates[$k - 1][$i];
                $rootpath = array_slice($pathcandidates[$k - 1], 0, $i+1, true);

                foreach ($pathcandidates as $path) {

                    if ($rootpath == array_slice($path, 0, $i+1, true)) {
                        foreach ($this->graph[$path[$i + 1]] as $index => $edge) {
                            if ($edge->to == $path[$i]) {
                                // Remove the links that are part of the previous shortest paths.
                                // Which share the same root path.
                                $restoreedges[$path[$i + 1]][$index] = $edge;
                                array_splice($this->graph[$path[$i + 1]], $index, 1);
                                break;
                            }
                        }
                    } else {
                        continue;
                    }

                    foreach ($rootpath as $rootpathnode) {
                        if ($rootpathnode != $spurnode) {
                            // Remove $rootpathnode from graph.
                            foreach ($this->graph as $index => $graphnode) {
                                if ($graphnode == $rootpathnode) {
                                    $restorenodes[$index] = $graphnode;
                                    unset($this->graph[$index]);
                                }
                            }
                        }
                    }

                    // Calculate the spur path from the spur node to the sink.
                    $spurpath = $this->find_shortest_path_bellf($i, $to);

                    // Entire path is made up of the root path and spur path.
                    $totalpath = array_merge($rootpath, $spurpath);

                    // Add the potential next shortest path to the heap.
                    $nextpaths[] = $totalpath;

                    // Now add back edges and nodes that were removed from the graph.
                    foreach ($restoreedges as $index1 => $node) {
                        foreach ($node as $index2 => $edge) {
                            $this->graph[$index1][$index2] = $edge;
                        }
                    }
                    foreach ($restorenodes as $index => $node) {
                        $this->graph[$index] = $node;
                    }
                }

                if (empty($nextpaths)) {
                    var_dump("No path found </br>");
                    $nopathfound = true;
                    break;
                }

                var_dump($nextpaths);
                // Sort the potential next shortest paths by cost. -> nextpaths[0] = best path with lowest cost.
                usort($nextpaths, function ($path1, $path2) {
                    return ($this->get_cost_of_path($path1) - $this->get_cost_of_path($path2));
                });
                var_dump("</br> Sortieren... </br>");
                var_dump($nextpaths);

                // Check if the next best path fullfillst our constraint.
                foreach ($this->graph[$nextpaths[0][1]] as $edge) {
                    if ($edge->to == $nextpaths[0][0]) {
                        $foundedge = $edge;
                        break;
                    }
                }
                if ($foundedge->space <= $teamvote[$toteamid[$nextpaths[0][2]]]) {
                    $constraintflag = false;
                }

                if ($constraintflag) {
                    var_dump("Path found");
                    return $nextpaths[0];
                }

                $pathcandidates[$k] = $nextpaths[0];

                // Reset flag condition.
                $constraintflag = true;

                array_pop($nextpaths);
            }
            $k++;
        }
        return null;
    }

    /**
     * Returns the cost of the path by adding the weight of all edges in the path.
     *
     * @param $path
     * @return int cost
     */
    private function get_cost_of_path ($path) {

        $cost = 0;

        for ($i = count($path) - 1; $i > 0; $i--) {
            $from = $path[$i];
            $to = $path[$i - 1];
            $edge = null;
            // Find the edge.
            foreach ($this->graph[$from] as $index => $edge) {
                if ($edge->to == $to) {
                    $cost += $edge->weight;
                    break;
                }
            }
        }

        return $cost;
    }

    /**
     * Bellman-Ford acc. to Cormen
     *
     * @param $from int index of starting node
     * @param $to int index of end node
     * @return array with the of the nodes in the path
     */
    private function find_shortest_path_bellf($from, $to) {

        // We have to alter this method to fit teamvote (find the shortest path with flow >= teammembers).
        // This is a constrained shortest path first problem.

        // Table of distances known so far.
        $dists = array();
        // Table of predecessors (used to reconstruct the shortest path later).
        $preds = array();

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
        $path = array();
        $p = $to;
        while ($p != $from) {
            $path[] = $p;
            $p = $preds[$p];
        }
        $path[] = $from;
        return $path;
    }

}
