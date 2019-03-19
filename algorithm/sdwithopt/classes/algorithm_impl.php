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
 * @package    raalgo_sdwithopt
 * @copyright  2019 Wwu
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace raalgo_sdwithopt;

defined('MOODLE_INTERNAL') || die();

class algorithm_impl extends \mod_ratingallocate\algorithm {

    /** @var array */
    protected $globalranking;
    /** @var choice[] */
    protected $choices = array();
    /** @var choice[] */
    protected $closedchoices = array();
    /** @var array */
    protected $ratings = array();
    /** @var user[] */
    protected $users = array();
    /** @var int */
    protected $sumcountmissingplaces;
    /** @var int */
    protected $sumcountmovableplaces;
    /** @var int */
    protected $sumcountfreeplaces;

    public function get_name() {
        return 'sdwithopt';
    }

    /**
     * Computes the distribution of students to choices based on the students ratings.
     * @param $choicerecords array[] array of all choices which are ratable in this ratingallocate.
     * @param $ratings array[] array of all relevant ratings.
     * @param $raters array[] array of all raters in course.
     * @return array mapping of choice ids to array of user ids.
     */
    protected function compute_distribution($choicerecords, $ratings, $raters) {
        // minsize, maxsize, optional
        $this->choices = $choicerecords;
        $this->ratings = $ratings;
        $this->users = $raters;
        $this->check_feasibility();
        // Compute global ranking.
        $this->prepare_execution();

        do {
            $this->run_deferred_acceptance();
            $this->calculate_assignment_counts();
            if ($this->sumcountmissingplaces == 0) {
                // Found feasible solution.
                break;
            }
            if ($this->sumcountmissingplaces < $this->sumcountmovableplaces) {
                $this->reduce_choices_max_size($this->sumcountmissingplaces);
                continue;
            } else {
                $choicewasclosed = $this->close_optional_choice();
                if (!$choicewasclosed) {
                    // TODO: Add to log, that no feasible solution could be found.
                    break;
                }
            }
        } while (true);

        return $this->extract_allocation();
    }

    /**
     * Extracts the allocation from the current state of the system.
     */
    protected function extract_allocation() {
        $result = array();
        foreach ($this->closedchoices as $choice) {
            $result[$choice->id] = array();
        }
        foreach ($this->choices as $choice) {
            $result[$choice->id] = array();
            foreach ($choice->waitinglist as $userid) {
                $result[$choice->id][] = $userid;
            }
        }
        return $result;
    }

    /**
     * Runs the deferred acceptance algorithm on the current state.
     */
    protected function run_deferred_acceptance() {
        do {
            $this->application_by_students();
            $rejectionoccured = $this->rejection_by_choices();
        } while ($rejectionoccured);
    }

    /**
     * Calculate the counts of place assignments, after the deferred acceptance run.
     * Those variables will be used to decide upon next steps to take.
     */
    protected function calculate_assignment_counts() {
        $this->sumcountmissingplaces = 0;
        $this->sumcountmovableplaces = 0;
        $this->sumcountfreeplaces = 0;

        foreach ($this->choices as $choice) {
            if (count($choice->waitinglist) < $choice->minsize) {
                $choice->countmissingplaces = $choice->minsize - count($choice->waitinglist);
                $choice->countmoveableassignments = 0;
            } else {
                $choice->countmissingplaces = 0;
                $choice->countmoveableassignments = count($choice->waitinglist) - $choice->minsize;
            }
            $choice->countoptionalassignments = count($choice->waitinglist);
            $choice->countfreeplaces = $choice->maxsize - count($choice->waitinglist);

            // Fill global variables.
            $this->sumcountmissingplaces += $choice->countmissingplaces;
            $this->sumcountmovableplaces += $choice->countmoveableassignments;
            $this->sumcountfreeplaces += $choice->countfreeplaces;
        }
    }

    /**
     * Reduce randomly the maxsize of different choices, which are over min capacity level.
     * If a place is reduced, which is not filled by the current allocation, then the counter $sumcountmissingplaces
     * is not reduced.
     * @param $sumcountmissingplaces int number of filled places to be reduced.
     */
    protected function reduce_choices_max_size($sumcountmissingplaces) {
        $reduceablechoices = array_filter($this->choices, function ($choice) {
            return $choice->countmissingplaces == 0;
        });
        shuffle($reduceablechoices);
        while ($sumcountmissingplaces > 0) {
            $reducedatleastone = false;
            foreach ($reduceablechoices as $choice) {
                if ($choice->maxsize <= count($choice->waitinglist)) {
                    $sumcountmissingplaces--;
                }
                if ($choice->maxsize > 0 && $choice->maxsize > $choice->minsize) {
                    $choice->maxsize--;
                    $reducedatleastone = true;
                }
                if ($sumcountmissingplaces <= 0) {
                    break(2);
                }
            }
            if (!$reducedatleastone) {
                // TODO add to log.
                break;
            }
        }
    }

