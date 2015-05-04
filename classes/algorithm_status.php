<?php
namespace mod_ratingallocate;
/**
* The different status an ratingallocate object can be in according to its algorithm run.
*
* @package    mod_ratingallocate
* @copyright  2015 Tobias Reischmann
* @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/
class algorithm_status{
    const failure = -1; // Algorithm did not finish correctly
    const notstarted = 0; // Default status for new instances
    const running = 1; // Algorithm is currently running
    const finished = 2; // Algorithm finished correctly
}
