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
 * Choice importer.
 *
 * @package    mod_ratingallocate
 * @copyright  2021 Catalyst IT
 * @author     David Thompson <david.thompson@catalyst.net.nz>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_ratingallocate;
use mod_ratingallocate\db as this_db;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/csvlib.class.php');

/**
 * Choice importer.
 *
 * @package mod_ratingallocate
 */
class choice_importer {
    /**
     * Required fields.
     */
    const REQUIRED_FIELDS = ["title", "explanation", "maxsize", "active", "groups"];

    /**
     * The import process worked as expected.
     */
    const IMPORT_STATUS_OK = 'csvupload_ok';
    /**
     * Something went wrong during setup; import cannot continue.
     */
    const IMPORT_STATUS_SETUP_ERROR = 'csvupload_setuperror';
    /** Partial success with data errors.*/
    const IMPORT_STATUS_DATA_ERROR = 'csvupload_dataerror';

    /** Default maximum number of warnings to show notifications for on import problems. */
    const MAX_WARNING_COUNT = 5;

    /**
     * @var \csv_import_reader
     */
    private $reader;

    /**
     * @var \ratingallocate
     */
    private $ratingallocate;

    /**
     * @var int
     */
    private $ratingallocateid;

    /**
     * Output a list of required (or missing) fields.
     *
     * @param array $fields
     * @return void
     */
    public static function print_fields($fields=self::REQUIRED_FIELDS) {
        return '[' . join(', ', $fields) . ']';
    }

    /**
     * Construct.
     *
     * @param int $ratingallocateid
     * @param \ratingallocate $ratingallocate
     */
    public function __construct($ratingallocateid, $ratingallocate) {
        $this->ratingallocate = $ratingallocate;
        $this->ratingallocateid = $ratingallocateid;
    }

    /**
     * Destruct.
     */
    public function __destruct() {
        $this->free_reader();
    }

    /**
     * Get active CSV import reader, setting up temporary dir as necessary.
     */
    public function get_reader() {
        if (!$this->reader) {
            $iid = \csv_import_reader::get_new_iid('modratingallocate');
            $this->reader = new \csv_import_reader($iid, 'modratingallocate');
        }

        return $this->reader;
    }

    /**
     * Release CSV import reader in current use.
     */
    public function free_reader() {
        if ($this->reader) {
            $this->reader->cleanup();
            $this->reader->close();
            $this->reader = null;
        }
    }

