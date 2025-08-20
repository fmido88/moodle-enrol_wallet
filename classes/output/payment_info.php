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
 * Get the data for payment in a certain instance.
 *
 * @package   enrol_wallet
 * @copyright 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet\output;

use renderable;
use templatable;
use renderer_base;
use enrol_wallet\local\entities\instance as helper;
use enrol_wallet\local\wallet\balance;
use enrol_wallet\payment\service_provider;
use core_payment\helper as payment_helper;
use context_course;
/**
 * Ready the payment info data and payment button to render.
 */
class payment_info implements renderable, templatable {
    /**
     * The cost after discount
     * @var float
     */
    protected $costafter;
    /**
     * The current user valid balance
     * @var float
     */
    protected $userbalance;
    /**
     * The cost of the instance to be payed.
     * @var float
     */
    protected $cost;
    /**
     * The currency of the instance
     * @var string
     */
    protected $currency;

    /**
     * The course object
     * @var \stdClass
     */
    protected $course;
    /**
     * The context
     * @var \context_course
     */
    protected $context;
    /**
     * The currency of the wallet.
     * @var string
     */
    protected $walletcurrency;
    /**
     * The instance id.
     * @var int
     */
    protected $instanceid;

    /**
     * Used to calculate and prepare the payment region for enrol wallet
     * instance.
     * @param \stdClass $instance the enrol wallet instance record.
     */
    public function __construct($instance) {
        $helper = new helper($instance);

        $this->instanceid = $instance->id;
        $this->cost = $instance->cost;
        $this->currency = $instance->currency;
        $this->costafter = $helper->get_cost_after_discount();
        $balance = balance::create_from_instance($instance);
        $this->userbalance = $balance->get_valid_balance();
        $this->course = $helper->get_course();
        $this->context = context_course::instance($this->course->id);
        $this->walletcurrency = get_config('enrol_wallet', 'currency');
    }

    /**
     * Get the cost after paying some from the wallet.
     * @return float
     */
    private function get_cost() {
        if ($this->walletcurrency == $this->currency) {
            return $this->costafter - $this->userbalance;
        }
        return $this->costafter;
    }

    /**
     * Check if the balance in the wallet is enough and no eed for payment.
     * @return bool
     */
    private function has_enough() {
        if ($this->walletcurrency == $this->currency && $this->userbalance >= $this->costafter) {
            return true;
        }
        return false;
    }

    /**
     * Function to export the renderer data in a format that is suitable for a
     * mustache template. This means:
     * No complex types - only stdClass, array, int, string, float, bool
     * Any additional info that is required for the template is pre-calculated (e.g. capability checks).
     * @param renderer_base $output
     * @return array|\stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $USER, $DB;
        // If user already had enough balance no need to display direct payment to the course.
        if ($this->has_enough()) {
            return [];
        }
        $cost = $this->get_cost();

        if ($cost < 0.01) { // No cost.
            return ['nocost' => '<p>'.get_string('nocost', 'enrol_wallet').'</p>'];
        }
        require_once(__DIR__.'/../payment/service_provider.php');

        $payrecord = [
            'cost'        => $cost,
            'currency'    => $this->currency,
            'userid'      => $USER->id,
            'instanceid'  => $this->instanceid,
        ];
        if (!$id = $DB->get_field('enrol_wallet_items', 'id', $payrecord, IGNORE_MULTIPLE)) {
            $payrecord['timecreated'] = time();
            $id = $DB->insert_record('enrol_wallet_items', $payrecord);
        }

        $data = [
            'isguestuser' => isguestuser() || !isloggedin(),
            'cost'        => payment_helper::get_cost_as_string($cost, $this->currency),
            'itemid'      => $id,
            'description' => get_string('purchasedescription', 'enrol_wallet',
                                    format_string($this->course->fullname, true, ['context' => $this->context])),
            'successurl'  => service_provider::get_success_url('wallet', $this->instanceid)->out(false),
        ];

        if (!empty($balance)) {
            $data['balance'] = payment_helper::get_cost_as_string($balance, $this->currency);
        } else {
            $data['balance'] = false;
        }
        return $data;
    }
}
