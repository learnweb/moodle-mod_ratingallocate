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
 * Contains the algorithm for the group distribution and some helper functions
 * that wrap useful SQL querys.
 *
 * @package    mod_ratingallocate
 * @subpackage mod_ratingallocate
 * @copyright  2014 M Schulze, C Usener
 * @copyright  based on code by Stefan Koegel copyright (C) 2013 Stefan Koegel
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Represents an Edge in the graph to have fixed fields instead of array-fields
 */
defined('MOODLE_INTERNAL') || die();

class edge {
    /** @var from int */
    public $from;
    /** @var to int */
    public $to;
    /** @var weight int Cost for this edge (rating of user) */
    public $weight;
    /** @var space int (places left for choices) */
    public $space;

    public function __construct($from, $to, $weight, $space = 0) {
        $this->from = $from;
        $this->to = $to;
        $this->weight = $weight;
        $this->space = $space;
    }

}

/**
 * Template Class for distribution algorithms
 */
class distributor {

    /** @var $graph Flow-Graph built */
    protected $graph;

    /**
     * Compute the 'satisfaction' functions that is to be maximized by adding the
     * ratings users gave to their allocated choices
     * @param array $ratings
     * @param array $distribution
     * @return integer
     */
    public static function compute_target_function($ratings, $distribution) {
        $functionvalue = 0;
        foreach ($distribution as $choiceid => $choice) {
            // Variable $choice is now an array of userids.
            foreach ($choice as $userid) {
                // Find the right rating.
                foreach ($ratings as $rating) {
                    if ($rating->userid == $userid && $rating->choiceid == $choiceid) {
                        $functionvalue += $rating->rating;
                        continue;
                    }
                }
            }
        }
        return $functionvalue;
    }

