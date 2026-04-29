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

namespace enrol_wallet\output;

use core\exception\coding_exception;
use core\output\core_renderer;
use core\output\html_writer;
use core\output\renderable;
use core_collator;
use core_reportbuilder\local\filters\category as category_filter;
use core_reportbuilder\local\filters\date as time_filter;
use core_reportbuilder\local\filters\user as user_filter;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\reportbuilder\local\systemreports\transactions;

/**
 * Statistic visualization of credit and debit transactions in the wallet.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transaction_chart implements renderable {
    /**
     * Constructor.
     * @param transactions $report
     */
    public function __construct(
        /** @var transactions the transactions system report */
        public transactions $report
    ) {
    }

    /**
     * Get array of data to be in the chart.
     * @return array[]
     */
    protected function get_chart_data(): array {
        global $DB, $USER;
        $report = $this->report;

        $debitarray = [];
        $creditarray = [];
        $balancearray = [];
        $labelsarray = [];

        $filtervalues = $report->get_filter_values();
        $filters = $report->get_active_filters();

        $params = [];
        $filterssql = [];

        $ttable = $report->get_main_table();
        $talias = $report->get_main_table_alias();
        $joins = [];

        if (isset($filters['user:userselect'])) {
            $userfilter = user_filter::create($filters['user:userselect']);
            [$sql, $params1] = $userfilter->get_sql_filter($filtervalues);
            $usersql = trim($sql) ? "($sql)" : '';
            $params += $params1;
            $joins = [...$joins, ...$filters['user:userselect']->get_joins()];
        }

        if (empty($usersql) && !has_capability('enrol/wallet:transaction', \core\context\system::instance())) {
            $filterssql[] = "{$talias}.userid = :userid ";
            $params['userid'] = $USER->id;
        } else {
            $filterssql[] = $usersql;
        }

        if (isset($filters['course_category:name'])) {
            [$sql, $params1] = category_filter::create($filters['course_category:name'])->get_sql_filter($filtervalues);
            $filterssql[] = trim($sql) ? "($sql)" : '';
            $params += $params1;
            $joins = [...$joins, ...$filters['course_category:name']->get_joins()];
        }

        $now = timedate::time();
        $maxtime = 0;
        $mintime = $week = timedate::start_of_week($now + WEEKSECS);

        if (isset($filters['transaction:timecreated'])) {
            [$sql, $params1] = time_filter::create($filters['transaction:timecreated'])->get_sql_filter($filtervalues);
            $timesql = trim($sql) ? "($sql)" : '';
            $params += $params1;
            $joins = [...$joins, ...$filters['transaction:timecreated']->get_joins()];
        }

        if (!empty($timesql)) {
            $filterssql['timesql'] = $timesql;
        }

        $select = implode(' AND ', array_filter($filterssql));

        $fields = ['id', 'amount', 'type', 'timecreated', 'balance', 'category', 'userid'];
        $fields = implode(', ', array_map(fn ($f) => "{$talias}.$f", $fields));
        $joins = implode(' ', $joins);
        $sql = "SELECT $fields, cc.name FROM {{$ttable}} $talias $joins
                LEFT JOIN {course_categories} cc ON {$talias}.category = cc.id";
        $sql .= $select ? " WHERE $select" : '';

        $records = $DB->get_records_sql($sql, $params);

        foreach ($records as $record) {
            $mintime = min($record->timecreated, $mintime);
            $maxtime = max($record->timecreated, $maxtime);
        }
        $week = timedate::start_of_week($maxtime + WEEKSECS);

        $numberweeks = ceil(($maxtime - $mintime - 2 * HOURSECS) / WEEKSECS);

        for ($i = 0; $i <= $numberweeks; $i++) {
            $week -= WEEKSECS;
            $week = timedate::start_of_week($week + 2 * HOURSECS);
            $debitarray[$week] = $creditarray[$week] = 0;
            $labelsarray[$week] = timedate::week_of_month($week) . ' '
                                . userdate($week, get_string('strftimemonthyear', 'langconfig'));
        }

        $sitestr = get_string('site');
        $deletedstr = get_string('deleted');
        foreach ($records as $record) {
            $week = timedate::start_of_week($record->timecreated);

            if (!isset($creditarray[$week])) {
                debugging('the week ' . userdate($week) . ' not initialized.', DEBUG_DEVELOPER);
                continue;
            }

            switch ($record->type) {
                case 'credit':
                    $creditarray[$week] += $record->amount;
                    break;

                case 'debit':
                    $debitarray[$week] -= $record->amount;
                    break;

                default:
                    throw new coding_exception("Unknown transaction type {$record->type}");
            }

            $catid = (int)($record->category ?? -1);
            $catname = $catid !== 0 ? ($record->name ?: $deletedstr) : $sitestr;

            if (!isset($balancearray["{$catid}-{$catname}"])) {
                $balancearray["{$catid}-{$catname}"] = array_fill_keys(array_keys($debitarray), false);
            }

            $balancearray["{$catid}-{$catname}"][$week] = $balancearray["{$catid}-{$catname}"][$week] ?: [];
            $balancearray["{$catid}-{$catname}"][$week][$record->userid] = $record->balance;
        }

        core_collator::ksort($creditarray, core_collator::SORT_NUMERIC);
        core_collator::ksort($debitarray, core_collator::SORT_NUMERIC);
        core_collator::ksort($labelsarray, core_collator::SORT_NUMERIC);

        $return = [
            'credit' => $creditarray,
            'debit'  => $debitarray,
            'labels' => $labelsarray,
        ];

        $newbalancearray = [];

        foreach ($balancearray as $cat => $array) {
            core_collator::ksort($array, core_collator::SORT_NUMERIC);
            // We need to fill zeros with its previous values.
            $prevbalance = false;

            foreach ($array as $week => &$balance) {
                if ($balance === false) {
                    if ($prevbalance !== false) {
                        $balance = $prevbalance;
                    } else {
                        [$catid] = explode('-', $cat, 2);
                        $params += [
                            'catid'    => $catid,
                            'lasttime' => $week,
                        ];
                        unset($filterssql['timesql']);
                        $select = implode(' AND ', array_filter($filterssql));
                        $fields = ['userid', 'balance'];
                        $fields = implode(', ', array_map(fn ($f) => "{$talias}.$f", $fields));
                        $sql = "SELECT DISTINCT $fields FROM {{$ttable}} $talias $joins";
                        $newselect = "{$talias}.category = :catid AND {$talias}.timecreated <= :lasttime";
                        $sql .= $select ? " WHERE $select AND $newselect" : " WHERE $newselect";
                        $sql .= " GROUP BY {$talias}.userid ORDER BY {$talias}.id DESC";
                        $records = $DB->get_records_sql($sql, $params);
                        $records = array_map(fn ($record) => $record->balance, $records);
                        $balance = $prevbalance = array_sum($records);
                    }
                } else {
                    $balance = array_sum($balance);
                    $prevbalance = $balance;
                }
                $newbalancearray[$week] ??= 0;
                $newbalancearray[$week] += $balance;
            }

            $return[$cat] = $array;
        }
        $return['balance'] = $newbalancearray;

        return array_map(array_values(...), $return);
    }

    /**
     * Get the chart widget.
     * @return \core\chart_line[]
     */
    public function get_chart(): array {
        $balancestr = get_string('balance', 'enrol_wallet');

        $chart1 = new \core\chart_line();
        $chart1->set_smooth(false);
        $chart1->set_title(get_string('transactions', 'enrol_wallet'));

        $chart2 = new \core\chart_line();
        $chart2->set_smooth(true);
        $chart2->set_title($balancestr);

        $data = $this->get_chart_data();

        foreach ($data as $key => $array) {
            switch ($key) {
                case 'credit':
                case 'debit':
                    $series = new \core\chart_series(get_string($key, 'enrol_wallet'), $array);
                    $chart1->add_series($series);
                    break;

                case 'balance':
                    $series = new \core\chart_series(get_string($key, 'enrol_wallet'), $array);
                    $chart2->add_series($series);
                    break;

                case 'labels':
                    $chart1->set_labels($array);
                    $chart2->set_labels($array);
                    break;

                default:
                    if (\count($data) > 7) {
                        // To much categories balances, only show total balance chart.
                        break;
                    }

                    [, $catname] = explode('-', $key, 2);
                    $catname = format_string($catname);
                    $series = new \core\chart_series("$catname $balancestr", $array);
                    $chart2->add_series($series);
            }
        }

        return [$chart1, $chart2];
    }

    /**
     * Get the rendered chart.
     * @param  core_renderer $output
     * @return string
     */
    public function get_output(core_renderer $output): string {
        $charts = $this->get_chart();
        $ch1 = $output->render($charts[0]);
        $ch2 = $output->render($charts[1]);

        return html_writer::div("$ch1\n$ch2", 'd-flex');
    }
}
