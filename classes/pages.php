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

namespace enrol_wallet;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

use moodle_url;
use html_table;
use html_writer;
use MoodleQuickForm;
use DOMDocument;
use core_user;
use single_button;
use core_course_category;
use enrol_wallet\util\balance;
use enrol_wallet\util\balance_op;
use enrol_wallet\util\discount_rules;
use enrol_wallet\util\offers;

/**
 * Class pages
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pages {
    /**
     * Output the content of referral page
     * @param int $userid
     */
    public static function process_referral_page($userid = 0) {
        global $DB, $USER, $CFG, $OUTPUT;
        if (!(bool)get_config( 'enrol_wallet', 'referral_enabled')) {
            echo 'Referrals not enabled';
            return;
        }

        if (empty($userid)) {
            $user = $USER;
        } else {
            $user = core_user::get_user($userid);
        }

        $isparent = false;
        if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
            require_once("$CFG->dirroot/auth/parent/auth.php");
            $authparent = new \auth_plugin_parent;
            $isparent = $authparent->is_parent($user);
        }

        if ($isparent) {
            echo 'Parents not allow to access referral program.';
            return;
        }

        $amount = get_config('enrol_wallet', 'referral_amount');
        $maxref = get_config('enrol_wallet', 'referral_max');

        $exist = $DB->get_record('enrol_wallet_referral', ['userid' => $user->id]);
        if (!$exist) {
            $data = (object)[
                'userid' => $user->id,
                'code'   => random_string(15) . $user->id,
            ];
            $DB->insert_record('enrol_wallet_referral', $data);
            $exist = $DB->get_record('enrol_wallet_referral', ['userid' => $user->id]);
        }

        $holdgift = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $user->username]);

        $refusers = $DB->get_records('enrol_wallet_hold_gift', ['referrer' => $user->id]);

        $output = '';
        if (!empty($holdgift)) {
            $referrer = core_user::get_user($holdgift->referrer);
            $a = [
                'name' => fullname($referrer),
                'amount' => format_float($holdgift->amount, 2),
            ];
            $message = get_string('referral_holdgift', 'enrol_wallet', );
            $output .= $OUTPUT->notification($message);
        }

        if (!empty($refusers)) {
            $table = new html_table;
            $headers = [
                get_string('user'),
                get_string('status'),
                get_string('referral_amount', 'enrol_wallet'),
                get_string('referral_timecreated', 'enrol_wallet'),
                get_string('referral_timereleased' , 'enrol_wallet'),
            ];
            $table->data[] = $headers;
            foreach ($refusers as $data) {
                $referred = core_user::get_user_by_username($data->referred);
                $status = empty($data->released) ? get_string('referral_hold', 'enrol_wallet')
                                                 : get_string('referral_done', 'enrol_wallet');
                $table->data[] = [
                    $referred->firstname . ' ' . $referred->lastname,
                    $status,
                    format_float($data->amount, 2),
                    userdate($data->timecreated),
                    !empty($data->timemodified) ? userdate($data->timemodified) : '',
                ];
            }
            $output .= html_writer::table($table);
        } else {
            $message = get_string('noreferraldata', 'enrol_wallet');
            $output .= $OUTPUT->notification($message);
        }

        $mform = new MoodleQuickForm('referral_info', 'get', null);

        $signup = new moodle_url('/login/signup.php', ['refcode' => $exist->code]);
        $mform->addElement('static', 'refurl', get_string('referral_url', 'enrol_wallet'), $signup->out(false));
        $mform->addHelpButton('refurl',  'referral_url',  'enrol_wallet');

        $mform->addElement('static', 'refcode', get_string('referral_code', 'enrol_wallet'), $exist->code);
        $mform->addHelpButton('refcode',  'referral_code',  'enrol_wallet');

        $mform->addElement('text', 'refamount', get_string('referral_amount', 'enrol_wallet'));
        $mform->addHelpButton('refamount', 'referral_amount', 'enrol_wallet');
        $mform->setType('refamount', PARAM_FLOAT);
        $mform->setConstant('refamount', $amount);

        $mform->addElement('hidden', 'disable');
        $mform->setType('disable', PARAM_INT);
        $mform->setConstant('disable', 0);

        $mform->disabledIf('refamount',  'disable',  'neq',  1);

        if (!empty($maxref)) {
            $mform->addElement('text', 'refremain', get_string('referral_remain', 'enrol_wallet'));
            $mform->setType('refremain', PARAM_INT);
            $mform->addHelpButton('refremain', 'referral_remain', 'enrol_wallet');
            $mform->setConstant('refremain', $maxref - $exist->usetimes);
            $mform->disabledIf('refremain',  'disable',  'neq',  1);
        }

        echo $OUTPUT->heading(get_string('referral_past', 'enrol_wallet'));

        echo $output;

        echo $OUTPUT->heading(get_string('referral_data', 'enrol_wallet'));

        $mform->display();
    }

    /**
     * Return a confirmation message for charging another user wallet.
     * @param array|stdClass $data the submitted data.
     * @param moodle_url $returnurl
     * @param moodle_url $pageurl
     * @return string
     */
    public static function get_charger_confirm($data, $returnurl, $pageurl = null) {
        global $OUTPUT, $PAGE;
        if (!has_capability('enrol/wallet:creditdebit', \context_system::instance())) {
            return '';
        }

        if (empty($pageurl)) {
            $pageurl = $PAGE->url;
        }

        $confirmurl = new moodle_url($pageurl, $data);
        $confirmurl->param('confirm', true);
        $confirmurl->param('return', $returnurl->out_as_local_url());
        $confirmbutton = new single_button($confirmurl, get_string('confirm'), 'post');
        $cancelbutton = new single_button($returnurl, get_string('cancel'), 'post');

        if (!empty($data['category'])) {
            $category = core_course_category::get($data['category'], IGNORE_MISSING);
        } else {
            $category = null;
        }

        $balance = new balance($data['userlist'], $category);
        $userbalance = $balance->get_valid_balance();

        $user = core_user::get_user($data['userlist']);
        $name = html_writer::link(new moodle_url('/user/view.php', ['id' => $user->id]), fullname($user), ['target' => '_blank']);

        $a = [
            'name' => $name,
            'amount' => $data['value'],
            'balance' => $userbalance,
        ];
        $a['category'] = !(empty($category)) ? $category->get_nested_name(false) : get_string('site');

        $negativewarn = false;
        switch ($data['op']) {
            case 'debit':
                $a['after'] = ($userbalance - $data['value']);
                if ($a['after'] < 0) {
                    $negativewarn = true;
                }
                $msg = get_string('confirm_debit', 'enrol_wallet', $a);
                break;
            case 'credit':
                $msg = get_string('confirm_credit', 'enrol_wallet', $a);
                list($extra, $condition) = discount_rules::get_the_rest($data['value'], $data['category']);
                if (!empty($extra)) {
                    $msg .= '<br>'.get_string('confirm_additional_credit', 'enrol_wallet', $extra);
                }
                break;
            default:
                $msg = '';
        }
        if ($negativewarn) {
            $warning = get_string('confirm_negative', 'enrol_wallet');
            $msg .= $OUTPUT->notification($warning, 'error', false);
        }
        return $OUTPUT->confirm($msg, $confirmbutton, $cancelbutton);
    }

    /**
     * Out the content of transfer page.
     * @param moodle_url|string $url
     * @return void
     */
    public static function process_transfer_page($url) {
        $mform = new \enrol_wallet\form\transfer_form();

        if ($data = $mform->get_data()) {

            $catid  = $data->category;
            $op = new balance_op(0, $catid);

            $msg = $op->transfer_to_other($data, $mform);
            if (stristr($msg, 'error')) {
                $type = 'error';
            } else {
                $type = 'success';
            }
            // All done.
            redirect($url, $msg, null, $type);

        } else {
            $mform->display();
        }
    }

    /**
     * Return the content of offers page.
     * @return string
     */
    public static function get_offers_content() {
        global $DB, $PAGE, $OUTPUT;
        $renderer = $PAGE->get_renderer('core', 'course');

        $courses = offers::get_courses_with_offers();

        $free = '';
        $withoffers = '';

        $dom = new DOMDocument();
        $injected = new DOMDocument();
        libxml_use_internal_errors(true);

        foreach ($courses as $course) {
            $coursebox = mb_convert_encoding($renderer->course_info_box($course), 'HTML-ENTITIES', "UTF-8");
            $dom->loadHTML($coursebox);

            $fragment = $dom->createDocumentFragment();
            foreach ($course->offers as $offer) {
                $injected->loadHTML(html_writer::div($offer, 'card-body'));
                $injectednode = $dom->importNode($injected->documentElement, true);
                $fragment->appendChild($injectednode);
            }

            $innercoursenodes = $dom->getElementsByTagName('div');
            $innercoursenode = $innercoursenodes->item(1);

            $innercoursenode->appendChild($fragment);

            if ($course->free) {
                $free .= $dom->saveHTML($innercoursenode);
            } else {
                $withoffers .= $dom->saveHTML($innercoursenode);
            }
        }

        $out = '';

        $rules = discount_rules::get_the_discount_line(-1);
        if (!empty($rules)) {
            $out .= $OUTPUT->heading(get_string('topupoffers', 'enrol_wallet'));
            $out .= $OUTPUT->box(get_string('topupoffers_desc', 'enrol_wallet'));
            $out .= $rules;
            $out .= "<hr/>";
        }

        $config = get_config('enrol_wallet');
        if (!empty($config->cashback)) {
            $cashbackvalue = (float)$config->cashbackpercent;
            if ($cashbackvalue > 0 && $cashbackvalue <= 100) {
                $out .= $OUTPUT->heading(get_string('cashback', 'enrol_wallet'));
                $out .= $OUTPUT->box(get_string('cashback_desc', 'enrol_wallet', $cashbackvalue));
                $out .= "<hr/>";
            }
        }

        if (!empty($config->referral_enabled) && (float)$config->referral_amount > 0) {
            $out .= $OUTPUT->heading(get_string('referral_program', 'enrol_wallet'));
            $url = new moodle_url('/enrol/wallet/extra/referral.php');
            $text = get_string('clickhere');
            $link = html_writer::link($url, $text);
            $out .= $OUTPUT->box(get_string('referral_site_desc', 'enrol_wallet') . $link);
            $out .= "<hr/>";
        }

        if (!empty($free)) {
            $out .= $OUTPUT->heading(get_string('freecourses', 'enrol_wallet'));
            $out .= html_writer::div($free, 'courses courses-flex-cards');
            $out .= "<hr/>";
        }

        if (!empty($withoffers)) {
            $out .= $OUTPUT->heading(get_string('courseswithdiscounts', 'enrol_wallet'));
            $out .= html_writer::div($withoffers, 'courses courses-flex-cards');
            $out .= "<hr/>";
        }
        return $out;
    }
}