    /**
     * Entry-point for the \ratingallocate object to call a solver
     * @param \ratingallocate $ratingallocate
     */
    public function distribute_users(\ratingallocate $ratingallocate) {

        $teamvote = $ratingallocate->get_teamvote_goups();

        // Load data from database.
        $choicerecords = $ratingallocate->get_rateable_choices();
        $ratings = $ratingallocate->get_ratings_for_rateable_choices();

        // Randomize the order of the entries to prevent advantages for early entry.
        shuffle($ratings);

        $usercount = count($ratingallocate->get_raters_in_course());

        $distributions = $this->compute_distribution($choicerecords, $ratings, $usercount, $teamvote);

        // Perform all allocation manipulation / inserts in one transaction.
        $transaction = $ratingallocate->db->start_delegated_transaction();

        $ratingallocate->clear_all_allocations();

        $userdistributions = array();

        if (!$teamvote) {
            $userdistributions = $distributions;
        } else {
            // Map choiceids to every user of the team it is mapped to.
            $userids = array();
            foreach ($distributions as $choiceid => $teamids) {
                foreach ($teamids as $teamid) {
                    $userids[$teamid] = groups_get_members($teamid, 'u.id');
                }
                $userdistributions[$choiceid] = array_merge($userids);
            }

            // We have to delete the provisionally groups containing only one user.
            $ratingallocate->delete_groups_for_usersnogroup($teamvote);

        }

        foreach ($userdistributions as $choiceid => $users) {
            foreach ($users as $userid) {
                $ratingallocate->add_allocation($choiceid, $userid, $ratingallocate->ratingallocate->id);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Extracts a distribution/allocation from the graph.
     *
     * @param $touserid a map mapping from indexes in the graph to userids (or teamids)
     * @param $tochoiceid a map mapping from indexes in the graph to choiceids
     * @return an array of the form array(groupid => array(userid, ...), ...)
     */
    protected function extract_allocation($touserid, $tochoiceid) {
        $distribution = array();
        foreach ($tochoiceid as $index => $groupid) {
            $group = $this->graph[$index];
            $distribution[$groupid] = array();
            foreach ($group as $assignment) {
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
     * @param type $usercount
     * @param type $ratings
     * @return array($fromuserid, $touserid, $fromchoiceid, $tochoiceid);
     */
    public static function setup_id_conversions($usercount, $ratings) {
        // These tables convert userids to their index in the graph
        // The range is [1..$usercount].
        $fromuserid = array();
        $touserid = array();
        // These tables convert choiceids to their index in the graph
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
     * Setup conversions between ids of teams and choices to their node-ids in the graph
     * @param type $teamcount
     * @param type $ratings
     * @return array($fromteamid, $toteamid, $fromchoiceid, $tochoiceid);
     */
    public static function setup_id_conversions_for_teamvote($teamcount, $ratings) {
        // These tables convert teamids to their index in the graph
        // The range is [1..$teamcount].
        $fromteamid = array();
        $toteamid = array();
        // These tables convert choiceids to their index in the graph
        // The range is [$teamcount + 1 .. $teamcount + $choicecount].
        $fromchoiceid = array();
        $tochoiceid = array();

        // Team counter.
        $ti = 1;
        // Group counter.
        $gi = $teamcount + 1;

        // Fill the conversion tables for group and team ids.
        foreach ($ratings as $rating) {
            if (!array_key_exists($rating->groupid, $fromteamid)) {
                $fromteamid[$rating->groupid] = $ti;
                $toteamid[$ti] = $rating->groupid;
                $ti++;
            }
            if (!array_key_exists($rating->choiceid, $fromchoiceid)) {
                $fromchoiceid[$rating->choiceid] = $gi;
                $tochoiceid[$gi] = $rating->choiceid;
                $gi++;
            }
        }

        return array($fromteamid, $toteamid, $fromchoiceid, $tochoiceid);
    }

    /**
     * Sets up $this->graph
     * @param type $choicecount
     * @param type $usercount
     * @param type $fromuserid
     * @param type $fromchoiceid
     * @param type $ratings
     * @param type $choicedata
     * @param type $source
     * @param type $sink
     */
    protected function setup_graph($choicecount, $usercount, $fromuserid, $fromchoiceid, $ratings, $choicedata, $source, $sink,
            $weightmult = 1) {
        // Construct the datastructures for the algorithm
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
            if ($choicedata[$id]->maxsize > 0) {
                $this->graph[$choice][] = new edge($choice, $sink, 0, $choicedata[$id]->maxsize);
            }
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
     * Sets up $this->graph
     * @param type $choicecount
     * @param type $teamcount
     * @param type $fromteamid
     * @param type $fromchoiceid
     * @param type $ratings
     * @param type $choicedata
     * @param type $source
     * @param type $sink
     */
    protected function setup_graph_for_teamvote($choicecount, $teamcount, $fromteamid, $fromchoiceid, $ratings, $choicedata, $source, $sink,
                                   $weightmult = 1) {
        // Construct the datastructures for the algorithm
        // A directed weighted bipartite graph.
        // A source is connected to all users with unit cost.
        // The teams are connected to their choices with cost equal to their rating.
        // The choices are connected to a sink with 0 cost.
        $this->graph = array();
        // Add source, sink and number of nodes to the graph.
        $this->graph[$source] = array();
        $this->graph[$sink] = array();
        $this->graph['count'] = $choicecount + $teamcount + 2;

        // Add teams and choices to the graph and connect them to the source and sink.
        foreach ($fromteamid as $id => $team) {
            $this->graph[$team] = array();
            $this->graph[$source][] = new edge($source, $team, 0);
        }
        foreach ($fromchoiceid as $id => $choice) {
            $this->graph[$choice] = array();
            if ($choicedata[$id]->maxsize > 0) {
                $this->graph[$choice][] = new edge($choice, $sink, 0, $choicedata[$id]->maxsize);
            }
        }

        // Add the edges representing the ratings to the graph.
        foreach ($ratings as $id => $rating) {
            $team = $fromteamid[$rating->groupid];
            $choice = $fromchoiceid[$rating->choiceid];
            $weight = $rating->rating;
            if ($weight > 0) {
                $this->graph[$team][] = new edge($team, $choice, $weightmult * $weight);
            }
        }

        // Andere Kanten noch entsprechend groupmembers gewichten?
    }

    /**
     * Augments the flow in the network, i.e. augments the overall 'satisfaction'
     * by distributing users (or teams) to choices
     * Reverses all edges along $path in $graph
     * @param type $path path from t to s
     * @throws moodle_exception
     */
    protected function augment_flow($path, $teamvote=false, $toteamid=null) {
        if (is_null($path) || count($path) < 2) {
            throw new \moodle_exception('invalid_path', 'ratingallocate');
        }

        // Walk along the path, from s to t.
        for ($i = count($path) - 1; $i > 0; $i--) {
            $from = $path[$i];
            $to = $path[$i - 1];
            $edge = null;
            $foundedgeid = -1;
            // Find the edge.
            foreach ($this->graph[$from] as $index => &$edge) {
                if ($edge->to == $to) {
                    $foundedgeid = $index;
                    break;
                }
            }
            // The second to last node in a path has to be a choice-node.
            // The node before that has to be a teamnode (or usernode), distribute them to this choice.
            if (!$teamvote) {
                // If teamvote=false, reduce its space by one, because one user just got distributed into it.
                $space = 1;
            } else {
                // If teamvote is enabled, reduce its space by amount of groupmembers.
                $space = $teamvote[$toteamid[$path[$i + 1]]];
            }

            if ($i == 1 && $edge->space > $space) {
                $edge->space = $edge->space - $space;
            } else {
                // Remove the edge.
                array_splice($this->graph[$from], $foundedgeid, 1);
                // Add a new edge in the opposite direction whose weight has an opposite sign
                // array_push($this->graph[$to], new edge($to, $from, -1 * $edge->weight));
                // according to php doc, this is faster.
                $this->graph[$to][] = new edge($to, $from, -1 * $edge->weight);
            }
        }
    }

}