    /**
     * Import choice data
     *
     * @param string $content The content to parse.
     * @param bool $live Commit when live; only test otherwise.
     *
     * @return array Import status information.
     *
     * The return array includes:
     * - status: IMPORT_STATUS_xxx constants
     * - status_message: Human-readable status explanation.
     * - errors[]: Any specific error messages
     * - readcount: The number of rows read.
     * - rowcount: The number of being row processed. (Once finished, should add up to readcount.)
     * - importcount: The number of rows successfully processed.
     */
    public function import($content, $live=true) {
        global $DB;

        $reader = $this->get_reader();

        $importstatus = new \stdClass;
        $importstatus->status = self::IMPORT_STATUS_OK;  // Unless we hear otherwise.
        $importstatus->live = $live;  // Only commit live transactions.
        $importstatus->errors = [];
        $importstatus->importcount = 0;
        $importstatus->rowcount = 1;  // Start at 1 for header.

        $importstatus->readcount = $reader->load_csv_content($content, 'UTF-8', 'comma');
        $importerror = $reader->get_error();

        if ($importerror) {
            $importstatus->status = self::IMPORT_STATUS_SETUP_ERROR;
            $importstatus->errors[] = $importerror;
        } else {
            if (empty($importstatus->readcount)) {
                $importstatus->status = self::IMPORT_STATUS_DATA_ERROR;
                $importstatus->errors[] = get_string('csvempty', 'ratingallocate');
            } else {
                // Trim whitespace on headers, just in case.
                if (!$fieldnames = array_map('trim', $reader->get_columns())) {
                    throw new moodle_exception('cannotreadtmpfile', 'error');
                }

                // Check for any missing required fields.
                $missingfields = array_diff(self::REQUIRED_FIELDS, $fieldnames);
                if ($missingfields) {
                    $importstatus->status = self::IMPORT_STATUS_SETUP_ERROR;
                    $importstatus->errors[] = get_string('csvupload_missing_fields', 'ratingallocate',
                        self::print_fields($missingfields));
                    return $importstatus;
                }

                // Map group names to group IDs.
                $allgroups = $this->ratingallocate->get_group_selections();
                $groupidmap = array_flip($allgroups);

                // Start DB transaction.
                $transaction = $this->ratingallocate->db->start_delegated_transaction();
                try {

                    $reader->init();
                    while ($record = $reader->next()) {
                        $importstatus->rowcount++;
                        // Unless we hear otherwise.
                        $ischoiceimportable = true;
                        $recordmap = new \stdClass();

                        // Map cell contents to field names.
                        foreach ($record as $col => $cell) {
                            $fieldname = $fieldnames[$col];

                            // Skip non-required field columns.
                            if (!in_array($fieldname, self::REQUIRED_FIELDS)) {
                                continue;
                            }

                            if ($fieldname === 'title' && mb_strlen($cell, 'UTF-8') > 255) {
                                $importstatus->status = self::IMPORT_STATUS_DATA_ERROR;
                                $importstatus->errors[] = get_string('csvupload_too_long_title',
                                        RATINGALLOCATE_MOD_NAME, $cell);
                                $ischoiceimportable = false;
                            }

                            if ($fieldname == 'groups') {
                                $groups = [];

                                // Turn off 'usegroups' if no groups specified.
                                if (empty(trim($cell))) {
                                    $recordmap->usegroups = false;
                                } else {
                                    // For fault tolerance, trim any surrounding whitespace.
                                    $cell = trim($cell);
                                    $partsbysemicolon = explode(';', $cell);
                                    $partsbycomma = explode(',', $cell);
                                    $parts = count($partsbysemicolon) >= count($partsbycomma) ? $partsbysemicolon : $partsbycomma;
                                    foreach ($parts as $part) {
                                        $part = trim($part);
                                        if (!empty($part)) {
                                            $groups[] = trim($part);
                                        }
                                    }
                                    $recordmap->usegroups = true;
                                    $recordmap->_groups = $groups;
                                }
                            } else {
                                $recordmap->{$fieldname} = $cell;
                            }
                        }

                        if (!$ischoiceimportable) {
                            // Parsed data of this choice has critical errors, so we will not add the choice.
                            continue;
                        }

                        // Create and insert a choice record.
                        // Note: this will add duplicates if run multiple times.
                        $choice = new \ratingallocate_choice($recordmap);
                        $choice->{this_db\ratingallocate_choices::RATINGALLOCATEID} = $this->ratingallocateid;

                        $recordmap->id = $DB->insert_record(this_db\ratingallocate_choices::TABLE, $choice->dbrecord);

                        // If groups are used, update choice groups.
                        if ($recordmap->usegroups) {
                            $validgroups = array_intersect($recordmap->_groups, $allgroups);
                            $invalidgroups = array_diff($recordmap->_groups, $validgroups);

                            // Non-fatal error: groups found that aren't valid for this course context.
                            // Note for warnings.
                            if (!empty($invalidgroups)) {
                                $importstatus->status = self::IMPORT_STATUS_DATA_ERROR;
                                $warningmessage = get_string('csvupload_missing_groups', 'ratingallocate', [
                                    'row' => $importstatus->rowcount,
                                    'invalidgroups' => join(', ', $invalidgroups),
                                ]);
                                $importstatus->errors[] = $warningmessage;
                            }

                            // Insert valid group choices.
                            foreach ($validgroups as $groupname) {
                                $record = new \stdClass();
                                $record->choiceid = $recordmap->id;
                                $record->groupid = $groupidmap[$groupname];
                                $DB->insert_record('ratingallocate_group_choices', $record);
                            }
                        }
                        $importstatus->importcount++;
                    }

                    if ($live) {
                        // Commit it once we're happy it's working.
                        $transaction->allow_commit();
                    }
                    $transaction->dispose();

                } catch (\Exception $e) {
                    if (isset($transaction)) {
                        $transaction->rollback($e);
                    }
                }

            }
        }

        $this->free_reader();

        // Determine main status message.
        if ($importstatus->status == self::IMPORT_STATUS_OK) {
            if ($live) {
                $importstatus->status_message = get_string('csvupload_live_success', 'ratingallocate', $importstatus);
            } else {
                $importstatus->status_message = get_string('csvupload_test_success', 'ratingallocate', $importstatus);
            }
        } else {
            if ($live) {
                $importstatus->status_message = get_string('csvupload_live_problems', 'ratingallocate',
                    count($importstatus->errors)
                );
            } else {
                $importstatus->status_message = get_string('csvupload_test_problems', 'ratingallocate',
                    count($importstatus->errors)
                );
            }
        }

        return $importstatus;
    }

    /**
     * Issue notifications from a CSV import when there are errors.
     *
     * A sanity measure for large broken CSVs: if there are a lot of errors, it
     * will only notify on up to the first $max items, then say how many more
     * remain.
     *
     * @param array $errors
     * @param \core\output\notification $notificationtype Notification type to use.
     * @param int $max Maximum number of individual notifications to send.
     * @return void
     */
    public function issue_notifications($errors,
        $notificationtype=\core\output\notification::NOTIFY_WARNING,
        $max=self::MAX_WARNING_COUNT
    ) {
        $errorcount = count($errors);

        if ($errorcount == 0) {
            return;  // Exit early if nothing to do.
        }

        $notifyerrors = array_slice($errors, 0, $max);

        foreach ($notifyerrors as $error) {
            \core\notification::add($error, $notificationtype);
        }
        if ($errorcount > $max) {
            $extra = get_string('csvupload_further_problems', 'ratingallocate', ($errorcount - $max));
            \core\notification::add($extra, $notificationtype);
        }
    }
}
