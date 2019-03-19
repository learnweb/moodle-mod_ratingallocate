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
 * @package    raalgo_fordfulkersonkoegel
 * @copyright  2014 M Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace raalgo_fordfulkersonkoegel;
defined('MOODLE_INTERNAL') || die();

class algorithm_impl extends \mod_ratingallocate\algorithm {

    /** @var $graph Flow-Graph built */
    protected $graph;

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
        // Index of source and sink in the graph.
        $source = 0;
        $sink = $groupcount + $usercount + 1;
        list($fromuserid, $touserid, $fromgroupid, $togroupid) = $this->setup_id_conversions($usercount, $ratings);

        $this->setup_graph($groupcount, $usercount, $fromuserid, $fromgroupid, $ratings, $groupdata, $source, $sink);

        // Now that the datastructure is complete, we can start the algorithm.
        // This is an adaptation of the Ford-Fulkerson algorithm.
        // (http://en.wikipedia.org/wiki/Ford%E2%80%93Fulkerson_algorithm).
        for ($i = 1; $i <= $usercount; $i++) {
            // Look for an augmenting path (a shortest path from the source to the sink).
            $path = $this->find_shortest_path_bellmanf_koegel($source, $sink);
            // If there is no such path, it is impossible to fit any more users into groups.
            if (is_null($path)) {
                // Stop the algorithm.
                continue;
            }
            // Reverse the augmenting path, thereby distributing a user into a group.
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

        // Table of distances known so far.
        $dists = array();
        // Table of predecessors (used to reconstruct the shortest path later).
        $preds = array();
        // Stack of the edges we need to test next.
        $edges = $this->graph[$from];
        // Number of nodes in the graph.
        $count = $this->graph['count'];

        // To prevent the algorithm from getting stuck in a loop with
        // with negative weight, we stop it after $count ^ 3 iterations.
        $counter = 0;
        $limit = $count * $count * $count;

        // Initialize dists and preds.
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

            // If this edge improves a distance update the tables and the edges stack.
            if ($dist > $dists[$t]) {
                $dists[$t] = $dist;
                $preds[$t] = $f;
                foreach ($this->graph[$t] as $newedge) {
                    $edges[] = $newedge;
                }
            }
        }

        // A valid groupdistribution graph can't contain a negative edge.
        if ($counter == $limit) {
            print_error('negative_cycle', 'ratingallocate');
        }

        // If there is no path to $to, return null.
        if (is_null($preds[$to])) {
            return null;
        }

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

    public function get_name() {
        return "ford-fulkerson Koegel2014";
    }

    /**
     * Extracts a distribution/allocation from the graph.
     *
     * @param $touserid a map mapping from indexes in the graph to userids
     * @param $tochoiceid a map mapping from indexes in the graph to choiceids
     * @return array of the form array(groupid => array(userid, ...), ...)
     */
    protected function extract_allocation($touserid, $tochoiceid) {
        $distribution = array();
        foreach ($tochoiceid as $index => $groupid) {
            $group = $this->graph[$index];
            $distribution[$groupid] = array();
            foreach ($group as $assignment) {
                /* @var $assignment edge */
                $user = intval($assignment->to);
                if (array_key_exists($user, $touserid)) {
                    $distribution[$groupid][] = $touserid[$user];
                }
            }
        }
        return $distribution;
    }

