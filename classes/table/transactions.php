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
 * TODO describe file transactions
 *
 * @package    enrol_wallet
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\table;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/tablelib.php');

use moodle_url;
use table_sql;
use core_user;
use context_system;
use stdClass;
use html_writer;
/**
 * The table that display the wallet transactions.
 */
class transactions extends table_sql {

    /**
     * The filtration data submitted from the filter form.
     * @var stdClass
     */
    protected $filterdata;

    /**
     * Cashing the users data
     * @var array
     */
    private $users;

    /**
     * Check if the user can view all.
     * @var bool
     */
    private $viewall;

    /**
     * Creates a transaction table.
     *
     * @param string $uniqueid all tables have to have a unique id.
     * @param stdClass|null $filterdata The data passed from the filtration form.
     */
    public function __construct($uniqueid, $filterdata = null) {
        parent::__construct($uniqueid);
        $columnsandheaders = [
            'user'        => get_string('user'),
            'timecreated' => get_string('time'),
            'category'    => get_string('category'),
            'amount'      => get_string('amount', 'enrol_wallet'),
            'type'        => get_string('transaction_type', 'enrol_wallet'),
            'balbefore'   => get_string('balance_before', 'enrol_wallet'),
            'balance'     => get_string('balance_after', 'enrol_wallet'),
            'norefund'    => get_string('nonrefundable', 'enrol_wallet'),
            'descripe'    => get_string('description'),
        ];
        $columns = array_keys($columnsandheaders);
        $headers = array_values($columnsandheaders);
        $this->define_columns($columns);
        $this->define_headers($headers);

        $this->set_attribute('class', 'generaltable generalbox wallet-transactions');
        $this->sortable(true);
        $this->no_sorting('user');

        $this->viewall = has_capability('enrol/wallet:transaction', context_system::instance());

        $this->filterdata = $filterdata ?? new stdClass;

        $types = [
            'userid'   => PARAM_INT,
            'datefrom' => PARAM_INT,
            'dateto'   => PARAM_INT,
            'ttype'    => PARAM_TEXT,
            'value'    => PARAM_FLOAT,
            'category' => PARAM_INT,
            'pagesize' => PARAM_INT,
            'page'     => PARAM_INT,
            'tsort'    => PARAM_TEXT,
        ];
        foreach ($_GET as $key => $value) {
            if (!isset($filterdata->$key) && array_key_exists($key, $types)) {
                $this->filterdata->$key = clean_param($value, $types[$key]);
            }
        }

        if (!$this->viewall) {
            global $USER;
            $this->filterdata->userid = $USER->id;
        }
        $this->set_sql();
    }

    /**
     * Set the sql to query the db. Query will be :
     *      SELECT $fields FROM $from WHERE $where
     * Of course you can use sub-queries, JOINS etc. by putting them in the
     * appropriate clause of the query.
     * @param string $fields always set to *
     * @param string $from  always set to enrol_wallet_transactions
     * @param string $where Ignored
     * @param array $params Ignored
     */
    public function set_sql($fields = '*', $from = '{enrol_wallet_transactions}', $where = '', $params = []) {
        $this->sql = new stdClass();
        $this->sql->fields = $fields;
        $this->sql->from = $from;

        $data = $this->filterdata;

        // SQL parameters and select where query.
        $params = [];
        $where = '1=1 ';
        // Check the data from submitted form first.
        if (!$this->viewall) {
            global $USER;
            $params['userid'] = $USER->id;
            $where .= "AND userid = :userid ";
        } else if (!empty($data->userid)) {
            $params['userid'] = $data->userid;
            $where .= "AND userid = :userid ";
        }

        if (!empty($data->ttype)) {
            $params['type'] = $data->ttype;
            $where .= "AND type = :type ";
        }

        if (!empty($data->value)) {
            $params['amount'] = $data->value;
            $where .= "AND amount = :amount ";
        }

        if (!empty($data->datefrom)) {
            $params['timefrom'] = $data->datefrom;
            $where .= "AND timecreated >= :timefrom ";
        }

        if (!empty($data->dateto)) {
            $params['timeto'] = $data->dateto;
            $where .= "AND timecreated <= :timeto ";
        }

        if (!empty($data->category)) {
            $params['category'] = $data->category;
            $where .= "AND category = :category";
        }
        $this->sql->where = $where;
        $this->sql->params = $params;
    }

    /**
     * get the sql fragment for ORDER BY.
     *
     * override to default ordering this by id descending.
     *
     * @return string fragment that can be used in an ORDER BY clause.
     */
    public function get_sql_sort() {
        $sort = parent::get_sql_sort();
        if (empty($sort)) {
            $sort = 'id DESC';
        }
        return $sort;
    }
    /**
     * User full name.
     * @param object $record
     * @return string link to user's profile
     */
    protected function col_user($record) {
        if (!isset($this->users[$record->userid])) {
            $user = core_user::get_user($record->userid);
            if (!$user) {
                $user = new stdClass;
                $user->fullname = get_string('usernotexist', 'enrol_wallet') . ' id:' . $record->userid;
                $user->url = '#';
            } else {
                $user->fullname = fullname($user);
                $user->url = new moodle_url('/user/profile.php', ['id' => $user->id]);
            }

            $this->users[$record->userid] = $user;
        } else {
            $user = $this->users[$record->userid];
        }

        if ($this->is_downloading()) {
            return $user->fullname;
        } else {
            return html_writer::link($user->url, $user->fullname);
        }
    }

    /**
     * Time of transaction.
     * @param object $record
     * @return string user readable date of transaction
     */
    protected function col_timecreated($record) {
        return userdate($record->timecreated);
    }

    /**
     * The category at which this transaction belong.
     * @param \stdClass $record
     * @return string the nested name of the category
     */
    protected function col_category($record) {
        if (!empty($record->category)) {
            if ($category = \core_course_category::get($record->category, IGNORE_MISSING, true)) {
                return $category->get_nested_name(false);
            }
            return get_string('unknowncategory');
        }
        return get_string('site');
    }
    /**
     * Transaction amount.
     * @param object $record
     * @return string
     */
    protected function col_amount($record) {
        return number_format($record->amount, 2);
    }

    /**
     * Type of transaction (debit or credit)
     * @param object $record
     * @return string
     */
    protected function col_type($record) {
        return $record->type;
    }

    /**
     * Balance before the transaction
     * @param object $record
     * @return string
     */
    protected function col_balbefore($record) {
        return number_format($record->balbefore, 2);
    }

    /**
     * The balance after transaction
     * @param object $record
     * @return string
     */
    protected function col_balance($record) {
        return number_format($record->balance, 2);
    }

    /**
     * The remain non-refundable amount.
     *
     * @param object $record
     * @return string
     */
    protected function col_norefund($record) {
        return number_format($record->norefund, 2);
    }

    /**
     * The full description of the transaction.
     * @param object $record
     * @return string
     */
    protected function col_descripe($record) {
        return format_string($record->descripe);
    }

}
