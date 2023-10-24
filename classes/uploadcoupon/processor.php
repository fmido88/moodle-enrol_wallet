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
 * Library of functions for uploading a course enrolment methods CSV file.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\uploadcoupon;
use enrol_wallet\uploadcoupon\tracker;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot.'/admin/tool/uploaduser/locallib.php');

/**
 * Validates and processes files for uploading a course enrolment methods CSV file
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor {

    /** @var csv_import_reader */
    protected $cir;

    /** @var array CSV columns. */
    protected $columns = array();

    /** @var int line number. */
    protected $linenb = 0;

    /** @var string allowed operations. */
    protected $allowed;

    /**
     * Constructor, sets the CSV file reader
     *
     * @param \csv_import_reader $cir import reader object
     * @param string $allowed What operation is allowed (all, updated, create)
     */
    public function __construct(\csv_import_reader $cir, $allowed = 'all') {
        $this->cir = $cir;
        $this->columns = $cir->get_columns();
        $this->allowed = $allowed;
        $this->validate();
        $this->reset();
        $this->linenb++;
    }

    /**
     * Processes the file to handle the enrolment methods
     *
     * Opens the file, loops through each row. Cleans the values in each column,
     * checks that the operation is valid and the methods exist. If all is well,
     * adds, updates or deletes the enrolment method metalink in column 3 to/from the course in column 2
     * context as specified.
     * Returns a report of successes and failures.
     *
     * @see open_file()
     * @uses enrol_meta_sync() Meta plugin function for syncing users
     * @return string A report of successes and failures.
     *
     * @param object $tracker the output tracker to use.
     * @return void
     */
    public function execute($tracker = null) {
        global $DB;
        $context = \context_system::instance();
        if (!has_capability('enrol/wallet:createcoupon', $context)
         || !has_capability('enrol/wallet:editcoupon', $context)) {
            return;
        }

        if (empty($tracker)) {
            $tracker = new tracker(tracker::NO_OUTPUT);
        }

        // Initialize the output heading row labels.
        $reportheadings = [
            'line'       => get_string('csvline', 'tool_uploadcourse'),
            'id'         => 'id',
            'code'       => get_string('coupon_code', 'enrol_wallet'),
            'type'       => get_string('coupon_type', 'enrol_wallet'),
            'value'      => get_string('coupon_value', 'enrol_wallet'),
            'category'   => get_string('category'),
            'courses'    => get_string('courses'),
            'maxusage'   => get_string('coupons_maxusage', 'enrol_wallet'),
            'maxperuser' => get_string('coupons_maxperuser', 'enrol_wallet'),
            'validfrom'  => get_string('validfrom', 'enrol_wallet'),
            'validto'    => get_string('validto', 'enrol_wallet'),
            'result'     => get_string('upload_result', 'enrol_wallet')
        ];
        $tracker->start($reportheadings, true);

        // Initialize some counters to summaries the results.
        $total = 0;
        $created = 0;
        $updated = 0;
        $errors = 0;

        // We will most certainly need extra time and memory to process big files.
        \core_php_time_limit::raise();
        raise_memory_limit(MEMORY_EXTRA);

        // Loop through each row of the file.
        while ($line = $this->cir->next()) {
            $this->linenb++;
            $total++;
            // Prepare reporting message strings.
            $messagerow = [];
            $coupondata = new \stdClass;
            // Read in and process one data line from the CSV file.
            $data = $this->parse_line($line);

            foreach ($data as $k => $v) {
                unset($data[$k]);
                $k = str_replace([' ', '/', '.', '?'], '', $k);
                $k = strtolower($k);

                if ($k == 'maximumusage') {
                    $k = 'maxusage';
                } else if ($k == 'maximumusageuser') {
                    $k = 'maxperuser';
                }

                $data[$k] = $v;
            }

            $id = !empty($data['id']) ? $data['id'] : null;
            if (!empty($id)) {
                $coupondata->id = $id;
            }

            $code = $data['code'] ?? null;
            $coupondata->code = $code;

            $value = !empty($data['value']) ? $data['value'] : 0;
            $coupondata->value = $value;

            $type = $data['type'] ?? null;
            if (empty($type) && !empty($value)) {
                $type = 'fixed';
            }
            if (!empty($type)) {
                $coupondata->type = $type;
            }
            $category = $data['category'] ?? null;
            if ($type == 'category' && !is_number($category)) {
                $categoryid = $DB->get_field('course_categories', 'id', ['name' => $category]);
                if (empty($categoryid)) {
                    $categoryid = $DB->get_field('course_categories', 'id', ['idnumber' => $category]);
                }
                if (!empty($categoryid)) {
                    $category = $categoryid;
                } else {
                    $category = null;
                }
            }
            $coupondata->category = $category;

            $courses = $data['courses'] ?? 0;
            if (!empty($courses) && $type == 'enrol') {
                $shortnames = explode('/', $courses);
                $courses = [];
                if ($shortnames) {
                    foreach ($shortnames as $shortname) {
                        $courseid = $DB->get_field('course', 'id', ['shortname' => $shortname]);
                        if (!$courseid) {
                            $courseid = $DB->get_field('course', 'id', ['idnumber' => $shortname]);
                        }
                        if ($courseid) {
                            $courses[] = $courseid;
                        }
                    }
                    $courses = implode(',', $courses);
                }
            }
            if (!empty($courses)) {
                $coupondata->courses = $courses;
            }

            $maxusage = $data['maxusage'] ?? null;
            $coupondata->maxusage = $maxusage;

            $maxperuser = $data['maxperuser'] ?? null;
            $coupondata->maxperuser = $maxperuser;

            $validfrom = $data['validfrom'] ?? null;
            if (!empty($validfrom) && !is_number($validfrom)) {
                $validfrom = strtotime($validfrom);
            }
            $coupondata->validfrom = $validfrom;

            $validto = $data['validto'] ?? null;
            if (!empty($validto) && !is_number($validto)) {
                $validto = strtotime($validto);
            }
            $coupondata->validto = $validto;

            $messagerow = [
                'line'       => $this->linenb,
                'id'         => $id ?? '',
                'code'       => $code,
                'value'      => $value ?? '',
                'courses'    => $courses ?? '',
                'category'   => $category ?? '',
                'type'       => $type,
                'maxusage'   => $maxusage,
                'maxperuser' => $maxperuser,
                'validfrom'  => !empty($validfrom) ? userdate($validfrom) : '',
                'validto'    => !empty($validto) ? userdate($validto) : '',
            ];

            // Need to check the line is valid. If not, add a message to the report and skip the line.
            // Check the type of the code.
            if (empty('type') || !in_array($type, ['fixed', 'percent', 'enrol', 'category'])) {
                $errors++;
                $messagerow['result'] = get_string('coupon_invalidtype', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }
            // Check if there is a code.
            if (empty('code')) {
                $errors++;
                $messagerow['result'] = get_string('coupon_nocode', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }
            // Check if there is no value.
            if (empty($value) && $type != 'enrol') {
                $errors++;
                $messagerow['result'] = get_string('coupons_valueerror', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }

            if ($type == 'enrol' && empty($courses)) {
                $errors++;
                $messagerow['result'] = get_string('coupons_courseserror', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }

            if ($type == 'category' && empty($category)) {
                $errors++;
                $messagerow['result'] = get_string('coupons_category_error', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }

            if ($type == 'percent' && $value > 100) {
                $errors++;
                $messagerow['result'] = get_string('invalidpercentcoupon', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }

            if (!empty($maxperuser) && $maxperuser > $maxusage) {
                $errors++;
                $messagerow['result'] = get_string('coupon_generator_peruser_gt_max', 'enrol_wallet');
                $tracker->output($messagerow, false);
                continue;
            }

            if (!empty($id)) {
                $oldrecord = $DB->get_record('enrol_wallet_coupons', ['id' => $id, 'code' => $code]);
                if (!$oldrecord) {
                    $errors++;
                    $messagerow['result'] = get_string('coupon_invalidid', 'enrol_wallet');
                    $tracker->output($messagerow, false);
                    continue;
                }
            } else {
                $oldrecord = $DB->get_record('enrol_wallet_coupons', ['code' => $code]);
            }

            if ($oldrecord) {
                foreach ($coupondata as $key => $value) {
                    if (is_null($value)) {
                        unset($coupondata->$key);
                    }
                }
                $coupondata->id = $oldrecord->id;
                if ($DB->update_record('enrol_wallet_coupons', $coupondata)) {
                    $updated++;
                    $messagerow['result'] = get_string('coupon_update_success', 'enrol_wallet');
                    $tracker->output($messagerow, true);
                } else {
                    $errors++;
                    $messagerow['result'] = get_string('coupon_update_failed', 'enrol_wallet');
                    $tracker->output($messagerow, false);
                }
            } else {
                foreach ($coupondata as $key => $value) {
                    if (is_null($value)) {
                        if (in_array($key, ['validfrom', 'validto', 'maxperuser'])) {
                            $coupondata->$key = 0;
                        } else if ($key == 'maxusage') {
                            $coupondata->$key = 1;
                        }
                    }
                }
                if ($DB->insert_record('enrol_wallet_coupons', $coupondata)) {
                    $created++;
                    $messagerow['result'] = get_string('coupons_generation_success', 'enrol_wallet', 1);
                    $tracker->output($messagerow, true);
                } else {
                    $errors++;
                    $messagerow['result'] = get_string('coupon_generator_error', 'enrol_wallet');
                    $tracker->output($messagerow, false);
                }
            }
        } // End of while loop.

        $message = [
            get_string('coupons_uploadtotal', 'enrol_wallet', $total),
            get_string('coupons_uploadcreated', 'enrol_wallet', $created),
            get_string('coupons_uploadupdated', 'enrol_wallet', $updated),
            get_string('coupons_uploaderrors', 'enrol_wallet', $errors)
        ];

        $tracker->finish();
        $tracker->results($message);
    }

    /**
     * Parse a line to return an array(column => value)
     *
     * @param array $line returned by csv_import_reader
     * @return array
     */
    protected function parse_line($line) {
        $data = [];
        foreach ($line as $keynum => $value) {
            if (!isset($this->columns[$keynum])) {
                // This should not happen.
                continue;
            }

            $key = $this->columns[$keynum];
            $data[$key] = $value;
        }
        return $data;
    }

    /**
     * Reset the current process.
     *
     * @return void.
     */
    public function reset() {
        $this->processstarted = false;
        $this->linenb = 0;
        $this->cir->init();
        $this->errors = array();
    }

    /**
     * Validation.
     *
     * @return void
     */
    protected function validate() {
        if (empty($this->columns)) {
            throw new \moodle_exception('cannotreadtmpfile', 'error');
        } else if (count($this->columns) < 3) { // At lest code and value columns.
            throw new \moodle_exception('csvfewcolumns', 'error');
        }
    }
    /**
     * Write contents to a file for debugging purposes
     *
     * @param string $content text to save
     * @param string $prefix file prefix
     * @return void
     */
    public function debug_write(string $content, string $prefix) {
        global $CFG;

        if (debugging(null, DEBUG_DEVELOPER)) {
            $tempxmlfilename = tempnam($CFG->tempdir, $prefix);
            file_put_contents($tempxmlfilename, $content);
        }
    }

    /**
     * Delete temporary files if debugging disabled
     *
     * @param string $filename name of file to be deleted
     * @return void
     */
    public function debug_unlink($filename) {
        if (!debugging(null, DEBUG_DEVELOPER)) { // Not Parenthesis debugging(null, DEBUG_DEVELOPER) parenthesis.
            unlink($filename);
        }
    }
}


/**
 * An exception for reporting errors when processing files
 *
 * Extends the moodle_exception with an http property, to store an HTTP error
 * code for responding to AJAX requests.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processor_exception extends \moodle_exception {

    /**
     * Stores an HTTP error code
     *
     * @var int
     */
    public $http;

    /**
     * Constructor, creates the exception from a string identifier, string
     * parameter and HTTP error code.
     *
     * @param string $errorcode
     * @param string $a
     * @param int $http
     */
    public function __construct($errorcode, $a = null, $http = 200) {
        parent::__construct($errorcode, 'enrol_wallet', '', $a);
        $this->http = $http;
    }
}
