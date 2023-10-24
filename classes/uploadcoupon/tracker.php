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
 * Output tracker.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet\uploadcoupon;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/weblib.php');

/**
 * Class output tracker.
 *
 * Copied from /admin/tool/uploadcourse/classes/tracker.php and modified
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class tracker {

    /**
     * Constant to output nothing.
     */
    const NO_OUTPUT = 0;

    /**
     * Constant to output HTML.
     */
    const OUTPUT_HTML = 1;

    /**
     * Constant to output plain text.
     */
    const OUTPUT_PLAIN = 2;

    /**
     * @var array columns to display.
     */
    protected $columns = array();

    /**
     * @var bool display visual outcome column.
     */
    protected $outcomecol = false;

    /**
     * @var int row number.
     */
    protected $rownb = 0;

    /**
     * @var int chosen output mode.
     */
    protected $outputmode;

    /**
     * @var object output buffer.
     */
    protected $buffer;

    /**
     * Constructor.
     *
     * @param int $outputmode desired output mode.
     * @param bool $outcomecol include outcome column.
     */
    public function __construct($outputmode = self::NO_OUTPUT, bool $outcomecol = false) {
        $this->outputmode = $outputmode;
        if ($this->outputmode == self::OUTPUT_PLAIN) {
            $this->buffer = new \progress_trace_buffer(new \text_progress_trace());
        }
        $this->outcomecol = $outcomecol;
    }

    /**
     * Start the output.
     *
     * @param array $reportheadings list of headings for output report, with names and labels
     * @param bool $outcomecol Include an outcome column to visually indicate success or failure
     * @return void
     */
    public function start(array $reportheadings, bool $outcomecol = false) {

        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        $this->outcomecol = $outcomecol;

        // Set the columns.
        foreach ($reportheadings as $hkey => $label) {
            $this->columns[$hkey] = $label;
        }

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            foreach ($reportheadings as $hkey => $label) {
                $this->buffer->output($label);
            }
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            // Print HTML table.
            $ci = 0;
            echo \html_writer::start_tag('table', array('class' => 'generaltable boxaligncenter flexible-wrap'));
            echo \html_writer::start_tag('thead');
            echo \html_writer::start_tag('tr', array('class' => 'heading r' . $this->rownb));
            if ($this->outcomecol) {
                echo \html_writer::tag('th', '', array('class' => 'c' . $ci++, 'scope' => 'col'));
            }
            // Print the headings in array order, and keep track of the columns and order for printing body rows.
            $ci = 0;
            foreach ($reportheadings as $hkey => $label) {
                echo \html_writer::tag('th', $label,
                    array('class' => 'c' . $ci++, 'scope' => 'col'));
            }
            echo \html_writer::end_tag('tr');
            echo \html_writer::end_tag('thead');
            echo \html_writer::start_tag('tbody');
        }
    }

    /**
     * Output one more line.
     *
     * @param array $rowdata data for each column of report
     * @param bool $outcome success or not?
     * @return void
     */
    public function output(array $rowdata, bool $outcome = false) {
        global $OUTPUT;

        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            if ($this->outcomecol) {
                $message[] = $outcome ? 'OK' : 'NOK';
            }

            // Print a column for each heading.
            foreach ($this->columns as $key => $value) {
                $message[] = isset($rowdata[$key]) ? $rowdata[$key] : '';
            }
            $this->buffer->output(implode("\t", $message));
            if (!empty($message)) {
                foreach ($message as $st) {
                    $this->buffer->output($st, 1);
                }
            }
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $ci = 0;
            $this->rownb++; // Use to mark odd and even rows for visual striping.

            // Print a row of output.
            echo \html_writer::start_tag('tr', array('class' => 'r' . $this->rownb % 2));
            // Print a visual success indicator column (green tickbox or red x) for the outcome.
            if ($this->outcomecol) {
                if ($outcome) {
                    $outcome = $OUTPUT->pix_icon('i/valid', '');
                } else {
                    $outcome = $OUTPUT->pix_icon('i/invalid', '');
                }
                echo \html_writer::tag('td', $outcome, array('class' => 'c' . $ci++));
            }

            // Print a column for each heading.
            foreach ($this->columns as $key => $value) {
                if (isset($rowdata[$key])) {
                    echo \html_writer::tag('td', $rowdata[$key], array('class' => 'c' . $ci++));
                } else {
                    echo \html_writer::tag('td', '', array('class' => 'c' . $ci++));
                }
            }
            echo \html_writer::end_tag('tr');
        }
    }

    /**
     * Finish the output.
     *
     * @return void
     */
    public function finish() {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_HTML) {
            echo \html_writer::end_tag('tbody');
            echo \html_writer::end_tag('table');
        }
    }

    /**
     * Output a summary of the results.
     *
     * @param array $summary Summary of completed operations.
     * @return void
     */
    public function results(array $summary) {
        if ($this->outputmode == self::NO_OUTPUT) {
            return;
        }

        if ($this->outputmode == self::OUTPUT_PLAIN) {
            foreach ($summary as $msg) {
                $this->buffer->output($msg);
            }
        } else if ($this->outputmode == self::OUTPUT_HTML) {
            $buffer = new \progress_trace_buffer(new \html_list_progress_trace());
            foreach ($summary as $msg) {
                $buffer->output($msg);
            }
            $buffer->finished();
        }
    }
}
