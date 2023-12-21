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
 * wallet enrolment plugin.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once("$CFG->dirroot/enrol/wallet/extendlib.php");

use enrol_wallet\form\enrol_form;
use enrol_wallet\form\empty_form;
use enrol_wallet\form\applycoupon_form;
use enrol_wallet\form\insuf_form;
use enrol_wallet\form\topup_form;
use enrol_wallet\transactions;

/**
 * wallet enrolment plugin implementation.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class enrol_wallet_plugin extends enrol_plugin {

    /**
     * If coupons disabled.
     */
    public const WALLET_NOCOUPONS = 0;
    /**
     * If only fixed value coupons enabled.
     */
    public const WALLET_COUPONSFIXED = 1;
    /**
     * If only percentage discount coupons enabled.
     */
    public const WALLET_COUPONSDISCOUNT = 2;
    /**
     * If all coupons enabled.
     */
    public const WALLET_COUPONSALL = 3;
    /**
     * If enrol coupons available.
     */
    public const WALLET_COUPONSENROL = 4;
    /**
     * If category coupons available.
     */
    public const WALLET_COUPONCAT = 5;
    /**
     * If the user has insufficient balance.
     */
    public const INSUFFICIENT_BALANCE = 2;
    /**
     * If the user has insufficient balance even after discount.
     */
    public const INSUFFICIENT_BALANCE_DISCOUNTED = 3;
    /**
     * lasternoller
     * @var array
     */
    protected $lasternoller = null;
    /**
     * lasternollerinstanceid
     * @var int
     */
    protected $lasternollerinstanceid = 0;
    /**
     * The cost after discounts.
     * @var float
     */
    protected $costafter;

    /**
     * Summary of __construct
     * @param mixed $instance
     */
    public function __construct($instance = null) {
        if (!empty($instance)) {
            global $USER;
            $this->costafter = $this->get_cost_after_discount($USER->id, $instance);
        }
        $this->load_config();
    }
    /**
     * Returns optional enrolment information icons.
     *
     * This is used in course list for quick overview of enrolment options.
     *
     * We are not using single instance parameter because sometimes
     * we might want to prevent icon repetition when multiple instances
     * of one type exist. One instance may also produce several icons.
     *
     * @param array $instances all enrol instances of this type in one course
     * @return array of pix_icon
     */
    public function get_info_icons(array $instances) {
        global $PAGE;
        $att = [];
        if (!empty($this->config->showprice)) {
            global $USER;
            $costs = [];
            foreach ($instances as $instance) {
                $cost = $this->get_cost_after_discount($USER->id, $instance);
                $enrolstat = $this->can_self_enrol($instance);
                $canenrol  = (true === $enrolstat);
                $insuf = (self::INSUFFICIENT_BALANCE == $enrolstat || self::INSUFFICIENT_BALANCE_DISCOUNTED == $enrolstat);

                // Get the cheapest cost.
                if ($canenrol || $insuf) {
                    $costs[] = $cost;
                }
            }

            $icons = [];
            foreach ($costs as $cost) {
                $id = "wallet-icon-".random_int(100000, 999999999);
                $idp = "wallet-price-".random_int(100000, 999999999);
                $att = ['class' => 'wallet-icon', 'id' => $id];
                if ($cost == 0) {
                    $cost = 'FREE';
                } else if ($cost == PHP_INT_MAX) {
                    $cost = null;
                }

                $att += ['title' => $cost];

                $script = "
                var titleElement = document.createElement('div');
                titleElement.textContent = '$cost';
                titleElement.className = 'enrol_wallet_walletcost';
                titleElement.id = '$idp';

                var imageElement = document.getElementById('$id');
                var x = setInterval(function() {
                    var exist = document.getElementById('$idp');
                    if (exist === null) {
                        // Insert the new title element before the image element
                        imageElement.parentNode.insertBefore(titleElement, imageElement);
                    } else {
                        clearInterval(x);
                    }
                }, 50);
                ";
                $PAGE->requires->js_init_code($script, true);
                $icons[] = new pix_icon('wallet', get_string('pluginname', 'enrol_wallet'), 'enrol_wallet', $att);
            }
            if (!empty($icons)) {
                return $icons;
            }
        }

        return [new pix_icon('wallet', get_string('pluginname', 'enrol_wallet'), 'enrol_wallet', $att)];
    }

    /**
     * Returns localized name of enrol instance
     *
     * @param stdClass $instance (null is accepted too)
     * @return string
     */
    public function get_instance_name($instance) {
        global $DB, $USER;

        if (empty($instance->name)) {

            if (!empty($instance->roleid) && $role = $DB->get_record('role', ['id' => $instance->roleid])) {
                $role = ' (' . role_get_name($role, context_course::instance($instance->courseid, IGNORE_MISSING)) . ')';
            } else {
                $role = '';
            }
            $cost = $this->get_cost_after_discount($USER->id, $instance);
            $currency = $instance->currency;
            $enrol = $this->get_name();
            return get_string('pluginname', 'enrol_' . $enrol) . $role . '-' . $cost . ' ' . $currency;
        } else {
            return format_string($instance->name);
        }
    }

    /**
     * Does this plugin assign protected roles are can they be manually removed?
     * @return bool - false means anybody may tweak roles, it does not use itemid and component when assigning roles
     */
    public function roles_protected() {
        return false;
    }

    /**
     * Does this plugin allow manual unenrolment of all users?
     * All plugins allowing this must implement 'enrol/xxx:unenrol' capability
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means user with 'enrol/xxx:unenrol' may unenrol others freely, false means nobody may touch
     * user_enrollments
     */
    public function allow_unenrol(stdClass $instance) {
        return true;
    }

    /**
     * Return unenrol link to unenrol user from the current course.
     * Null if unenrol self is not allowed or the user doesn't has the capability to unenrol.
     * @param stdClass $instance
     * @return moodle_url|null
     */
    public function get_unenrolself_link($instance) {
        global $USER, $DB;
        // Check main security in the main function.
        $return = parent::get_unenrolself_link($instance);
        if (empty($return)) {
            return null;
        }

        $parentreturn = $return;
        // Check if unenrol self is enabled in the settings.
        $enabled = get_config('enrol_wallet', 'unenrolselfenabled');
        if (!$enabled) {
            return null;
        }

        // Check the periods conditions.
        $before = get_config('enrol_wallet', 'unenrollimitbefor');
        $after  = get_config('enrol_wallet', 'unenrollimitafter');

        $enrolrecord = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $USER->id]);

        $enrolstart = $enrolrecord->timestart;
        $enrolend   = $enrolrecord->timeend;
        // Cannot unenrol self after this period from enrol start date.
        if (!empty($after) && time() > $after + $enrolstart) {
            // Make sure this is not interfere with the second condition.
            if (!empty($before) && !empty($enrolend) && time() > $enrolend - $before) {
                $return = $parentreturn;
            } else {
                $return = null;
            }
        }

        // Cannot unenrol self before this period from the enrol end date.
        if (!empty($before) && !empty($enrolend) && time() < $enrolend - $before) {
            // Make sure this is not interfere with the first condition.
            if (!empty($after) && time() < $after + $enrolstart) {
                $return = $parentreturn;
            } else {
                $return = null;
            }
        }

        return $return;
    }

    /**
     * Unenrol user from the course if enrolled using wallet enrolment.
     * using this to refund the users balance again.
     * @param stdClass $instance
     * @param int $userid
     * @return void
     */
    public function unenrol_user(stdClass $instance, $userid) {
        // Check if refund upon unenrolment is enabled.
        $enabled = get_config('enrol_wallet', 'unenrolrefund');
        if (empty($enabled)) {
            return parent::unenrol_user($instance, $userid);
        }

        global $DB;

        $enrolrecord  = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid]);
        $enrolstart   = $enrolrecord->timestart;
        $enrolend     = $enrolrecord->timeend;
        $refundperiod = get_config('enrol_wallet', 'unenrolrefundperiod');
        $now = time();
        if (
            (!empty($enrolend) && $now > $enrolend) // The enrolmet already ended.
            || ($now > $enrolstart && !empty($refundperiod) && ($now - $enrolstart) > $refundperiod) // Passed the period.
        ) {
            // Condition for refunding aren't met.
            return parent::unenrol_user($instance, $userid);
        }

        $rawcost = $this->get_cost_after_discount($userid, $instance);
        // Check for refunding fee.
        $fee  = intval(get_config('enrol_wallet', 'unenrolrefundfee'));
        $cost = $rawcost - ($rawcost * $fee / 100);

        // Check for previously used coupon.
        $coupons = $DB->get_records('enrol_wallet_coupons_usage', ['userid' => $userid, 'instanceid' => $instance->id]);
        $credit = $cost;
        if (!empty($coupons)) {
            foreach ($coupons as $coupon) {
                if ($coupon->type == 'fixed' || $coupon->type == 'category') {
                    $credit -= $coupon->value;
                } else if ($coupon->type == 'percent') {
                    $credit -= ($cost * $coupon->value / 100);
                } else if ($coupon->type == 'enrol') {
                    $credit -= $cost;
                }
            }
        }

        if ($credit <= 0) {
            return parent::unenrol_user($instance, $userid);
        } else if ($credit > $cost) {
            // Shouldn't happen.
            $credit = $cost;
        }

        // Credit the user.
        $a = [
            'fee'        => $cost - $credit,
            'credit'     => $credit,
            'coursename' => get_course($instance->courseid)->fullname,
        ];
        $desc = get_string('refunduponunenrol_desc', 'enrol_wallet', $a);
        transactions::payment_topup($credit, $userid, $desc, $userid, false, false);

        return parent::unenrol_user($instance, $userid);
    }
    /**
     * Does this plugin allow manual changes in user_enrollments table?
     *
     * All plugins allowing this must implement 'enrol/xxx:manage' capability
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means it is possible to change enrol period and status in user_enrolments table
     */
    public function allow_manage(stdClass $instance) {
        $context = \context_course::instance($instance->courseid);
        if (!has_capability('enrol/wallet:manage', $context)) {
            return false;
        }
        return true;
    }

    /**
     * Does this plugin support some way to user to self enrol?
     *
     * @param stdClass $instance course enrol instance
     * @return bool - true means show "Enrol me in this course" link in course UI
     */
    public function show_enrolme_link(stdClass $instance) {

        if (true !== $this->can_self_enrol($instance, false)) {
            return false;
        }

        return true;
    }

    /**
     * Return true if we can add a new instance to this course.
     *
     * @param int $courseid
     * @return boolean
     */
    public function can_add_instance($courseid) {
        global $DB;
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('moodle/course:enrolconfig', $context) || !has_capability('enrol/wallet:config', $context)) {
            return false;
        }

        // Check the number of allowed instances.
        $count = $DB->count_records('enrol', ['courseid' => $courseid, 'enrol' => 'wallet']);
        if ($multiple = get_config('enrol_wallet', 'allowmultipleinstances')) {
            if (empty($multiple)) {
                return true;
            } else if ($count >= $multiple) {
                return false;
            }
        }

        return true;
    }

    /**
     * Self enrol user to course
     *
     * @param stdClass $instance enrolment instance
     * @param stdClass|null $user User to enrol and deduct fees from
     * @param bool $charge Charge the user to enrol (only false in case of enrol coupons)
     * @return bool|array true if enrolled else error code and message
     */
    public function enrol_self(stdClass $instance, \stdClass $user = null, $charge = true) {
        global $CFG, $DB;
        require_once("$CFG->dirroot/enrol/wallet/locallib.php");
        if (empty($user)) {
            global $USER;
            $user = $USER;
        }

        // Get the name of the course.
        $coursename = get_course($instance->courseid)->fullname;

        $coupon = $this->check_discount_coupon();

        // Get the final cost after discount (if there is no discount it return the full cost).
        $costafter = (!empty($this->costafter)) ? $this->costafter : $this->get_cost_after_discount($user->id, $instance, $coupon);

        if ($charge) {
            $canborrow = enrol_wallet_is_borrow_eligible($user->id);
            // Deduct fees from user's account.
            if (!transactions::debit($user->id, $costafter, $coursename, '', '', $instance->courseid, $canborrow)) {
                throw new moodle_exception('cannotdeductbalance', 'enrol_wallet');
            }
        }

        $timestart = time();
        $timeend = ($instance->enrolperiod) ? $timestart + $instance->enrolperiod : 0;

        // The times the user get deducted but not enrolled, so we try the while loop to make sure that the user enrolled.
        try {
            $conditions = [
                'userid'    => $user->id,
                'enrolid'   => $instance->id,
                'timestart' => $timestart,
                'timeend'   => $timeend,
                'status'    => ENROL_USER_ACTIVE,
            ];
            while (!$DB->record_exists('user_enrolments', $conditions)) {
                $this->enrol_user($instance, $user->id, $instance->roleid, $timestart, $timeend, null, true);
            }
        } catch (\moodle_exception $e) {
            // Rollback the transaction in case of error.
            if ($charge) {
                transactions::payment_topup($costafter, $user->id, 'Refund due to error', '', false, false);
            }

            throw $e;
        }

        \core\notification::success(get_string('youenrolledincourse', 'enrol'));

        // Mark coupon as used (this is for percentage discount coupons only).
        if ($coupon != null && $costafter < $instance->cost) {
            transactions::mark_coupon_used($coupon, $user->id, $instance->id);
        }

        // Unset the session coupon to make sure not used again.
        // This is a double check, already included in mark_coupon_used().
        if (isset($_SESSION['coupon'])) {
            $_SESSION['coupon'] = '';
            unset($_SESSION['coupon']);
        }

        // Now apply the cashback if enabled.
        $cashbackenabled = get_config('enrol_wallet', 'cashback');

        if ($cashbackenabled) {
            transactions::apply_cashback($user->id, $costafter, $coursename, $instance->courseid);
        }

        // Send welcome message.
        if ($instance->customint4 != ENROL_DO_NOT_SEND_EMAIL) {
            $this->email_welcome_message($instance, $user);
        }

        return true;
    }

    /**
     * Check for other enrol_wallet instances, return true if there is a cheaper one.
     *
     * @param stdClass $thisinstance the id of this instances.
     * @return bool
     */
    public function hide_due_cheaper_instance($thisinstance) {
        global $DB, $USER;
        $coupon = $this->check_discount_coupon();
        $courseid = $thisinstance->courseid;

        // Check the status of this instance.
        $thisid        = $thisinstance->id;
        $thiscost      = $this->get_cost_after_discount($USER->id, $thisinstance, $coupon);
        $thisenrolstat = $this->can_self_enrol($thisinstance);
        $thiscanenrol  = (true === $thisenrolstat);
        $thisinsuf = (self::INSUFFICIENT_BALANCE == $thisenrolstat || self::INSUFFICIENT_BALANCE_DISCOUNTED == $thisenrolstat);

        // Get the other instances.
        $instances = $DB->get_records('enrol', ['courseid' => $courseid, 'enrol' => 'wallet']);
        // No need to check if there is only one instance.
        if (count($instances) < 2) {
            return false;
        }

        // Check the status of other instance.
        $hide = false;
        foreach ($instances as $instance) {
            // Don't compare the instance with itself.
            if ($thisid == $instance->id) {
                continue;
            }

            $othercost = $this->get_cost_after_discount($USER->id, $instance, $coupon);
            $enrolstat = $this->can_self_enrol($instance);
            $canenrol  = (true === $enrolstat);
            $insuf = (self::INSUFFICIENT_BALANCE == $enrolstat || self::INSUFFICIENT_BALANCE_DISCOUNTED == $enrolstat);

            // Hide if can enrol in other and is cheaper or cannot enrol in this one.
            if ($canenrol && ($othercost < $thiscost || !$thiscanenrol)) {
                $hide = true;
                break;
            }
            // Both insuficient but there is a cheaper one.
            if ($insuf && $thisinsuf && $othercost < $thiscost) {
                $otherinsuf = true;
            }
        }

        // Cannot enrol in any but there is other cheaper with insufficient balance.
        if (!$hide && !empty($otherinsuf) && !$thiscanenrol) {
            $hide = true;
        }

        return $hide;
    }

    /**
     * Check if there is restriction according to other courses enrolment.
     * Return false if not restricted and string with required courses names in case if restricted.
     * @param stdClass $instance
     * @return bool|string
     */
    public function is_course_enrolment_restriction($instance) {
        global $DB;
        if (!empty($instance->customchar3) && !empty($instance->customint7)) {
            $courses = explode(',', $instance->customchar3);
            $restrict = false;
            $count = 0;
            $total = 0;
            $notenrolled = [];
            foreach ($courses as $courseid) {
                if (!$DB->record_exists('course', ['id' => $courseid])) {
                    continue;
                }

                $total++;
                $coursectx = context_course::instance($courseid);
                if (!is_enrolled($coursectx)) {
                    $restrict = true;
                    // The user is not enrolled in the required course.
                    $notenrolled[] = get_course($courseid)->fullname;
                } else {
                    // Count the courses which the user enrolled in.
                    $count++;
                }
            }

            $coursesnames = '(' . implode(', ', $notenrolled) . ')';
            // In case that the course creator choose a higher number than the selected courses.
            $limit = min($total, $instance->customint7);
            if ($restrict && $count < $limit) {
                return $coursesnames;
            }
        }
        return false;
    }

    /**
     * Creates course enrol form, checks if form submitted
     * and enrols user if necessary. It can also redirect.
     *
     * @param stdClass $instance
     * @return string html text, usually a form in a text box
     */
    public function enrol_page_hook(stdClass $instance) {
        global $OUTPUT, $USER, $CFG;
        require_once("$CFG->dirroot/enrol/wallet/locallib.php");
        // Hide this instance in case of existance of another avaliable one with lower cost.
        if ($this->hide_due_cheaper_instance($instance)) {
            return '';
        }

        $coupon = $this->check_discount_coupon();
        $couponsetting = get_config('enrol_wallet', 'coupons');

        $this->costafter = self::get_cost_after_discount($USER->id, $instance, $coupon);
        $costafter       = $this->costafter;
        $costbefore      = $instance->cost;

        $enrolstatus = $this->can_self_enrol($instance);

        $output = '';
        if (true === $enrolstatus) {

            $confirmpage = new moodle_url('/enrol/wallet/confirm.php');
            // This user can self enrol using this instance.
            $form = new enrol_form($confirmpage, $instance);
            $instanceid = optional_param('instance', 0, PARAM_INT);
            if ((int)$instance->id === $instanceid) {
                // If form validates user can purchase enrolment with wallet balance.
                $data = $form->get_data();
                if (!empty($data) && $data->instance == $instance->id) {
                    $this->enrol_self($instance, $USER);
                    return '';
                }
            }

            ob_start();
            $form->display();
            $output .= ob_get_clean();

            // Now prepare the coupon form.
            // Check the coupons settings first.
            if ($couponsetting != self::WALLET_NOCOUPONS && $costafter != 0) {
                $data = new stdClass();
                $data->header   = $this->get_instance_name($instance);
                $data->instance = $instance;

                $action = new moodle_url('/enrol/wallet/extra/action.php');
                $couponform = new applycoupon_form(null, $data);
                if ($submitteddata = $couponform->get_data()) {
                    enrol_wallet_process_coupon_data($submitteddata);
                }
                ob_start();
                $couponform->display();
                $output .= ob_get_clean();
            }

        } else if (
                self::INSUFFICIENT_BALANCE == $enrolstatus
                || self::INSUFFICIENT_BALANCE_DISCOUNTED == $enrolstatus
            ) {

            $balance = transactions::get_user_balance($USER->id);
            // This user has insufficient wallet balance to be directly enrolled.
            // So we will show him several ways for payments or recharge his wallet.
            $data = new stdClass();
            $data->header   = $this->get_instance_name($instance);
            $data->instance = $instance;
            $a = [
                'cost_before'  => $costbefore,
                'cost_after'   => $costafter,
                'user_balance' => $balance,
                'currency'     => $instance->currency,
            ];
            if ($enrolstatus == self::INSUFFICIENT_BALANCE) {
                $data->info = get_string('insufficient_balance', 'enrol_wallet', $a);
            } else {
                $data->info = get_string('insufficient_balance_discount', 'enrol_wallet', $a);
            }

            $form = new insuf_form(null, $data);
            ob_start();
            $form->display();
            $output .= ob_get_clean();

            // Now prepare the coupon form.
            if ($couponsetting != self::WALLET_NOCOUPONS) {

                $action = new moodle_url('/enrol/wallet/extra/action.php');
                $couponform = new applycoupon_form(null, $data);
                if ($submitteddata = $couponform->get_data()) {
                    enrol_wallet_process_coupon_data($submitteddata);
                }
                ob_start();
                $couponform->display();
                $output .= ob_get_clean();
            }

            // If the payment enbled in this instance, display the payment button.
            if (!empty($instance->customint1) && !empty($instance->currency)) {
                $output .= self::show_payment_info($instance, $costafter);
            }

            // If payment is enabled in general, adding topup option.
            $account = get_config('enrol_wallet', 'paymentaccount');
            if (enrol_wallet_is_valid_account($account)) {
                $topupurl = new moodle_url('/enrol/wallet/extra/topup.php');
                $topupform = new topup_form($topupurl, $data);

                ob_start();
                $topupform->display();
                $output .= ob_get_clean();
            }

        } else {
            // This user cannot enrol using this instance. Using an empty form to keep
            // the UI consistent with other enrolment plugins that returns a form.
            $data = new stdClass();
            $data->header   = $this->get_instance_name($instance);
            $data->info     = $enrolstatus;
            $data->instance = $instance;

            // The can_self_enrol call returns a button to the login page if the user is a
            // guest, setting the login url to the form if that is the case.
            $url = isguestuser() ? get_login_url() : null;
            $form = new empty_form($url, $data);

            ob_start();
            $form->display();
            $output .= ob_get_clean();

        }

        return $OUTPUT->box($output);
    }

    /**
     * Checks if user can self enrol.
     *
     * @param stdClass $instance enrolment instance
     * @param bool $checkuserenrolment if true will check if user enrolment is inactive.
     *             used by navigation to improve performance.
     * @return bool|string true if successful, else error message or false.
     */
    public function can_self_enrol(stdClass $instance, $checkuserenrolment = true) {
        global $CFG, $DB, $OUTPUT, $USER;
        $repurchase = get_config('enrol_wallet', 'repurchase');

        if (isguestuser()) {
            // Can not enrol guest.
            return get_string('noguestaccess', 'enrol') . $OUTPUT->continue_button(get_login_url());
        }

        // Check if user has the capability to enrol in this context.
        if (!has_capability('enrol/wallet:enrolself', context_course::instance($instance->courseid))) {
            return get_string('canntenrol', 'enrol_wallet');
        }

        // Check if user is a parent only if auth wallet exists.
        if (file_exists($CFG->dirroot . '/auth/parent/lib.php')) {
            require_once($CFG->dirroot . '/auth/parent/lib.php');
            if (auth_parent_is_parent($USER)) {
                return get_string('canntenrol', 'enrol_wallet');
            }
        }

        if ($checkuserenrolment) {
            // Check if user is already enroled.
            if ($ue = $DB->get_record('user_enrolments', ['userid' => $USER->id, 'enrolid' => $instance->id])) {
                // Check if repurchase enabled, the enrolment already endded and the user isn't suspended.
                if (!$repurchase || (!empty($ue->timeend) && $ue->timeend < time()) || $ue->status == ENROL_USER_SUSPENDED) {
                    return get_string('alreadyenroled', 'enrol_wallet');
                }
            }
        }

        $return = [];
        // Disabled instance.
        if ($instance->status != ENROL_INSTANCE_ENABLED) {
            $return[] = get_string('canntenrol', 'enrol_wallet');
        }

        // Cannot enrol early.
        if ($instance->enrolstartdate != 0 && $instance->enrolstartdate > time()) {
            $return[] = get_string('canntenrolearly', 'enrol_wallet', userdate($instance->enrolstartdate));
        }

        // Cannot enrol late.
        if ($instance->enrolenddate != 0 && $instance->enrolenddate < time()) {
            $return[] = get_string('canntenrollate', 'enrol_wallet', userdate($instance->enrolenddate));
        }

        // New enrols not allowed.
        if (!$instance->customint6) {
            $return[] = get_string('canntenrol', 'enrol_wallet');
        }

        // Max enrolments reached.
        if ($instance->customint3 > 0) {
            // Max enrol limit specified.
            $count = $DB->count_records('user_enrolments', ['enrolid' => $instance->id]);
            if ($count >= $instance->customint3) {
                // Bad luck, no more self enrolments here.
                $return[] = get_string('maxenrolledreached', 'enrol_wallet');
            }
        }

        // Check the restrictions upon other courses enrollment.
        if ($coursesnames = $this->is_course_enrolment_restriction($instance)) {
            $a = [
                'courses' => $coursesnames,
                'number'  => $instance->customint7,
            ];
            $return[] = get_string('othercourserestriction', 'enrol_wallet', $a);
        }

        // Check the cohorts restrictions.
        if ($instance->customint5) {
            require_once("$CFG->dirroot/cohort/lib.php");
            if (!cohort_is_member($instance->customint5, $USER->id)) {
                $cohort = $DB->get_record('cohort', ['id' => $instance->customint5]);
                if (!$cohort) {
                    return null;
                }
                $a = format_string($cohort->name, true, ['context' => context::instance_by_id($cohort->contextid)]);
                $return[] = markdown_to_html(get_string('cohortnonmemberinfo', 'enrol_wallet', $a));
            }
        }

        if (!empty($this->config->restrictionenabled) && !empty($instance->customtext2)) {
            $info = new \enrol_wallet\restriction\info($instance);
            if (!$info->is_available($reasons, true, $USER->id)) {

                $return[] = $reasons;

            }
        }
        // All restrictions checked.
        if (!empty($return)) {
            // Display them all.
            return implode('<br> &' . ' ', $return);
        }

        // Non valid cost.
        if (!isset($instance->cost) || !is_numeric($instance->cost) || $instance->cost < 0) {
            return get_string('nocost', 'enrol_wallet');
        }

        // Insufficient balance.
        $costafter = self::get_cost_after_discount($USER->id, $instance, null);
        // Check if the discount gives acceptable cost.
        if (!is_numeric($costafter) || $costafter < 0) {
            return get_string('nocost', 'enrol_wallet');
        }

        require_once("$CFG->dirroot/enrol/wallet/locallib.php");
        $this->costafter = $costafter;
        $costbefore      = $instance->cost;
        $balance         = transactions::get_user_balance($USER->id);
        $canborrow       = enrol_wallet_is_borrow_eligible($USER->id);
        if ($balance < $costafter && !$canborrow) {
            if ($costbefore == $costafter) {
                return self::INSUFFICIENT_BALANCE;
            } else {
                return self::INSUFFICIENT_BALANCE_DISCOUNTED;
            }
        }

        return true;
    }

    /**
     * Return information for enrolment instance containing list of parameters required
     * for enrolment, name of enrolment plugin etc.
     *
     * @param stdClass $instance enrolment instance
     * @return stdClass instance info.
     */
    public function get_enrol_info(stdClass $instance) {
        global $USER;
        $coupon = $this->check_discount_coupon();

        $instanceinfo = new stdClass();
        $instanceinfo->id       = $instance->id;
        $instanceinfo->courseid = $instance->courseid;
        $instanceinfo->type     = $this->get_name();
        $instanceinfo->name     = $this->get_instance_name($instance);
        $instanceinfo->status   = $this->can_self_enrol($instance);
        $instanceinfo->cost     = $this->get_cost_after_discount($USER->id, $instance, $coupon);

        return $instanceinfo;
    }

    /**
     * Add new instance of enrol plugin with default settings.
     * @param stdClass $course
     * @return int id of new instance
     */
    public function add_default_instance($course) {
        $fields = $this->get_instance_defaults();

        return $this->add_instance($course, $fields);
    }

    /**
     * Send welcome email to specified user.
     *
     * @param stdClass $instance
     * @param stdClass $user user record
     * @return void
     */
    protected function email_welcome_message($instance, $user) {
        global $CFG, $DB;

        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        $a = new stdClass();
        $a->coursename = format_string($course->fullname, true, ['context' => $context]);
        $a->profileurl = "$CFG->wwwroot/user/view.php?id=$user->id&course=$course->id";

        if (!is_null($instance->customtext1) && trim($instance->customtext1) !== '') {
            $message = $instance->customtext1;
            $key = ['{$a->coursename}', '{$a->profileurl}', '{$a->fullname}', '{$a->email}'];
            $value = [$a->coursename, $a->profileurl, fullname($user), $user->email];
            $message = str_replace($key, $value, $message);
            if (strpos($message, '<') === false) {
                // Plain text only.
                $messagetext = $message;
                $messagehtml = text_to_html($messagetext, null, false, true);
            } else {
                // This is most probably the tag/newline soup known as FORMAT_MOODLE.
                $options = new \stdClass();
                $options->context = $context;
                $options->para = false;
                $options->newlines = true;
                $options->filter = true;

                $messagehtml = format_text($message, FORMAT_MOODLE, $options);
                $messagetext = html_to_text($messagehtml);
            }
        } else {
            $messagetext = get_string('welcometocoursetext', 'enrol_wallet', $a);
            $messagehtml = text_to_html($messagetext, null, false, true);
        }

        $subject = get_string('welcometocourse', 'enrol_wallet', format_string($course->fullname, true, ['context' => $context]));

        $sendoption = $instance->customint4;
        $contact = $this->get_welcome_email_contact($sendoption, $context);

        // Directly emailing welcome message rather than using messaging.
        email_to_user($user, $contact, $subject, $messagetext, $messagehtml);
    }

    /**
     * Sync all meta course links.
     *
     * Unenrols users that have exceeded the "longtimenosee" value set on wallet enrolment instances.
     *
     * @param progress_trace $trace
     * @param int $courseid one course, empty mean all
     * @return int 0 means ok, 1 means error, 2 means plugin disabled
     */
    public function sync(progress_trace $trace, $courseid = null) {
        global $DB;

        if (!enrol_is_enabled('wallet')) {
            $trace->finished();
            return 2;
        }

        // Unfortunately this may take a long time, execution can be interrupted safely here.
        core_php_time_limit::raise();
        raise_memory_limit(MEMORY_HUGE);

        $trace->output('Verifying wallet enrolments...');

        $params = ['now' => time(), 'useractive' => ENROL_USER_ACTIVE, 'courselevel' => CONTEXT_COURSE];
        $coursesql = "";
        if ($courseid) {
            $coursesql = "AND e.courseid = :courseid";
            $params['courseid'] = $courseid;
        }

        // Note: the logic of wallet enrolment guarantees that user logged in at least once (=== u.lastaccess set)
        // and that user accessed course at least once too (=== user_lastaccess record exists).

        // First deal with users that did not log in for a really long time - they do not have user_lastaccess records.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'wallet' AND e.customint2 > 0)
                  JOIN {user} u ON u.id = ue.userid
                 WHERE :now - u.lastaccess > e.customint2
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);

        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint2 / DAYSECS;
            $msg = "unenrolling user $userid from course $instance->courseid as they have did not log in for at least $days days";
            $trace->output($msg, 1);
        }

        $rs->close();

        // Now unenrol from course user did not visit for a long time.
        $sql = "SELECT e.*, ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON (e.id = ue.enrolid AND e.enrol = 'wallet' AND e.customint2 > 0)
                  JOIN {user_lastaccess} ul ON (ul.userid = ue.userid AND ul.courseid = e.courseid)
                 WHERE :now - ul.timeaccess > e.customint2
                       $coursesql";
        $rs = $DB->get_recordset_sql($sql, $params);

        foreach ($rs as $instance) {
            $userid = $instance->userid;
            unset($instance->userid);
            $this->unenrol_user($instance, $userid);
            $days = $instance->customint2 / DAYSECS;
            $msg = 'unenrolling user '.$userid.' from course '.$instance->courseid.
            ' as they have did not access course for at least '.$days.' days';
            $trace->output($msg, 1);
        }

        $rs->close();

        $trace->output('...user wallet enrolment updates finished.');
        $trace->finished();

        $this->process_expirations($trace, $courseid);

        return 0;
    }

    /**
     * Returns the user who is responsible for wallet enrolments in given instance.
     *
     * Usually it is the first editing teacher - the person with "highest authority"
     * as defined by sort_by_roleassignment_authority() having 'enrol/wallet:manage'
     * capability.
     *
     * @param int $instanceid enrolment instance id
     * @return stdClass user record
     */
    protected function get_enroller($instanceid) {
        global $DB;

        if ($this->lasternollerinstanceid == $instanceid && $this->lasternoller) {
            return $this->lasternoller;
        }

        $instance = $DB->get_record('enrol', ['id' => $instanceid, 'enrol' => $this->get_name()], '*', MUST_EXIST);
        $context = context_course::instance($instance->courseid);

        if ($users = get_enrolled_users($context, 'enrol/wallet:manage')) {
            $users = sort_by_roleassignment_authority($users, $context);
            $this->lasternoller = reset($users);
            unset($users);
        } else {
            $this->lasternoller = parent::get_enroller($instanceid);
        }

        $this->lasternollerinstanceid = $instanceid;

        return $this->lasternoller;
    }

    /**
     * Restore instance and map settings.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $course
     * @param int $oldid
     */
    public function restore_instance(restore_enrolments_structure_step $step, stdClass $data, $course, $oldid) {
        global $DB;

        if ($step->get_task()->get_target() == backup::TARGET_NEW_COURSE) {
            $merge = false;
        } else {
            $merge = [
                'courseid' => $data->courseid,
                'enrol'    => $this->get_name(),
                'status'   => $data->status,
                'roleid'   => $data->roleid,
            ];
        }

        if ($merge && $instances = $DB->get_records('enrol', $merge, 'id')) {
            $instance = reset($instances);
            $instanceid = $instance->id;
        } else {
            if (!empty($data->customint5)) {
                if (!$step->get_task()->is_samesite()) {
                    // Use some id that can not exist in order to prevent wallet enrolment,
                    // because we do not know what cohort it is in this site.
                    $data->customint5 = -1;
                }
            }
            $instanceid = $this->add_instance($course, (array)$data);
        }

        $step->set_mapping('enrol', $oldid, $instanceid);
    }

    /**
     * Restore user enrolment.
     *
     * @param restore_enrolments_structure_step $step
     * @param stdClass $data
     * @param stdClass $instance
     * @param int $userid
     * @param int $oldinstancestatus
     */
    public function restore_user_enrolment(restore_enrolments_structure_step $step, $data, $instance, $userid, $oldinstancestatus) {
        $this->enrol_user($instance, $userid, null, $data->timestart, $data->timeend, $data->status);
    }

    /**
     * Restore role assignment.
     *
     * @param stdClass $instance
     * @param int $roleid
     * @param int $userid
     * @param int $contextid
     */
    public function restore_role_assignment($instance, $roleid, $userid, $contextid) {
        // This is necessary only because we may migrate other types to this instance,
        // we do not use component in wallet enrol.
        role_assign($roleid, $userid, $contextid, '', 0);
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        return $this->can_hide_show_instance($instance);
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     * @throws \coding_exception
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/wallet:config', $context);
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = [
                    ENROL_INSTANCE_ENABLED  => get_string('yes'),
                    ENROL_INSTANCE_DISABLED => get_string('no'),
                ];
        return $options;
    }

    /**
     * Return an array of valid options for the newenrols property.
     *
     * @return array
     */
    protected function get_newenrols_options() {
        $options = [1 => get_string('yes'), 0 => get_string('no')];
        return $options;
    }

    /**
     * Return an array of valid options for the expirynotify property.
     *
     * @return array
     */
    protected function get_expirynotify_options() {
        $options = [
                    0 => get_string('no'),
                    1 => get_string('expirynotifyenroller', 'core_enrol'),
                    2 => get_string('expirynotifyall', 'core_enrol'),
                ];
        return $options;
    }

    /**
     * Return an array of valid options for the longtimenosee property.
     *
     * @return array
     */
    public function get_longtimenosee_options() {
        $options = [
                    0              => get_string('never'),
                    1800 * DAYSECS => get_string('numdays', '', 1800),
                    1000 * DAYSECS => get_string('numdays', '', 1000),
                    365 * DAYSECS  => get_string('numdays', '', 365),
                    180 * DAYSECS  => get_string('numdays', '', 180),
                    150 * DAYSECS  => get_string('numdays', '', 150),
                    120 * DAYSECS  => get_string('numdays', '', 120),
                    90 * DAYSECS   => get_string('numdays', '', 90),
                    60 * DAYSECS   => get_string('numdays', '', 60),
                    30 * DAYSECS   => get_string('numdays', '', 30),
                    21 * DAYSECS   => get_string('numdays', '', 21),
                    14 * DAYSECS   => get_string('numdays', '', 14),
                    7 * DAYSECS    => get_string('numdays', '', 7),
                ];
        return $options;
    }

    /**
     * Get all available courses for restriction by another course enrolment.
     * @param int $courseid Current course id of exceptions.
     * @return array<string>
     */
    public function get_courses_options($courseid) {
        // Adding restriction upon another course enrolment.
        // Prepare the course selector.
        $courses = get_courses();
        $options = [];
        foreach ($courses as $course) {
            // We don't check enrolment in home page.
            if ($course->id == SITEID || $course->id == $courseid) {
                continue;
            }

            $category = core_course_category::get($course->category);
            $parentname = $category->name.': ';
            // For sites with greate number of course.
            // This will make it clearer for selections.
            while ($category->parent > 0) {
                $parent = core_course_category::get($category->parent);
                $parentname = $parent->name . ': ' . $parentname;
                $category = $parent;
            }

            $options[$course->id] = $parentname.$course->fullname;
        }
        return $options;
    }

    /**
     * Adding another course restriction options to enrolment edit form.
     * @param array<string> $coursesoptions
     * @param \MoodleQuickForm $mform
     * @param stdClass $instance
     * @return void
     */
    public function course_restriction_edit($coursesoptions, \MoodleQuickForm $mform, $instance = null) {
        if (!empty($coursesoptions)) {
            $count = count($coursesoptions);

            $options = [];
            for ($i = 0; $i <= $count; $i++) {
                $options[$i] = $i;
            }
            $select = $mform->addElement('select', 'customint7', get_string('coursesrestriction_num', 'enrol_wallet'), $options);
            $select->setMultiple(false);
            $mform->addHelpButton('customint7', 'coursesrestriction_num', 'enrol_wallet');

            $mform->addElement('hidden', 'customchar3', '', ['id' => 'wallet_customchar3']);
            $mform->setType('customchar3', PARAM_TEXT);

            $attributes = [
                'id'       => 'wallet_courserestriction',
                'onChange' => 'restrictByCourse()',
            ];
            $restrictionlable = get_string('coursesrestriction', 'enrol_wallet');
            $select = $mform->addElement('select', 'courserestriction', $restrictionlable, $coursesoptions, $attributes);
            $select->setMultiple(true);
            $mform->addHelpButton('courserestriction', 'coursesrestriction', 'enrol_wallet');
            $mform->hideIf('courserestriction', 'customint7', 'eq', 0);
            if (!empty($instance->customchar3)) {
                $mform->setDefault('courserestriction', explode(',', $instance->customchar3));
            }
        } else {
            $mform->addElement('hidden', 'customint7');
            $mform->setType('customint7', PARAM_INT);
            $mform->setConstant('customint7', 0);

            $mform->addElement('hidden', 'customchar3');
            $mform->setType('customchar3', PARAM_TEXT);
            $mform->setConstant('customchar3', '');
        }
    }
    /**
     * Return an array of valid send welcome email options.
     * @return array<string>
     */
    protected function get_send_welcome_email_option() {
        $options = [
            ENROL_DO_NOT_SEND_EMAIL                 => get_string('no'),
            ENROL_SEND_EMAIL_FROM_COURSE_CONTACT    => get_string('sendfromcoursecontact', 'enrol'),
            ENROL_SEND_EMAIL_FROM_NOREPLY           => get_string('sendfromnoreply', 'enrol'),
        ];

        return $options;
    }

    /**
     * Get availabe cohorts options for cohort restriction options.
     * @param stdClass $instance
     * @param context $context
     * @return array<string>
     */
    protected function get_cohorts_options($instance, $context) {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/cohort/lib.php');

        $cohorts = [0 => get_string('no')];
        $allcohorts = cohort_get_available_cohorts($context, 0, 0, 0);
        if ($instance->customint5 && !isset($allcohorts[$instance->customint5])) {
            $c = $DB->get_record('cohort',
                                 ['id' => $instance->customint5],
                                 'id, name, idnumber, contextid, visible',
                                 IGNORE_MISSING);
            if ($c) {
                // Current cohort was not found because current user can not see it. Still keep it.
                $allcohorts[$instance->customint5] = $c;
            }
        }
        foreach ($allcohorts as $c) {
            $cohorts[$c->id] = format_string($c->name, true, ['context' => context::instance_by_id($c->contextid)]);
            if ($c->idnumber) {
                $cohorts[$c->id] .= ' ['.s($c->idnumber).']';
            }
        }
        if ($instance->customint5 && !isset($allcohorts[$instance->customint5])) {
            // Somebody deleted a cohort, better keep the wrong value so that random ppl can not enrol.
            $cohorts[$instance->customint5] = get_string('unknowncohort', 'cohort', $instance->customint5);
        }
        return $cohorts;
    }

    /**
     * The wallet enrollment plugin has several bulk operations that can be performed.
     * @param course_enrolment_manager $manager
     * @return array
     */
    public function get_bulk_operations(course_enrolment_manager $manager) {
        $context = $manager->get_context();
        $bulkoperations = [];
        if (has_capability("enrol/wallet:manage", $context)) {
            $bulkoperations['editselectedusers'] = new \enrol_wallet\editselectedusers_operation($manager, $this);
        }
        if (has_capability("enrol/wallet:unenrol", $context)) {
            $bulkoperations['deleteselectedusers'] = new \enrol_wallet\deleteselectedusers_operation($manager, $this);
        }
        return $bulkoperations;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     */
    public function edit_instance_form($instance, \MoodleQuickForm $mform, $context) {
        // Merge these two settings to one value for the single selection element.
        if ($instance->notifyall && $instance->expirynotify) {
            $instance->expirynotify = 2;
        }
        unset($instance->notifyall);

        if (!empty($instance->customtext2)) {
            $instance->availabilityconditionsjson = $instance->customtext2;
        }
        // Instance name.
        $nameattribs = ['size' => '20', 'maxlength' => '255'];
        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'), $nameattribs);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'server');

        // Cost.
        $mform->addElement('text', 'cost', get_string('credit_cost', 'enrol_wallet'));
        $mform->setType('cost', PARAM_INT);
        $mform->addHelpButton('cost', 'credit_cost', 'enrol_wallet');
        $mform->addRule('cost', get_string('invalidvalue', 'enrol_wallet'), 'required', null, 'client');
        $mform->addRule('cost', get_string('invalidvalue', 'enrol_wallet'), 'numeric', null, 'client');

        // Payment account.
        if (class_exists('\core_payment\account')) {
            $accounts = \core_payment\helper::get_payment_accounts_menu($context);
        } else {
            $accounts = false;
        }

        if ($accounts) {
            $accounts = ((count($accounts) > 1) ? ['' => ''] : []) + $accounts;
            $mform->addElement('select', 'customint1', get_string('paymentaccount', 'payment'), $accounts);
            $mform->addHelpButton('customint1', 'paymentaccount', 'enrol_wallet');
        } else {
            $mform->addElement('static', 'customint1_text', get_string('paymentaccount', 'payment'),
                html_writer::span(get_string('noaccountsavilable', 'payment'), 'alert alert-warning'));
            $mform->addElement('hidden', 'customint1');
            $mform->setType('customint1', PARAM_INT);
            $mform->setConstant('customint1', 0);
        }

        // Currency.
        $supportedcurrencies = $this->get_possible_currencies($instance->customint1);
        $mform->addElement('select', 'currency', get_string('currency', 'enrol_wallet'), $supportedcurrencies);
        $mform->addHelpButton('currency', 'currency', 'enrol_wallet');

        // Instance status (Enabled or Disabled).
        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_wallet'), $options);
        $mform->addHelpButton('status', 'status', 'enrol_wallet');

        // TODO add password as an optional restriction.

        // New enrolments option.
        $options = $this->get_newenrols_options();
        $mform->addElement('select', 'customint6', get_string('newenrols', 'enrol_wallet'), $options);
        $mform->addHelpButton('customint6', 'newenrols', 'enrol_wallet');
        $mform->disabledIf('customint6', 'status', 'eq', ENROL_INSTANCE_DISABLED);

        // Role.
        $roles = $this->extend_assignable_roles($context, $instance->roleid);
        $mform->addElement('select', 'roleid', get_string('role', 'enrol_wallet'), $roles);

        // Enrol period.
        $options = ['optional' => true, 'defaultunit' => 86400];
        $mform->addElement('duration', 'enrolperiod', get_string('enrolperiod', 'enrol_wallet'), $options);
        $mform->addHelpButton('enrolperiod', 'enrolperiod', 'enrol_wallet');

        // Expiry notification.
        $options = $this->get_expirynotify_options();
        $mform->addElement('select', 'expirynotify', get_string('expirynotify', 'core_enrol'), $options);
        $mform->addHelpButton('expirynotify', 'expirynotify', 'core_enrol');

        // Expiry notification notify threshold.
        $options = ['optional' => false, 'defaultunit' => 86400];
        $mform->addElement('duration', 'expirythreshold', get_string('expirythreshold', 'core_enrol'), $options);
        $mform->addHelpButton('expirythreshold', 'expirythreshold', 'core_enrol');
        $mform->disabledIf('expirythreshold', 'expirynotify', 'eq', 0);

        // Enrol start date.
        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolstartdate', get_string('enrolstartdate', 'enrol_wallet'), $options);
        $mform->setDefault('enrolstartdate', 0);
        $mform->addHelpButton('enrolstartdate', 'enrolstartdate', 'enrol_wallet');

        // Enrol end date.
        $options = ['optional' => true];
        $mform->addElement('date_time_selector', 'enrolenddate', get_string('enrolenddate', 'enrol_wallet'), $options);
        $mform->setDefault('enrolenddate', 0);
        $mform->addHelpButton('enrolenddate', 'enrolenddate', 'enrol_wallet');

        // Unenrol inactive users.
        $options = $this->get_longtimenosee_options();
        $mform->addElement('select', 'customint2', get_string('longtimenosee', 'enrol_wallet'), $options);
        $mform->addHelpButton('customint2', 'longtimenosee', 'enrol_wallet');

        // Maximun number of enrolled users.
        $mform->addElement('text', 'customint3', get_string('maxenrolled', 'enrol_wallet'));
        $mform->addHelpButton('customint3', 'maxenrolled', 'enrol_wallet');
        $mform->setType('customint3', PARAM_INT);
        $mform->addRule('customint3', get_string('invalidvalue', 'enrol_wallet'), 'numeric', null, 'client');

        // Send welcone email option.
        $options = $this->get_send_welcome_email_option();
        $mform->addElement('select', 'customint4', get_string('sendcoursewelcomemessage', 'enrol_wallet'), $options);
        $mform->addHelpButton('customint4', 'sendcoursewelcomemessage', 'enrol_wallet');

        // Welcone email content.
        $options = ['cols' => '60', 'rows' => '8'];
        $mform->addElement('textarea', 'customtext1', get_string('customwelcomemessage', 'enrol_wallet'), $options);
        $mform->addHelpButton('customtext1', 'customwelcomemessage', 'enrol_wallet');

        // Adding the awarding program options for this course.
        if (get_config('enrol_wallet', 'awardssite')) {
            // Enable or disable awards.
            $mform->addElement('advcheckbox', 'customint8', get_string('awards', 'enrol_wallet'), '', [], [false, true]);
            $mform->setDefault('customint8', false);
            $mform->addHelpButton('customint8', 'awards', 'enrol_wallet');
            // Getting award condition.
            $mform->addElement('float', 'customdec1', get_string('awardcreteria', 'enrol_wallet'));
            $mform->disabledIf('customdec1', 'customint8', 'notchecked');
            $mform->addHelpButton('customdec1', 'awardcreteria', 'enrol_wallet');
            // Award value per each grage.
            $mform->addElement('float', 'customdec2', get_string('awardvalue', 'enrol_wallet'));
            $mform->disabledIf('customdec2', 'customint8', 'notchecked');
            $mform->addHelpButton('customdec2', 'awardvalue', 'enrol_wallet');
        }

        if (enrol_accessing_via_instance($instance)) {
            $warntext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warntext);
        }

        $this->include_availability($instance, $mform, $context);

        // Cohort restriction.
        $cohorts = $this->get_cohorts_options($instance, $context);
        if (count($cohorts) > 1) {
            $mform->addElement('select', 'customint5', get_string('cohortonly', 'enrol_wallet'), $cohorts);
            $mform->addHelpButton('customint5', 'cohortonly', 'enrol_wallet');
        } else {
            $mform->addElement('hidden', 'customint5');
            $mform->setType('customint5', PARAM_INT);
            $mform->setConstant('customint5', 0);
        }

        // Course restriction.
        $coursesoptions = $this->get_courses_options($instance->courseid);
        $this->course_restriction_edit($coursesoptions, $mform, $instance);

        if (!empty($coursesoptions)) {
            // Add some js code to set the value of customchar3 element for the restriction course enrolment.
            $js = <<<JS
                    function restrictByCourse() {
                        var textelement = document.getElementById("wallet_customchar3");
                        var courseArray = document.getElementById("wallet_courserestriction").selectedOptions;
                        var selectedValues = [];
                        for (var i = 0; i < courseArray.length; i++) {
                            selectedValues.push(courseArray[i].value);
                        }
                        // Set the value of the hidden input field to the comma-separated string of selected values.
                        textelement.value = selectedValues.join(",");
                    }
                JS;
            $mform->addElement('html', '<script>'.$js.'</script>');
        }
    }

    /**
     * Include availability restrictions options to the instance edit form.
     *
     * @param stdClass $instance
     * @param \MoodleQuickForm $mform
     * @param context $context
     */
    protected function include_availability($instance, $mform, $context) {
        global $CFG;
        if (empty($this->config->restrictionenabled) || empty($this->config->availability_plugins)) {
            return;
        }
        if (!$course = self::get_course_by_instance_id($instance->id)) {
            $courseid = optional_param('courseid', null, PARAM_INT);
            if (!empty($courseid) && $courseid != SITEID) {
                $course = get_course($courseid);
            }
        }
        if (empty($course)) {
            return;
        }
        $courses = [$course->id => $course];
        if (!empty($instance->customchar3)) {
            $coursesids = explode(',', $instance->customchar3);
            foreach ($coursesids as $cid) {
                $courses[$cid] = get_course($cid);
            }
        }

        $mform->addElement('header', 'availabilityconditions',
                    get_string('restrictaccess', 'availability'));
        $mform->setExpanded('availabilityconditions', true);

        $mform->addElement('static', 'availabilitynotice',
                    get_string('notice'),
                    get_string('availability_form_desc', 'enrol_wallet'));
        // Availability field. This is just a textarea; the user interface
        // interaction is all implemented in JavaScript. The field is named
        // availabilityconditionsjson for consistency with moodleform_mod.
        $mform->addElement('textarea', 'availabilityconditionsjson',
                    get_string('accessrestrictions', 'availability'));
        \enrol_wallet\restriction\frontend::include_availability_javascript($course, $courses);

    }

    /**
     * Adds enrol instance UI to course edit form
     *
     * @param object $instance enrol instance or null if does not exist yet
     * @param MoodleQuickForm $mform
     * @param object $data
     * @param object $context context of existing course or parent category if course does not exist
     * @return void
     */
    public function course_edit_form($instance, \MoodleQuickForm $mform, $data, $context) {
        global $DB, $OUTPUT;

        $courseid = $data->id ?? optional_param('id', null, PARAM_INT);
        // If the course not created yet, we cannot display the form as it needs the course id.
        if ($context instanceof \context_coursecat) {
            if (empty($courseid) || $courseid == SITEID) {
                $mform->addElement('header', 'enrol_wallet', 'Enrol Wallet Availabe after creation of the course');
                $mform->addElement('static', 'warn', '', 'The course not created yet.');
                return;
            }
        }

        if (empty($courseid) || $courseid == SITEID) {
            return;
        }

        $count = $DB->count_records('enrol', ['courseid' => $courseid, 'enrol' => 'wallet']);

        // In case of many wallet enrolment instances it will be a mess if we try to edit from here.
        if ($count > 1) {
            return;
        }

        // In case there is no wallet enrol instance in this course (This part of the code never reached).
        // I leave this part of the code in case if the core code in /course/edit.php changed.
        if (empty($count) || empty($instance)) {
            $wallet = optional_param('wallet', false, PARAM_BOOL);
            if (!$wallet) {
                $mform->addElement('header', 'enrol_wallet', 'Enrol Wallet Availabe after creation of the course');
                unset($data->id);
                unset($data->summary_editor);
                $params = ['id' => $courseid, 'wallet' => true, 'sesskey' => sesskey()] + (array)$data;
                $url = (new \moodle_url('/course/edit.php', $params))->out(false);
                $mform->addElement('button', 'wallet', 'insert wallet enrolment instance', [
                    'onclick' => "createWallet('$url')",
                ]);

                $code = <<<JS
                    function createWallet(url) {
                        window.location.href = url;
                    }
                JS;
                $mform->addElement('html', "<script>$code</script>");
                return;
            } else {
                $course = get_course($courseid);
                $instanceid = $this->add_default_instance($course);
                $instance = $this->get_instance_by_id($instanceid);
            }
        }

        $mform->addElement('header', 'enrol_wallet', $this->get_instance_name($instance));
        $this->edit_instance_form($instance, $mform, $context);

        $data->instanceid = $instance->id;
        unset($instance->id);

        $mform->addElement('hidden', 'instanceid');
        $mform->setType('instanceid', PARAM_INT);

        $mform->setDefaults((array)$instance + ['instanceid' => $data->instanceid]);
    }

    /**
     * Called after updating/inserting course.
     *
     * @param bool $inserted true if course just inserted
     * @param object $course
     * @param object $data form data
     * @return void
     */
    public function course_updated($inserted, $course, $data) {
        global $DB;
        if (isset($data->instanceid)) {
            $instance = $this->get_instance_by_id($data->instanceid);
        } else {
            $instances = $DB->get_records('enrol', ['courseid' => $course->id, 'enrol' => 'wallet']);
            $instance = array_pop($instances);
            if (empty($instances) || count($instances) > 1) {
                return parent::course_updated($inserted, $course, $data);
            }
        }

        $this->update_instance($instance, $data);
        parent::course_updated($inserted, $course, $data);
    }

    /**
     * Validates course edit form data
     *
     * @param object $instance enrol instance or null if does not exist yet
     * @param array $data
     * @param object $context context of existing course or parent category if course does not exist
     * @return array errors array
     */
    public function course_edit_validation($instance, $data, $context) {
        if (empty($instance)) {
            if (isset($data['instanceid'])) {
                $data['id'] = $data['instanceid'];
                $instance = $this->get_instance_by_id($data['instanceid']);
            } else if (!empty($data['id'])) {
                global $DB;
                $instances = $DB->get_records('enrol', ['courseid' => $data['id'], 'enrol' => 'wallet']);
                if (empty($instances) || count($instances) > 1) {
                    return [];
                }
                $instance = array_pop($instances);
            }
        }
        if (!empty($instance)) {
            $data['id'] = $instance->id;
            return $this->edit_instance_validation($data, [], $instance, $context);
        }
        return [];
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = [];

        if ($data['status'] == ENROL_INSTANCE_ENABLED) {
            if (!empty($data['enrolenddate']) && $data['enrolenddate'] < $data['enrolstartdate']) {
                $errors['enrolenddate'] = get_string('enrolenddaterror', 'enrol_wallet');
            }
        }

        if ($data['expirynotify'] > 0 && $data['expirythreshold'] < 86400) {
            $errors['expirythreshold'] = get_string('errorthresholdlow', 'core_enrol');
        }

        // Now these ones are checked by quickforms, but we may be called by the upload enrolments tool, or a webservive.
        if (core_text::strlen($data['name']) > 255) {
            $errors['name'] = get_string('err_maxlength', 'form', 255);
        }

        $context = context_course::instance($instance->courseid);

        $validstatus        = array_keys($this->get_status_options());
        $validnewenrols     = array_keys($this->get_newenrols_options());
        $validroles         = array_keys($this->extend_assignable_roles($context, $instance->roleid));
        $validexpirynotify  = array_keys($this->get_expirynotify_options());
        $validlongtimenosee = array_keys($this->get_longtimenosee_options());
        $validswep          = array_keys($this->get_send_welcome_email_option());
        $cohorts            = $this->get_cohorts_options($instance, $context);
        $validcohorts       = array_keys($cohorts);
        $validcurrencies    = array_keys($this->get_possible_currencies($instance->customint1));
        $tovalidate = [
            'enrolstartdate' => PARAM_INT,
            'enrolenddate'   => PARAM_INT,
            'name'           => PARAM_TEXT,
            'currency'       => $validcurrencies,
            'cost'           => PARAM_FLOAT,
            'customint2'     => $validlongtimenosee,
            'customint3'     => PARAM_INT,
            'customint4'     => $validswep,
            'customint6'     => $validnewenrols,
            'customint7'     => PARAM_INT,
            'status'         => $validstatus,
            'enrolperiod'    => PARAM_INT,
            'expirynotify'   => $validexpirynotify,
            'roleid'         => $validroles,
        ];

        if (count($cohorts) > 0) {
            $tovalidate['customint5'] = $validcohorts;
        } else {
            $tovalidate['customint5'] = 0;
        }

        if ($data['expirynotify'] != 0) {
            $tovalidate['expirythreshold'] = PARAM_INT;
        }

        if (get_config('enrol_wallet', 'awardssite')) {
            $tovalidate['customint8'] = PARAM_BOOL;
            if (!empty($data['customint8'])) {
                $tovalidate['customdec1'] = PARAM_FLOAT;
                $tovalidate['customdec2'] = PARAM_FLOAT;
            }
        }

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        if (!empty($data['availabilityconditionsjson'])) {
            \core_availability\frontend::report_validation_errors($data, $errors);
        }

        return $errors;
    }


    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {
        $expirynotify = $this->get_config('expirynotify');
        if ($expirynotify == 2) {
            $expirynotify = 1;
            $notifyall = 1;
        } else {
            $notifyall = 0;
        }

        $fields = [
            'status'          => $this->get_config('status'),
            'roleid'          => $this->get_config('roleid'),
            'enrolperiod'     => $this->get_config('enrolperiod'),
            'expirynotify'    => $expirynotify,
            'notifyall'       => $notifyall,
            'expirythreshold' => $this->get_config('expirythreshold'),
            'currency'        => $this->get_config('currency'),
            'customint1'      => $this->get_config('paymentaccount'),
            'customint2'      => $this->get_config('longtimenosee'),
            'customint3'      => $this->get_config('maxenrolled'),
            'customint4'      => $this->get_config('sendcoursewelcomemessage'),
            'customint5'      => 0,
            'customint6'      => $this->get_config('newenrols'),
            'customint7'      => 0,
            'customtext2'     => '',
        ];
        if (get_config('enrol_wallet', 'awardssite')) {
            $awards = $this->get_config('awards');
            $fields['customint8'] = !empty($awards) ? $awards : 0;
            if (!empty($awards)) {
                $fields['customdec1'] = $this->get_config('awardcreteria');
                $fields['customdec2'] = $this->get_config('awardvalue');
            } else {
                $fields['customdec1'] = 0;
                $fields['customdec2'] = 0;
            }
        }

        return $fields;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Returns the list of currencies that the payment subsystem supports and therefore we can work with.
     *
     * @param int $account The payment account id if exist.
     * @return array[currencycode => currencyname]
     */
    public function get_possible_currencies($account = null) {
        $codes = [];
        if (class_exists('\core_payment\helper')) {
            $codes = \core_payment\helper::get_supported_currencies();
        }

        $currencies = [];
        foreach ($codes as $c) {
            $currencies[$c] = new lang_string($c, 'core_currencies');
        }

        uasort($currencies, function($a, $b) {
            return strcmp($a, $b);
        });

        // Adding Wallet Coins currency and empty currency.
        if (empty($currencies)) {
            $currencies = [
                ''    => '',
                'MCW' => get_string('MWC', 'enrol_wallet'),
            ];
        }
        // Adding custom currency in case of there is no available payment gateway or customize the wallet.
        if (empty($currencies) || (empty($account))) {
            $customcurrency = $this->get_config('customcurrency') ?? '';
            $cc = $this->get_config('customcurrencycode') ?? '';
            // Don't override standard currencies.
            if (!array_key_exists($cc, $currencies) || $cc === '' || $cc === 'MCW') {
                $currencies[$cc] = $customcurrency;
            }
        }
        return $currencies;
    }

    /**
     * Add new instance of enrol plugin.
     * @param object $course
     * @param array $fields instance fields
     * @return int id of new instance, null if can not be created
     */
    public function add_instance($course, array $fields = null) {
        // In the form we are representing 2 db columns with one field.
        if (!empty($fields) && !empty($fields['expirynotify'])) {
            if ($fields['expirynotify'] == 2) {
                $fields['expirynotify'] = 1;
                $fields['notifyall'] = 1;
            } else {
                $fields['notifyall'] = 0;
            }
        }

        if (!empty($fields['availabilityconditionsjson'])) {
            $fields['customtext2'] = $fields['availabilityconditionsjson'];
        }

        return parent::add_instance($course, $fields);
    }

    /**
     * Update instance of enrol plugin.
     * @param stdClass $instance
     * @param stdClass $data modified instance fields
     */
    public function update_instance($instance, $data) {
        // Check first if expiry notify is sent by the edit form (not sent in case of bulk edit only).
        if (isset($data->expirynotify)) {
            // In the form we are representing 2 db columns with one field.
            if ($data->expirynotify == 2) {
                $data->expirynotify = 1;
                $data->notifyall = 1;
            } else {
                $data->notifyall = 0;
            }

            // Keep previous/default value of disabled expirythreshold option.
            if (!$data->expirynotify) {
                $data->expirythreshold = $instance->expirythreshold;
            }
        }

        if (!empty($data->availabilityconditionsjson)) {
            $data->customtext2 = $data->availabilityconditionsjson;
        }

        // Add previous value of newenrols if disabled.
        if (!isset($data->customint6)) {
            $data->customint6 = $instance->customint6;
        }

        return parent::update_instance($instance, $data);
    }

    /**
     * Get the enrol wallet instance by id.
     * @param int $instanceid
     * @return stdClass|false
     */
    public function get_instance_by_id($instanceid) {
        global $DB;
        $instance = $DB->get_record('enrol', ['enrol' => 'wallet', 'id' => $instanceid], '*', MUST_EXIST);

        return $instance;
    }

    /**
     * Get the course object by enrol wallet instance id.
     * @param int $instanceid
     * @return bool|stdClass
     */
    public function get_course_by_instance_id($instanceid) {
        global $DB;
        $courseid = $DB->get_field('enrol', 'courseid', ['enrol' => 'wallet', 'id' => $instanceid], IGNORE_MISSING);
        if (!$courseid) {
            return false;
        }
        $course = get_course($courseid);
        return $course;
    }

    /**
     * Gets a list of roles that this user can assign for the course as the default for wallet enrolment.
     *
     * @param context $context the context.
     * @param integer $defaultrole the id of the role that is set as the default for wallet enrolment
     * @return array index is the role id, value is the role name
     */
    public function extend_assignable_roles($context, $defaultrole) {
        global $DB;

        $roles = get_assignable_roles($context, ROLENAME_BOTH);
        if (!isset($roles[$defaultrole])) {
            if ($role = $DB->get_record('role', ['id' => $defaultrole])) {
                $roles[$defaultrole] = role_get_name($role, $context, ROLENAME_BOTH);
            }
        }

        return $roles;
    }

    /**
     * Get the "from" contact which the email will be sent from.
     *
     * @param int $sendoption send email from constant ENROL_SEND_EMAIL_FROM_*
     * @param object $context context where the user will be fetched
     * @return array|stdClass the contact user object.
     */
    public function get_welcome_email_contact($sendoption, $context) {
        global $CFG;

        $contact = null;
        // Send as the first user assigned as the course contact.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_COURSE_CONTACT) {
            $rusers = [];
            if (!empty($CFG->coursecontact)) {
                $croles = explode(',', $CFG->coursecontact);
                list($sort, $sortparams) = users_order_by_sql('u');
                // We only use the first user.
                $i = 0;
                do {
                    if (class_exists('\core_user\fields')) {
                        $userfieldsapi = \core_user\fields::for_name();
                        $allnames = $userfieldsapi->get_sql('u', false, '', '', false)->selects;
                    } else {
                        $allnames = 'u.firstname, '.
                                    'u.lastname, '.
                                    'u.middlename, '.
                                    'u.firstnamephonetic, '.
                                    'u.lastnamephonetic, '.
                                    'u.alternatename';
                    }

                    $rusers = get_role_users($croles[$i], $context, true, 'u.id,  u.confirmed, u.username, '. $allnames . ',
                    u.email, r.sortorder, ra.id', 'r.sortorder, ra.id ASC, ' . $sort, false, '', '', '', '', $sortparams);
                    $i++;
                } while (empty($rusers) && !empty($croles[$i]));
            }
            if ($rusers) {
                $contact = array_values($rusers)[0];
            }
        }

        // If send welcome email option is set to no reply or if none of the previous options have
        // returned a contact send welcome message as noreplyuser.
        if ($sendoption == ENROL_SEND_EMAIL_FROM_NOREPLY || empty($contact)) {
            $contact = core_user::get_noreply_user();
        }

        return $contact;
    }

    /**
     * Check if there is coupon code in session or as a parameter
     * @return string|null return the coupon code, or null if not found.
     */
    public static function check_discount_coupon() {
        $coupon = optional_param('coupon', null, PARAM_RAW);
        if (!empty($coupon)) {
            $_SESSION['coupon'] = $coupon;
        }
        return !empty($_SESSION['coupon']) ? $_SESSION['coupon'] : $coupon;
    }

    /**
     * Get percentage discount for a user from custom profile field and coupon code.
     * Calculate the cost of the course after discount.
     *
     * @param int $userid
     * @param object $instance
     * @param string $coupon the coupon code in case if the discount from it.
     * @return float the cost after discount.
     */
    public static function get_cost_after_discount($userid, $instance, $coupon = null) {
        global $DB;
        $cost = $instance->cost;
        if ($ue = $DB->get_record('user_enrolments', ['enrolid' => $instance->id, 'userid' => $userid])) {
            if (!empty($ue->timeend) && get_config('enrol_wallet', 'repurchase')) {
                if ($first = get_config('enrol_wallet', 'repurchase_firstdis')) {
                    $cost = (100 - $first) * $instance->cost / 100;
                    $second = get_config('enrol_wallet', 'repurchase_seconddis');
                    $timepassed = $ue->timemodified > $ue->timecreated + $ue->timeend - $ue->timestart;
                    if ($second && $ue->modifierid == $userid && $timepassed) {
                        $cost = (100 - $second) * $instance->cost / 100;
                    }
                }
            }
        }

        $couponsetting = get_config('enrol_wallet', 'coupons');
        $percentav = $couponsetting == self::WALLET_COUPONSALL || $couponsetting == self::WALLET_COUPONSDISCOUNT;
        // Check if there is a discount coupon first.
        if (empty($coupon)) {
            $coupon = self::check_discount_coupon();
        }

        $costaftercoupon = $cost;

        if (!empty($coupon) && $percentav) {
            // Save coupon in session.
            $_SESSION['coupon'] = $coupon;

            $coupondata = transactions::get_coupon_value($coupon, $userid, $instance->id);

            $type = (is_array($coupondata)) ? $coupondata['type'] : '';
            if ($type == 'percent' && $coupondata['value'] <= 100) {

                $costaftercoupon = $instance->cost * (1 - $coupondata['value'] / 100);

            }
        }

        // Check if the discount according to custom profile field in enabled.
        if (!$fieldid = get_config('enrol_wallet', 'discount_field')) {
            return $costaftercoupon;
        }
        // Check the data in the discount field.
        $data = $DB->get_field('user_info_data', 'data', ['userid' => $userid, 'fieldid' => $fieldid]);

        if (empty($data)) {
            return $costaftercoupon;
        }
        // If the user has free access to courses return 0 cost.
        if (stripos(strtolower($data), 'free') !== false) {
            return 0;
            // If there is a word no in the data means no discount.
        } else if (stripos(strtolower($data), 'no') !== false) {
            return $costaftercoupon;
        } else {
            // Get the integer from the data.
            preg_match('/\d+/', $data, $matches);
            if (isset($matches[0]) && intval($matches[0]) <= 100) {
                // Cannot allow discount more than 100%.
                $discount = intval($matches[0]);
                $cost = $costaftercoupon * (100 - $discount) / 100;
                return $cost;
            } else {
                return $costaftercoupon;
            }
        }
    }

    /**
     * Generates payment information to display on enrol/info page.
     *
     * @param stdClass $instance
     * @param float $fee the cost after discounts.
     * @return string
     */
    public static function show_payment_info(stdClass $instance, $fee) {
        global $USER, $OUTPUT, $DB, $CFG;
        require_once("$CFG->dirroot/enrol/wallet/locallib.php");

        if (!enrol_wallet_is_valid_account($instance->customint1)) {
            return '';
        }

        if (!class_exists('\core_payment\helper')) {
            return '';
        }

        $balance = (float)transactions::get_user_balance($USER->id);
        $cost    = (float)$fee - $balance;

        // If user already had enough balance no need to display direct payment to the course.
        if ($balance >= $fee) {
            return '';
        }

        $course = $DB->get_record('course', ['id' => $instance->courseid], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        if ($cost < 0.01) { // No cost.
            return '<p>'.get_string('nocost', 'enrol_wallet').'</p>';

        } else {
            require_once(__DIR__.'/classes/payment/service_provider.php');

            $payrecord = [
                'cost'        => $cost,
                'currency'    => $instance->currency,
                'userid'      => $USER->id,
                'instanceid'  => $instance->id,
                'timecreated' => time(),
            ];
            if (!$id = $DB->get_field('enrol_wallet_items', 'id', $payrecord, IGNORE_MULTIPLE)) {
                $id = $DB->insert_record('enrol_wallet_items', $payrecord);
            }

            $data = [
                'isguestuser' => isguestuser() || !isloggedin(),
                'cost'        => \core_payment\helper::get_cost_as_string($cost, $instance->currency),
                'itemid'      => $id,
                'description' => get_string('purchasedescription', 'enrol_wallet',
                                        format_string($course->fullname, true, ['context' => $context])),
                'successurl'  => \enrol_wallet\payment\service_provider::get_success_url('wallet', $instance->id)->out(false),
            ];

            if (!empty($balance)) {
                $data['balance'] = \core_payment\helper::get_cost_as_string($balance, $instance->currency);
            } else {
                $data['balance'] = false;
            }
        }

        return $OUTPUT->render_from_template('enrol_wallet/payment_region', $data);
    }

}

/*  A reference to remind me with the instance object.
    id:              the instance id (int)
    enrol:           wallet "fixed for each plugin" (string)
    status:          is the instance enabled or disabled (int)
    courseid:        the course id (int)
    sortorder:       "Don't override" the order of this instance in the course (int)
    name:            the name of the instance (string)
    enrolperiod:     duration of enrolment (int)
    enrolstartdate:  start date (int)
    enrolenddate:    end date (int)
    expirynotify:    Whom to notify about expiration? (int)
    expirythreshold: When to send notification? (int)
    notifyall:       if to notify enrolled and enroller or not, (overridden by expirynotify) (int) - bool
    password:        "Not used"
    cost:            The cost (float)
    currency:        The currency (sting)
    roleid:          the id of the role, by default student role (int)
    customint1:      Payment Account id (int)
    customint2:      long time no see (unenrol inactive after) (int)
    customint3:      Max enrolled users (int)
    customint4:      Send welcome email (int) - bool
    customint5:      Cohort restriction id (int)
    customint6:      Allow new enrol (int) - bool
    customint7:      Min number or required courses (int)
    customint8:      Enable Awards (int) - bool
    customchar1:     "not used"
    customchar2:     "not used"
    customchar3:     ids of the courses required for enrol restriction (string) integers imploded by ','
    customdec1:      condition for award (percentage) (int) 0 - 99
    customdec2:      Award value per each raw mark above the condition (float)
    customtext1:     Welcome email content (string)
    customtext2:     "not used" TODO add restriction rules.
    customtext3:     "not used" TODO add offers rules.
    customtext4:     "not used"
    timecreated:     the time at which the instance created (int)
    timemodified:    the time at which the instance modified (int)
    */