    /**
     * Setup conversions between ids of users and choices to their node-ids in the graph
     * @param $usercount
     * @param $ratings
     * @return array($fromuserid, $touserid, $fromchoiceid, $tochoiceid);
     */
    public static function setup_id_conversions($usercount, $ratings) {
        // These tables convert userids to their index in the graph.
        // The range is [1..$usercount].
        $fromuserid = array();
        $touserid = array();
        // These tables convert choiceids to their index in the graph.
        // The range is [$usercount + 1 .. $usercount + $choicecount].
        $fromchoiceid = array();
        $tochoiceid = array();

        // User counter.
        $ui = 1;
        // Group counter.
        $gi = $usercount + 1;

        // Fill the conversion tables for group and user ids.
        foreach ($ratings as $rating) {
            if (!array_key_exists($rating->userid, $fromuserid)) {
                $fromuserid[$rating->userid] = $ui;
                $touserid[$ui] = $rating->userid;
                $ui++;
            }
            if (!array_key_exists($rating->choiceid, $fromchoiceid)) {
                $fromchoiceid[$rating->choiceid] = $gi;
                $tochoiceid[$gi] = $rating->choiceid;
                $gi++;
            }
        }

        return array($fromuserid, $touserid, $fromchoiceid, $tochoiceid);
    }

    /**
     * Sets up $this->graph
     * @param $choicecount
     * @param $usercount
     * @param $fromuserid
     * @param $fromchoiceid
     * @param $ratings
     * @param $choicedata
     * @param $source
     * @param $sink
     */
    protected function setup_graph($choicecount, $usercount, $fromuserid, $fromchoiceid, $ratings, $choicedata, $source,
                                   $sink, $weightmult = 1) {
        // Construct the datastructures for the algorithm.
        // A directed weighted bipartite graph.
        // A source is connected to all users with unit cost.
        // The users are connected to their choices with cost equal to their rating.
        // The choices are connected to a sink with 0 cost.
        $this->graph = array();
        // Add source, sink and number of nodes to the graph.
        $this->graph[$source] = array();
        $this->graph[$sink] = array();
        $this->graph['count'] = $choicecount + $usercount + 2;

        // Add users and choices to the graph and connect them to the source and sink.
        foreach ($fromuserid as $id => $user) {
            $this->graph[$user] = array();
            $this->graph[$source][] = new edge($source, $user, 0);
        }
        foreach ($fromchoiceid as $id => $choice) {
            $this->graph[$choice] = array();
            $this->graph[$choice][] = new edge($choice, $sink, 0, $choicedata[$id]->maxsize);
        }

        // Add the edges representing the ratings to the graph.
        foreach ($ratings as $id => $rating) {
            $user = $fromuserid[$rating->userid];
            $choice = $fromchoiceid[$rating->choiceid];
            $weight = $rating->rating;
            if ($weight > 0) {
                $this->graph[$user][] = new edge($user, $choice, $weightmult * $weight);
            }
        }
    }

    /**
     * Augments the flow in the network, i.e. augments the overall 'satisfaction'
     * by distributing users to choices
     * Reverses all edges along $path in $graph
     * @param $path path from t to s
     */
    protected function augment_flow($path) {
        if (is_null($path) or count($path) < 2) {
            print_error('invalid_path', 'ratingallocate');
        }

        // Walk along the path, from s to t.
        for ($i = count($path) - 1; $i > 0; $i--) {
            $from = $path[$i];
            $to = $path[$i - 1];
            $edge = null;
            $foundedgeid = -1;
            // Find the edge.
            foreach ($this->graph[$from] as $index => &$edge) {
                /* @var $edge edge */
                if ($edge->to == $to) {
                    $foundedgeid = $index;
                    break;
                }
            }
            // The second to last node in a path has to be a choice-node.
            // Reduce its space by one, because one user just got distributed into it.
            if ($i == 1 and $edge->space > 1) {
                $edge->space --;
            } else {
                // Remove the edge.
                array_splice($this->graph[$from], $foundedgeid, 1);
                // Add a new edge in the opposite direction whose weight has an opposite sign.
                // Array_push($this->graph[$to], new edge($to, $from, -1 * $edge->weight));
                // According to php doc, this is faster.
                $this->graph[$to][] = new edge($to, $from, -1 * $edge->weight);
            }
        }
    }

    /**
     * Expected return value is an array with min and opt as key and 1 or 0 as supported or not supported.
     * @return array
     */
    public static function get_supported_features() {
        return ['min' => 0, 'opt' => 0];
    }
}