    /**
     * Tries to close a choice, which is optional and has less assignments,
     * than the number of free places in other chocies.
     * Closing sets the maxsize and minsize of the option to 0.
     * @return bool true, if a choice has been closed.
     */
    protected function close_optional_choice() {
        $sumcountfreeplaces = $this->sumcountfreeplaces;
        // Filter for all choices, which have less assignments than free places left in other choices.
        $closeablechoices = array_filter($this->choices, function(choice $choice) use ($sumcountfreeplaces) {
            return $choice->optional &&
                $choice->countoptionalassignments <= $sumcountfreeplaces - $choice->countfreeplaces;
        });
        if (empty($closeablechoices)) {
            // There is no choice, which we could close!
            return false;
        }
        // Sort choices in order to close the choice, which has the least assignments.
        uasort($closeablechoices, function(choice $choicea, choice $choiceb) {
            if ($choicea->countoptionalassignments == $choiceb->countoptionalassignments) {
                return 0;
            }
            return ($choicea->countoptionalassignments < $choiceb->countoptionalassignments) ? -1 : 1;
        });
        $choicetobeclosed = array_shift($closeablechoices);
        // Remove choice from set of existing choices.
        $this->closedchoices[] = $choicetobeclosed;
        unset($this->choices[$choicetobeclosed->id]);
        // Remove assignments from and to closed choice.
        $choicetobeclosed->waitinglist = [];
        foreach ($this->users as $user) {
            if ($user->currentchoice == $choicetobeclosed->id) {
                $user->currentchoice = null;
            }
        }
        return true;
    }

    /**
     * Students apply at the next choice at which they were not previously rejected.
     * The users preferencelist is shortened by the choice he/she applies to.
     * The waitinglist is directly ordered based on the global ranking.
     */
    protected function application_by_students() {
        foreach ($this->users as $user) {
            if (!$user->currentchoice && count($user->preferencelist) > 0) {
                $nextchoice = array_shift($user->preferencelist);
                $user->currentchoice = $nextchoice;
                $this->choices[$nextchoice]->waitinglist[$this->globalranking[$user->id]] = $user->id;
            }
        }
    }

    /**
     * Choices reject students based on their max size and the global ranking.
     * @return bool true if any choice did reject a student.
     */
    protected function rejection_by_choices() {
        $rejectionoccured = false;
        foreach ($this->choices as $choice) {
            ksort($choice->waitinglist);
            while (count($choice->waitinglist) > $choice->maxsize) {
                $userid = array_pop($choice->waitinglist);
                $this->users[$userid]->currentchoice = null;
                $rejectionoccured = true;
            }
        }
        return $rejectionoccured;
    }

    /**
     * Initializes the datatstructures needed for the algorithm.
     * - It creates the global ranking.
     * - It creates empty waiting lists for choices.
     * - It creates and fills the preferencelist for all users.
     */
    protected function prepare_execution() {
        // Compute global ranking.
        $userids = array_keys($this->users);
        shuffle($userids);
        $this->globalranking = array();
        $counter = 0;
        foreach ($userids as $userid) {
            $this->globalranking[$userid] = $counter++;
        }
        // Prepare waiting lists.
        foreach ($this->choices as $choice) {
            $choice->waitinglist = array();
        }
        // Prepare preference list of raters. TODO: Testfälle schreiben!
        foreach ($this->users as $user) {
            // TODO: Filter out ratings with 0 value.
            $ratingsofuser = array_filter($this->ratings, function ($rating) use ($user) {
                return $user->id == $rating->userid;
            });
            usort($ratingsofuser, function ($a, $b) {
                if ($a->rating == $b->rating) {
                    return 0;
                }
                return ($a->rating < $b->rating) ? -1 : 1;
            });
            $user->preferencelist = array();
            foreach ($ratingsofuser as $rating) {
                $user->preferencelist[] = $rating->choiceid;
            }
        }
    }

    /**
     * Checks the feasibility of the problem.
     * If the problem isn't feasible it is adjusted accordingly.
     */
    protected function check_feasibility () {
        $sumoflowerbounds = array_reduce($this->choices, function ($sum, $choice) {
            if (!$choice->optional) {
                return $sum + $choice->minsize;
            }
            return $sum;
        });
        $sumofupperbounds = array_reduce($this->choices, function ($sum, $choice) {
            return $sum + $choice->maxsize;
        });
        $usercount = count($this->users);
        if ($usercount < $sumoflowerbounds) {
            throw new \Exception("unfeasible problem");
        }
        if ($usercount > $sumofupperbounds) {
            throw new \Exception("unfeasible problem");
        }
    }

}
