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
 * @copyright  2014 Max Schulze
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(__FILE__) . '/../../../config.php'); // to include $CFG, for example
require_once(dirname(__FILE__) . '/../locallib.php');

$id = required_param('id', PARAM_INT); // course_module ID, or
$action = optional_param('action', '', PARAM_ACTION);

if ($id) {
    $cm = get_coursemodule_from_id('ratingallocate', $id, 0, false, MUST_EXIST);
    $course = get_course($cm->course);
    $ratingallocate = $DB->get_record('ratingallocate', array(
        'id' => $cm->instance
            ), '*', MUST_EXIST);
} else {
    error('You must specify a course_module ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);
require_capability('mod/ratingallocate:export_ratings', $context);

/* @var $ratingallocateobj ratingallocate */
$ratingallocateobj = new ratingallocate($ratingallocate, $course, $cm, $context);

/**
 * Eine beim csv_export_writer abgeschaute Klasse, die in Dateien schreiben kann und zum Download anbieten.
 * @copyright (c) 2014, M Schulze
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class lp_export_write {
    /** @var $filename path to write file */
    protected $filename;

    /** @var string $path The directory path for storing the temporary csv file. */
    protected $path;

    /** @var resource $fp File pointer for the csv file. */
    protected $fp;

    /**
     * Constructor for the csv export reader
     *
     * @param string $delimiter The name of the character used to seperate fields. Supported
     *        types(comma, tab, semicolon, colon, cfg)
     * @param string $enclosure The character used for determining the enclosures.
     * @param string $mimetype Mime type of the file that we are exporting.
     */
    public function __construct($mimetype = 'application/download') {
        $this->filename = "Moodle-lp_solve-export.txt";
        $this->mimetype = $mimetype;
    }

    /**
     * Set the file path to the temporary file.
     */
    protected function set_temp_file_path() {
        global $USER, $CFG;
        make_temp_directory('ratingallocate/' . $USER->id);
        $path = $CFG->tempdir . '/ratingallocate/' . $USER->id . '/' . $this->filename;
        // Check to see if the file exists, if so delete it.
        if (file_exists($path)) {
            unlink($path);
        }
        $this->path = $path;
    }

    /**
     * Add data to the temporary file in csv format
     *
     * @param array $row An array of values.
     */
    public function add_line($row) {
        if (!isset($this->path)) {
            $this->set_temp_file_path();
            $this->fp = fopen($this->path, 'w+');
        }
        fputs($this->fp, $row, strlen($row));
    }

    /**
     * Echos or returns a file data line by line for displaying.
     *
     * @param bool $return Set to true to return a string with the csv data.
     * @return string csv data.
     */
    public function print_csv_data($return = false) {
        fseek($this->fp, 0);
        $returnstring = '';
        while (($content = fgets($this->fp)) !== false) {
            if (!$return) {
                echo $content;
            } else {
                $returnstring .= $content;
            }
        }
        if ($return) {
            return $returnstring;
        }
    }

    /**
     * Set the filename for the uploaded csv file
     *
     * @param string $dataname The name of the module.
     * @param string $extenstion File extension for the file.
     */
    public function set_filename($dataname, $extension = '.txt') {
        $filename = clean_filename($dataname);
        $filename .= clean_filename('-' . gmdate("Ymd_Hi"));
        $filename .= $extension;
        $this->filename = $filename;
    }

    /**
     * Output file headers to initialise the download of the file.
     */
    protected function send_header() {
        global $CFG;
        if (strpos($CFG->wwwroot, 'https://') === 0) { // https sites - watch out for IE! KB812935 and KB316431
            header('Cache-Control: max-age=10');
            header('Pragma: ');
        } else { // normal http - prevent caching at all cost
            header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
            header('Pragma: no-cache');
        }
        header('Expires: ' . gmdate('D, d M Y H:i:s', 0) . ' GMT');
        header("Content-Type: $this->mimetype\r\n");
        header("Content-Disposition: attachment; filename=\"$this->filename\"");
    }

    /**
     * Download the csv file.
     */
    public function download_file() {
        $this->send_header();
        $this->print_csv_data();
        exit();
    }

    /**
     * Make sure that everything is closed when we are finished.
     */
    public function __destruct() {
        fclose($this->fp);
        // unlink ( $this->path );
    }

}

global $DB;

$writer = new lp_export_write ();
$writer->set_filename("lp_solve_export_course_" . $ratingallocate->id);

$choices = $ratingallocateobj->get_rateable_choices();

$ratings = $ratingallocateobj->get_ratings_for_rateable_choices();
$ratingscells = array();
foreach ($ratings as $rating) {
    if (!array_key_exists($rating->userid, $ratingscells)) {
        $ratingscells [$rating->userid] = array();
    }
    $ratingscells [$rating->userid] [$rating->choiceid] = $rating->rating;
}

$zielfkt = 'max '; // die zu maximierende Zielfunktion
$usernb = ''; // Stelle die NB pro User auf, dass er nur in eine Wahl kommt
$variablenerkl = '';
$nbkurs = array(); // nebenbedingungen pro Kurs
foreach ($ratingscells as $userid => $userrating) {
    $variablenerkl .= 'bin';
    $nbkursakt = '';
    foreach ($userrating as $choiceid => $rating) {
        $usercoursevar = 'u' . $userid . '_c' . $choiceid;
        if ($rating > 0) {   // wenn der Nutzer das absolut nicht will,darf er da auch nicht rein kommen
            $usernb .= '+' . $usercoursevar . ' ';
        }
        $zielfkt .= '+' . $rating . '*' . $usercoursevar;
        $variablenerkl .= ' ' . $usercoursevar . ',';

        $nbkursakt .= ' +' . $usercoursevar;
    }
    $nbkurs [$choiceid] = $nbkursakt;
    $variablenerkl = substr($variablenerkl, 0, strlen($variablenerkl) - 1); // strip komma
    $variablenerkl .= ";\r\n";
    $usernb .= " = 1;\r\n";
}

// Nebenbedingungen pro Kurs abschließen
foreach ($nbkurs as $choiceid => $nbeinkurs) {
    $nbkurs [$choiceid] .= '<=' . $choices [$choiceid]->maxsize . ";\r\n";
}

$zielfkt .= ";\r\n";
$writer->add_line('/* export generated by ratingallocate. Date '.gmdate("Ymd_Hi")."*/\r\n");
$writer->add_line($zielfkt);
$writer->add_line("/* waehlende-nb*/\r\n");
$writer->add_line($usernb);
$writer->add_line("/* kurs-nb, damit nicht ueberbelegt*/\r\n");
foreach ($nbkurs as $zeile) {
    $writer->add_line($zeile);
}
$writer->add_line("\r\n");
$writer->add_line("/* deklarierung variablen*/\r\n");
$writer->add_line($variablenerkl);

if ($action == ACTION_SOLVE_LP_SOLVE) {
    $command = "lp_solve $writer->path >" . dirname($writer->path) . "/ausgabe.txt\r\n";
    $output = exec($command);

    if ($return != 0) {
        // an error occurred
        print_erro("error");
    }
}

$writer->download_file();

