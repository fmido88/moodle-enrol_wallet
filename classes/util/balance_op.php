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

namespace enrol_wallet\util;

use enrol_wallet\category\operations;
use enrol_wallet\notifications;
use enrol_wallet\wordpress;
use enrol_wallet\event\transactions_triggered;
use enrol_wallet\task\turn_non_refundable;
use enrol_wallet\util\discount_rules as discounts;

/**
 * Class balance_op
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class balance_op extends balance {

    /**
     * Debit operation
     */
    private const DEBIT = 'debit';
    /**
     * Credit operation
     */
    private const CREDIT = 'credit';
    /**
     * The operation done manually by user.
     */
    public const USER = 'user';
    /**
     * The operation done for other reason.
     */
    public const OTHER = 'other';

    /**
     * The debit operation done after enrolment with passing the course id.
     */
    public const D_ENROL_COURSE = 'enrol';

    /**
     * The debit operation done for enrolment with passing the instance id.
     */
    public const D_ENROL_INSTANCE = 'enrol_instance';

    /**
     * The debit operation done for accessing a course module.
     */
    public const D_CM_ACCESS = 'cm';

    /**
     * The debit operation done for access a course section.
     */
    public const D_SECTION_ACCESS = 'section';

    /**
     * The debit operation done for payment of signup fee.
     */
    public const D_AUTH_FEE = 'authfee';

    /**
     * The debit operation done for paying extra fee for signup.
     */
    public const D_AUTH_EXTRA_FEE = 'authextrafee';

    /**
     * The wallet has been charged by payment.
     */
    public const C_PAYMENT = 'payment';

    /**
     * The wallet has been charged by cashback.
     */
    public const C_CASHBACK = 'cashback';

    /**
     * The wallet has been charged by new user gift.
     */
    public const C_ACCOUNT_GIFT = 'newaccountgift';

    /**
     * The wallet has been charged by course completion award.
     */
    public const C_AWARD = 'award';

    /**
     * The wallet has been charged by referral gift.
     */
    public const C_REFERRAL = 'referral';

    /**
     * The wallet has been charged by vc block.
     */
    public const C_VC = 'vc';

    /**
     * The wallet has been charged by a coupon.
     */
    public const C_COUPON = 'coupon';
    /**
     * Credit happen after calculating the rest of the amount
     * granted due to conditional discount rules.
     */
    public const C_DISCOUNT = 'conditionaldiscount';
    /**
     * Refund after un-enrol
     */
    public const C_UNENROL = 'unenrol';
    /**
     * Rollback in case of error in enrolment process.
     */
    public const C_ROLLBACK = 'rollback';
    /**
     * Transfered from another user.
     */
    public const C_TRANSFER = 'transfer';

    /**
     * The amount per transaction.
     * @var float
     */
    private $amount;
    /**
     * The course id at which the operation done.
     * @var int
     */
    private $courseid;

    /**
     * Helper calss
     * @var instance|cm|section
     */
    private $helper;
    /**
     * Transaction record id.
     * @var int
     */
    private $transactionid;
    /**
     * How much of the free balance has been deducted.
     * @var float
     */
    protected $freecut;

    /**
     * Same as its parent constructor
     * @see enrol_wallet\util\balance::__construct
     * Just unset the category if not specified in the operation.
     *
     * @param int $userid
     * @param int|object $category
     */
    public function __construct($userid = 0, $category = 0) {
        parent::__construct($userid, $category);
        if (empty($category)) {
            $this->catid = 0;
            unset($this->catop);
        }
        $this->freecut = 0;
    }

    /**
     * Cut amount from the main balance, this is not checking for negative balance.
     * Which means that the balance could be negative, so before using this validate if the new balance
     * could be negative of not.
     *
     * @param float $amount
     * @return void
     */
    protected function cut_from_main($amount) {
        $refundable = $this->details['mainrefundable'];
        $nonrefundable = $this->details['mainnonrefund'];
        $free = $this->details['mainfree'] ?? 0;
        if ($refundable >= $amount) {
            $refundable = $refundable - $amount;
        } else {
            $remain = $amount - $refundable;
            $refundable = 0;
            if ($remain > $nonrefundable) {
                $nonrefundable = 0;
                $refundable = $nonrefundable - $remain;
            } else {
                $nonrefundable = $nonrefundable - $remain;
            }

            $newfree = max($free - $remain, 0);
            $this->freecut += $free - $newfree;
        }
        $this->details['mainrefundable'] = $refundable;
        $this->details['mainnonrefund'] = $nonrefundable;
        $this->details['mainbalance'] = $refundable + $nonrefundable;
        $this->details['mainfree'] = $newfree ?? $free;
    }

    /**
     * Cut balance from all categories and main.
     * @param float $remain
     */
    private function total_cut($remain) {

        if (!empty($this->details['catbalance'])) {
            foreach ($this->details['catbalance'] as $id => $detail) {
                $balance = $detail->balance ?? $detail->refundable + $detail->nonrefundable;
                if (empty($balance)) {
                    continue;
                }
                $op = new operations($id, $this->userid);
                $remain = $op->deduct($remain);
                $this->details['catbalance'] = $op->details;
                $this->freecut += $op->get_free_cut();
                $this->update();

                if ($remain == 0) {
                    break;
                }
            }
        }
        if (!empty($remain)) {
            $this->cut_from_main($remain);
        }

    }
    /**
     * Add an amount to the main balance
     * @param float $amount
     * @param bool $refundable
     * @param bool $free
     */
    protected function add_to_main($amount, $refundable, $free = false) {
        if ($refundable) {
            $this->details['mainrefundable'] += $amount;
        } else {
            if ($free) {
                $this->details['mainfree'] += $amount;
            }
            $this->details['mainnonrefund'] += $amount;
        }
        $this->details['mainbalance'] += $amount;
    }

    /**
     * Basic function to debit balance from a user.
     * The parameter $for should be one of the constants balance_op::D_*** or balance_op::USER for manual debit
     * or balance_op::OTHER for other reason specified in the $desc param.
     * the parameter $thingid is the id of something corresponding to reason ($for)
     * for example: if $for = balance_op::USER so the $thingid = $user->id which done the debit operation,
     * balance_op::D_ENROL_COURSE --> course id
     * balance_op::D_ENROL_INSTANCE --> enrol wallet instance id
     * balance_op::D_CM_ACCESS --> course module id (cmid)
     * balance_op::D_SECTION_ACCESS --> section id
     *
     * @param float $amount the amount to be debit.
     * @param string $for The reason that the amount has been debited, should be one of specific constants.
     * @param int $thingid the id corresponding to the reason, if balance_op::USER it will be the user id and so on.
     * @param string $desc extra description for the reason of the debit, not optional if the reason is OTHER
     * @param bool $neg if the operation allows negative balance or not.
     * @return bool true on success and false in failure.
     * @throws \moodle_exception
     */
    public function debit($amount, $for = self::USER, $thingid = 0, $desc = '', $neg = false) {
        global $DB, $USER;
        if ($for == self::USER) {
            $charger = !empty($thingid) ? $thingid : $USER->id;
        } else {
            $charger = !empty($USER->id) && !isguestuser() ? $USER->id : $this->userid;
        }

        if (!is_numeric($amount) || $amount < 0) {
            return false;
        }

        $this->amount = $amount;

        $this->set_category(self::DEBIT, $for, $thingid);

        $description = $this->get_debit_description($for, $thingid, $desc);

        if ($this->source == self::WP) {
            $before = $this->get_main_balance();

            $this->reset();
            $wordpress = new wordpress;

            $response = $wordpress->debit($this->userid, $amount, $desc, $charger);

            if (!is_numeric($response)) {
                return false;
            }

            $newbalance = $this->get_main_balance();
            $newnonrefund = $this->get_main_nonrefundable();
            // No debit occurs.
            if ($newbalance != $before - $amount) {
                return false;
            }
        } else if ($this->source == self::MOODLE) {
            $before = $this->get_valid_balance();

            $newbalance = $before - $amount;

            if ($newbalance < 0 && !$neg) {
                // This is mean that value to debit is greater than the balance and the new balance is negative.
                $a = ['amount' => $amount, 'before' => $before];
                throw new \moodle_exception('negativebalance', 'enrol_wallet', '', $a);
            }

            $remain = $amount;
            if (!$this->catenabled) {
                $this->total_cut($amount);
            } else if (!empty($this->catop)) {
                $remain = $this->catop->deduct($amount);
                $this->freecut += $this->catop->get_free_cut();
                $this->details['catbalance'] = $this->catop->details;

                if (!empty($remain)) {
                    $this->cut_from_main($remain);
                }

            } else {
                $this->cut_from_main($amount);
            }

            $this->update();
            $this->reset();

            $newbalance = $this->get_valid_balance();
            $newnonrefund = $this->get_valid_nonrefundable();
        }

        // No debit occurs.
        if ($newbalance != $before - $amount) {
            debugging("no debit occur");
        }

        $recorddata = [
            'userid'      => $this->userid,
            'type'        => 'debit',
            'amount'      => $amount,
            'balbefore'   => $before,
            'balance'     => $newbalance,
            'norefund'    => $newnonrefund,
            'category'    => $this->catid ?? 0,
            'opby'        => $for,
            'thingid'     => $thingid,
            'descripe'    => $description,
            'timecreated' => time(),
        ];

        $this->transactionid = $DB->insert_record('enrol_wallet_transactions', $recorddata);

        (new notifications)->transaction_notify($recorddata);

        $this->trigger_transaction_event('debit', $charger, $description, false);

        return true;
    }

    /**
     * Get the description of the debit operation based on the reason.
     *
     * @param string $for The reason for deduction {@see ::debit}
     * @param int $thingid
     * @param string $desc extra description
     * @return string
     */
    private function get_debit_description($for, $thingid, $desc) {
        global $DB, $USER;

        $a = ['amount' => $this->amount];
        switch($for) {
            case self::USER:
                $chargerid = !empty($thingid) ? $thingid : $USER->id;
                $charger = \core_user::get_user($chargerid, '*', MUST_EXIST);
                $a['charger'] = fullname($charger);
                $description = get_string('debitdesc_user', 'enrol_wallet', $a);
                break;
            case self::D_ENROL_COURSE:
                $this->courseid = $thingid;
                $course = get_course($thingid);
                $a['coursename'] = $course->fullname;
                $description = get_string('debitdesc_course', 'enrol_wallet', $a);
                break;
            case self::D_ENROL_INSTANCE:
                $helper = $this->helper ?? new instance($thingid, $this->userid);
                $course = $helper->get_course();
                $this->courseid = $course->id;
                $a['coursename'] = $course->fullname;
                $a['instance'] = $helper->get_name();
                $description = get_string('debitdesc_course', 'enrol_wallet', $a);
                break;
            case self::D_CM_ACCESS:
                $helper = $this->helper ?? new cm($thingid, $this->userid);
                $module = $helper->cm;
                $course = $helper->get_course();
                $this->courseid = $course->id;
                $name = $course->fullname;
                $name .= ': ';
                $name .= get_string('module', 'availability_wallet');
                $name .= '(' . $module->name . ')';
                $description = get_string('debitdesc', 'availability_wallet', $name);
                break;
            case self::D_SECTION_ACCESS:
                $helper = $this->helper ?? new section($thingid, $this->userid);
                $section = $helper->section;
                $course = $helper->get_course();
                $this->courseid = $course->id;
                $name = $course->fullname;
                $name .= ': ';
                $name .= get_string('section');
                $name .= (!empty($section->name)) ? "($section->name)" : "($section->section)";
                $description = get_string('debitdesc', 'availability_wallet', $name);
                break;
            case self::D_AUTH_FEE:
                $desc = get_string('debitfee_desc', 'auth_wallet');
                break;
            case self::D_AUTH_EXTRA_FEE:
                $description = get_string('debitextrafee_desc', 'auth_wallet');
                break;
            case self::OTHER:
            default:
                $description = '';
        }

        if (empty($description) && !empty($desc)) {
            $description = $desc;
        } else if (!empty($desc)) {
            $description .= ', '.$desc;
        } else if (empty($description)) {
            // Should not happen.
            $a['charger'] = fullname($USER);
            $description = get_string('debitdesc_user', 'enrol_wallet', $a);
        }

        return $description;
    }

    /**
     * Add a certain amount to the wallet balance.
     *
     * @param float $amount the amount to be added
     * @param string $by the method by which the credit done, should be one of constants C_***, USER or OTHER
     * @param int $thingid the userid in case that the operation done manually by a user or courseid in case of award or cashback
     * @param string $desc description of the reason of credit.
     * @param bool $refundable this amount is refundable or not.
     * @param bool $trigger trigger the transaction event or not.
     * @return bool
     */
    public function credit($amount, $by = self::OTHER, $thingid = 0, $desc = '', $refundable = true, $trigger = true) {
        global $DB;
        if (in_array($by, [self::USER, self::C_TRANSFER, self::C_REFERRAL])) {
            $charger = $thingid;
        } else {
            $charger = $this->userid;
        }

        if (!is_numeric($amount) || $amount < 0) {
            return false;
        }

        $this->amount = $amount;

        $this->set_category(self::CREDIT, $by, $thingid);

        $description = $this->get_credit_description($by, $thingid, $desc);

        // Turn all credit operations to nonrefundable if refund settings not enabled.
        $refundenabled = get_config('enrol_wallet', 'enablerefund');

        if (empty($refundenabled)) {
            $refundable = false;
        }

        if ($this->source == self::WP) {
            $before = $this->get_main_balance();
            $this->reset();

            $wordpress = new wordpress;
            $responsedata = $wordpress->credit($amount, $this->userid, $description, $charger);

            if (is_string($responsedata)) {
                debugging($responsedata);
                return false;
            }
            $newbalance = $this->get_main_balance();
            $newnonrefund = $this->get_main_nonrefundable();
        } else if (!empty($this->catop) && $this->catenabled) {
            $before = $this->catop->get_balance();
            $this->catop->add($amount, $refundable, $this->is_free($by, $refundable));
            $this->update();
            $newbalance = $this->catop->get_balance();
            $newnonrefund = $this->catop->get_non_refundable_balance();
        } else {
            $before = $this->get_main_balance();
            $this->add_to_main($amount, $refundable, $this->is_free($by, $refundable));
            $this->update();
            $newbalance = $this->get_main_balance();
            $newnonrefund = $this->get_main_nonrefundable();
        }

        // Check if it is a valid operation done.
        if ($newbalance <= $before) {
            return false;
        }

        $recorddata = [
            'userid'      => $this->userid,
            'type'        => 'credit',
            'amount'      => $amount,
            'balbefore'   => $before,
            'balance'     => $newbalance,
            'norefund'    => $newnonrefund,
            'category'    => $this->catid ?? 0,
            'opby'        => $by,
            'thingid'     => $thingid,
            'descripe'    => $description,
            'timecreated' => time(),
        ];

        $this->transactionid = $DB->insert_record('enrol_wallet_transactions', $recorddata);

        if ($refundable) {
            $this->queue_transaction_transformation();
        }

        (new notifications)->transaction_notify($recorddata);
        if ($trigger) {
            $this->trigger_transaction_event(self::CREDIT, $charger, $description, $refundable);
        }

        return $this->apply_conditional_discount($by);
    }

    /**
     * Check if this type of credit transaction should be marked
     * as free credit.
     * @param string $by
     * @param bool $refundable
     * @return bool
     */
    private function is_free($by, $refundable) {
        if ($refundable) {
            return false;
        }
        switch ($by) {
            case self::USER:
            case self::C_PAYMENT:
            case self::C_TRANSFER:
            case self::C_VC:
            case self::OTHER:
            case self::C_COUPON:
                return false;
            case self::C_ROLLBACK:
            case self::C_DISCOUNT:
            case self::C_UNENROL:
            case self::C_ACCOUNT_GIFT:
            case self::C_REFERRAL:
            case self::C_AWARD:
            case self::C_CASHBACK:
                return true;
            default:
                return false;
        }
    }
    /**
     * Get the discription of the credit transaction.
     *
     * @param string $by
     * @param int $thingid
     * @param string $desc
     * @return string
     */
    private function get_credit_description($by, $thingid, $desc) {
        $description = $desc;
        switch ($by) {
            case self::C_CASHBACK:
                $course = get_course($thingid);
                $this->courseid = $course->id;
                $description = get_string('cashbackdesc', 'enrol_wallet', $course->fullname);
                break;
            case self::C_ACCOUNT_GIFT:
                $a = new \stdClass;
                $a->userid = $this->userid;
                $a->time = userdate(time());
                $a->amount = $this->amount;
                $description = get_string('giftdesc', 'enrol_wallet', $a);
                break;
            case self::C_DISCOUNT:
            case self::USER:
            case self::C_TRANSFER:
            case self::C_AWARD:
            case self::C_COUPON:
            case self::C_PAYMENT:
            case self::C_ROLLBACK:
            case self::C_UNENROL:
            case self::C_REFERRAL:
            case self::C_VC:
            default:
        }
        return $description;
    }

    /**
     * Automatic set the category according to the reason of operation.
     *
     * @param string $op credit or debit
     * @param string $reason
     * @param int $thingid
     * @return void
     */
    private function set_category($op, $reason, $thingid) {
        if ($this->source == self::WP || !empty($this->catop)) {
            return;
        }
        if (!empty($this->catop)) {
            return;
        }

        if ($op == self::DEBIT) {
            switch($reason) {
                case self::D_ENROL_COURSE:
                    $this->courseid = $thingid;
                    $category = get_course($thingid)->category;
                    break;
                case self::D_ENROL_INSTANCE:
                    $this->helper = new instance($thingid, $this->userid);
                    $category = $this->helper->get_course_category();
                    break;
                case self::D_CM_ACCESS:
                    $this->helper = new cm($thingid, $this->userid);
                    $category = $this->helper->get_course_category();
                    break;
                case self::D_SECTION_ACCESS:
                    $this->helper = new section($thingid, $this->userid);
                    $category = $this->helper->get_course_category();
                    break;
                default:
            }
            if (!empty($category)) {
                $this->catid = (is_number($category)) ? $category : $category->id;
                $this->catop = new operations($category, $this->userid);
            }
        } else if ($op == self::CREDIT) {
            if (!$this->catenabled) {
                return;
            }
            switch ($reason) {
                case self::C_AWARD:
                case self::C_CASHBACK:
                    $this->courseid = $thingid;
                    $this->catid = get_course($thingid)->category;
                    $this->catop = new operations($this->catid, $this->userid);
                    break;
                case self::C_UNENROL:
                case self::C_ROLLBACK:
                    $this->helper = new instance($thingid, $this->userid);
                    $category = $this->helper->get_course_category();
                    $this->catid = $category->id;
                    $this->catop = new operations($category, $this->userid);
                    break;
                case self::C_PAYMENT:
                    // Credit done by payment, Check for the category id from the item.
                    // The category could be directly specified in the record or could be extracted from..
                    // ...the enrolment instance.
                    global $DB;
                    $item = $DB->get_record('enrol_wallet_items', ['id' => $thingid]);
                    $this->userid = $item->userid;
                    if (!empty($item->category)) {
                        $this->catid = $item->category;
                        $this->catop = new operations($this->catid, $this->userid);
                    } else if (!empty($item->instanceid)) {
                        $this->helper = new instance($item->instanceid, $this->userid);
                        $category = $this->helper->get_course_category();
                        $this->catid = $category->id;
                        $this->catop = new operations($category, $this->userid);
                    }
                    break;
                case self::C_COUPON:
                    // We need to check for the type of the coupon, only if it is category.
                    // No category coupons in wordpress.
                    if ($this->source == self::MOODLE) {
                        global $DB;
                        $record = $DB->get_record('enrol_wallet_coupons', ['id' => $thingid]);
                        if ($record && $record->type == 'category' && !empty($record->category)) {
                            $this->catid = $record->category;
                            $this->catop = new operations($this->catid, $this->userid);
                        }
                    }
                    break;
                case self::USER:
                case self::C_TRANSFER:
                    // The charger should specify the category id in the form.
                    break;
                case self::C_VC:
                    // NOTE update the vc form to pass the category id.
                case self::C_ACCOUNT_GIFT:
                case self::OTHER:
                default:
                    // Do nothing.
            }
        }
        if (!empty($this->helper) && empty($this->courseid)) {
            $this->courseid = $this->helper->courseid;
        }
    }

    /**
     * Apply the conditional discount rule to credit the user with the rest amount.
     * @param string $by The reason for credit.
     * @return bool
     */
    private function apply_conditional_discount($by) {
        global $DB, $USER;
        $forbidden = [
            self::C_ACCOUNT_GIFT,
            self::C_AWARD,
            self::C_CASHBACK,
            self::C_DISCOUNT,
            self::C_REFERRAL,
            self::C_UNENROL,
            self::C_TRANSFER,
        ];
        if (in_array($by, $forbidden)) {
            return true;
        }

        $amount  = $this->amount;

        list($rest, $condition) = discounts::get_the_rest($amount, $this->catid ?? 0);

        if (empty($rest)) {
            return true;
        }

        $desc = get_string('conditionaldiscount_desc', 'enrol_wallet', ['rest' => $rest, 'condition' => $condition]);
        // Credit the user with the rest amount.
        return $this->credit($rest, self::C_DISCOUNT, 0, $desc, false);
    }

    /**
     * Get how much amount has been cut from the free balance.
     * @param bool $reset
     * @return float
     */
    protected function get_free_cut($reset = true) {
        $return = $this->freecut;
        if ($reset) {
            $this->freecut = 0;
        }
        return $return;
    }

    /**
     * Apply cashback after course purchase.
     * Called from enrol_self method
     * @return void
     */
    public function apply_cashback() {
        // Now apply the cashback if enabled.
        $cashbackenabled = get_config('enrol_wallet', 'cashback');

        if ($cashbackenabled) {
            $percent = get_config('enrol_wallet', 'cashbackpercent');

            $value = $this->amount * $percent / 100;
            $this->credit($value, self::C_CASHBACK, $this->courseid, '', false, false);

            // Trigger cashback event.
            $eventdata = [
                'context'       => \context_course::instance($this->courseid),
                'courseid'      => $this->courseid,
                'objectid'      => $this->transactionid,
                'userid'        => $this->userid,
                'relateduserid' => $this->userid,
                'other'         => [
                        'amount'   => $value,
                        'original' => $this->amount,
                ],
            ];
            $event = \enrol_wallet\event\cashback_applied::create($eventdata);
            $event->trigger();
        }
    }

    /**
     * Triggering transactions event.
     *
     * @param string $type credit or debit
     * @param int $charger id of the charger user
     * @param string $desc reason of the transaction
     * @param bool $refundable is the transaction is refundable
     * @return void
     */
    private function trigger_transaction_event($type, $charger, $desc, $refundable) {

        if (empty($this->courseid)) {
            $context = \context_system::instance();
        } else {
            $context = \context_course::instance($this->courseid);
        }

        $eventarray = [
                        'context'       => $context,
                        'objectid'      => $this->transactionid,
                        'userid'        => $charger,
                        'relateduserid' => $this->userid,
                        'other' => [
                                    'type'       => $type,
                                    'amount'     => $this->amount,
                                    'refundable' => $refundable,
                                    'freecut'    => $type === self::DEBIT ? $this->get_free_cut(false) : 0,
                                    'desc'       => $desc,
                                    ],
                    ];

        $event = transactions_triggered::create($eventarray);
        $event->trigger();
    }

    /**
     * Queue the task to Transform the amount of a certain credit transaction to be nonrefundable
     * after the grace period is over.
     * @return void
     */
    private function queue_transaction_transformation() {
        $period = get_config('enrol_wallet', 'refundperiod');

        if (empty($period)) {
            return;
        }

        $runtime = time() + $period;

        $task = new turn_non_refundable;
        $task->set_custom_data(
                [
                    'id'     => $this->transactionid,
                    'userid' => $this->userid,
                    'amount' => $this->amount,
                    'catid'  => $this->catid ?? 0,
                ]
            );

        $task->set_next_run_time($runtime);

        \core\task\manager::queue_adhoc_task($task);
    }

    /**
     * Used to transform a certain amount from refundable to nonrefundable.
     * @param float $amount
     */
    public function turn_to_nonrefundable($amount) {
        if ($this->source == self::WP) {
            return;
        }
        if (!empty($this->catop)) {
            $transform = min($amount, $this->catop->get_refundable_balance());
            $this->catop->deduct($transform);

            $this->update();

            $this->catop->add($transform, false);

            $this->details['catbalance'] = $this->catop->details;

        } else {
            $transform = min($amount, $this->get_main_refundable());
            $this->cut_from_main($transform);
            $this->update();
            $this->add_to_main($transform, false);
        }
        $this->update();
    }

    /**
     * Transfer balance to another user.
     * @param \stdClass $data the data submited by the form.
     * @param \enrol_wallet\form\transfer_form $mform
     * @return string
     */
    public function transfer_to_other($data, $mform = null) {
        global $USER, $CFG;

        if (empty($mform)) {
            $mform = new \enrol_wallet\form\transfer_form;
        }

        $config = $mform->config;
        if (empty($config->transfer_enabled) || $this->userid != $USER->id) {
            throw new \moodle_exception('transfer_notenabled', 'enrol_wallet');
        }

        if (!$mform->get_data()) {
            $errors = $mform->validation((array)$data, []);
            if (!empty($errors)) {
                return reset($errors);
            }
        }

        $email    = $data->email;
        $amount   = $data->amount;
        $catid    = $data->category ?? 0;

        if ($this->catid != $catid) {
            $this->catid = $catid;
            if (!empty($this->catid)) {
                $this->catop = new operations($this->catid, $this->userid);
            } else {
                unset($this->catop);
            }
        }

        $receiver = \core_user::get_user_by_email($email);

        list($debit, $credit) = $mform->get_debit_credit($amount);
        $fee = abs($credit - $debit);

        $unknownerror = get_string('error');
        // Debit the sender.
        if (!$this->debit($debit, self::USER, $receiver->id)) {
            return $unknownerror;
        }

        $this->get_free_cut();
        // Credit the receiver.
        $a = [
            'fee'      => $fee,
            'amount'   => $credit,
            'receiver' => fullname($receiver),
        ];
        $desc = get_string('transferop_desc', 'enrol_wallet', $a);
        $op = new balance_op($receiver->id, $catid);
        $done = true;
        try {
            $done = $op->credit($credit, self::C_TRANSFER, $receiver->id, $desc, false);
        } catch (\moodle_exception $e) {
            $done = false;
            $unknownerror = $e->getMessage();
        }
        if (!$done) {
            $this->credit($debit, self::C_ROLLBACK);
            return $unknownerror;
        }
        return $desc;
    }

    /**
     * Get the transaction id in the transactions table
     * must be called only after credit or debit operations.
     * @return int|null
     */
    public function get_transaction_id() {
        return $this->transactionid ?? null;
    }

    /**
     * Create a balance operation class to obtain balance data and perform operations for a given user
     * by providing the enrol_wallet instance or its id.
     * @param int|\stdClass $instance
     * @param int $userid 0 means the current user.
     * @return self
     */
    public static function create_from_instance($instance, $userid = 0) {
        $util = new instance($instance, $userid);
        $category = $util->get_course_category();
        return new self($userid, $category);
    }

    /**
     * Create a balance operation class to obtain balance data and perform operations for a given user
     * by providing the course module record or its id.
     * @param int|\stdClass $cm
     * @param int $userid 0 means the current user.
     * @return self
     */
    public static function create_from_cm($cm, $userid = 0) {
        $util = new cm($cm, $userid);
        $category = $util->get_course_category();
        return new self($userid, $category);
    }

    /**
     * Create a balance operation class to obtain balance data and perform operations for a given user
     * by providing the course section record or its id.
     * @param int|\stdClass $section
     * @param int $userid 0 means the current user.
     * @return self
     */
    public static function create_from_section($section, $userid = 0) {
        $util = new section($section, $userid);
        $category = $util->get_course_category();
        return new self($userid, $category);
    }
}
