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
 * wallet enrolment plugin callback functions to extend navigation and profile.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * To add the category and node information into the my profile page.
 * If is a regular user, it show the balance, refund policy and topping up options.
 * Regular users can't see the balance of others unless they have the capability 'enrol/wallet:viewotherbalance'.
 * If the user has the capability to credit others, the charger form appears in his own profile.
 *
 * @param core_user\output\myprofile\tree $tree The myprofile tree to add categories and nodes to.
 * @param stdClass                        $user The user object that the profile page belongs to.
 * @param bool                            $iscurrentuser If the $user object is the current user.
 * @param stdClass                        $course The course to determine if we are in a course context or system context.
 * @return void
 */
function enrol_wallet_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    require_once(__DIR__.'/locallib.php');
    global $OUTPUT;
    $context = context_system::instance();
    $cancredit = has_capability('enrol/wallet:creditdebit', $context);

    // Only the user with capability could see other user's ballance.
    if (!$iscurrentuser && !has_capability('enrol/wallet:viewotherbalance', $context)) {
        return;
    }

    // Add the main category.
    $wdcategory = new core_user\output\myprofile\category('walletcreditdisplay',
                                                    get_string('walletcredit', 'enrol_wallet'),
                                                    'contact',
                                                    'enrol_wallet_card');
    $tree->add_category($wdcategory);

    if (!$cancredit || !$iscurrentuser) {
        // First node for displaying the balance information.
        $render1 = enrol_wallet_display_current_user_balance($user->id);
        $node1 = new core_user\output\myprofile\node('walletcreditdisplay',
                                                    'walletcreditnode',
                                                    '',
                                                    null,
                                                    null,
                                                    $render1,
                                                    null,
                                                    'enrol_wallet_display_node');
        $tree->add_node($node1);

        // Second node to display the topping up options.
        $render2 = enrol_wallet_display_topup_options();
        if (!empty($render2) && $iscurrentuser) {
            $node2 = new core_user\output\myprofile\node('walletcreditdisplay',
                                                        'wallettopupnode',
                                                        '',
                                                        null,
                                                        null,
                                                        $render2,
                                                        null,
                                                        'enrol_wallet_display_node');
            $tree->add_node($node2);
        }

    } else {
        // Node 3 to display charger form and coupon view and generation pages links.
        $render3 = '';
        $form = enrol_wallet_display_charger_form();
        $render3 .= $OUTPUT->box($form);
        $render3 .= enrol_wallet_display_coupon_urls();
        $node3 = new core_user\output\myprofile\node('walletcreditdisplay',
                                                    'walletchargingnode',
                                                    '',
                                                    null,
                                                    null,
                                                    $render3,
                                                    null,
                                                    'enrol_wallet_display_node');
        $tree->add_node($node3);
    }
}

/**
 * Adding extra options to the navigation in the frontpage so manager can control wallet easily.
 *
 * @param navigation_node $parentnode
 * @param stdClass $course
 * @param context_course $context
 * @return void
 */
function enrol_wallet_extend_navigation_frontpage(navigation_node $parentnode, stdClass $course, context_course $context) {
    $context = context_system::instance();

    $captransactions = has_capability('enrol/wallet:transaction', $context);
    $capcredit       = has_capability('enrol/wallet:creditdebit', $context);
    $capbulkedit     = has_capability('enrol/wallet:bulkedit', $context);
    $capcouponview   = has_capability('enrol/wallet:viewcoupon', $context);
    $capcouponcreate = has_capability('enrol/wallet:createcoupon', $context);
    $hassiteconfig   = has_capability('moodle/site:config', $context);

    $any = ($captransactions || $capcredit || $capbulkedit || $capcouponview || $capcouponcreate);
    $ismoodle = (get_config('enrol_wallet', 'walletsource') == enrol_wallet\transactions::SOURCE_MOODLE);

    if ($hassiteconfig && $any) {

        $node = navigation_node::create(
            get_string('bulkfolder', 'enrol_wallet'),
            new moodle_url('/admin/category.php', ['category' => 'enrol_wallet_settings']),
            navigation_node::TYPE_CONTAINER,
            'Wallet',
            'extrawallet',
            null
        );
        $parentnode->add_node($node);

    } else {

        if ($capcredit) {
            // Adding page to charge wallets of other users.
            $node = navigation_node::create(
                get_string('chargingoptions', 'enrol_wallet'),
                new moodle_url('/enrol/wallet/extra/charger.php'),
                navigation_node::TYPE_CUSTOM,
                'enrol_wallet_charging',
                'enrol_wallet_charging'
            );
            $node->set_show_in_secondary_navigation(true);

            $parentnode->add_node($node);
        }

        if ($captransactions) {
            // Adding page to charge wallets of other users.
            $node = navigation_node::create(
                get_string('transactions', 'enrol_wallet'),
                new moodle_url('/enrol/wallet/extra/transaction.php'),
                navigation_node::TYPE_CUSTOM,
                'wallettransactions',
                'wallettransactions'
            );
            $parentnode->add_node($node);
        }

        if ($capcouponcreate && $ismoodle) {
            // Adding page to generate coupons.
            $node = navigation_node::create(
                get_string('coupon_generation', 'enrol_wallet'),
                new moodle_url('/enrol/wallet/extra/coupon.php'),
                navigation_node::TYPE_CUSTOM,
                'enrol_wallet_coupongenerate',
                'enrol_wallet_coupongenerate'
            );
            $parentnode->add_node($node);
        }

        if ($capcouponview && $ismoodle) {
            // Adding page to view coupons.
            $node = navigation_node::create(
                get_string('coupon_table', 'enrol_wallet'),
                new moodle_url('/enrol/wallet/extra/coupontable.php'),
                navigation_node::TYPE_CUSTOM,
                'enrol_wallet_coupontable',
                'enrol_wallet_coupontable'
            );
            $parentnode->add_node($node);
        }

        if ($capbulkedit) {
            // Bulk edit enrollments.
            $node = navigation_node::create(
                get_string('bulkeditor', 'enrol_wallet'),
                new moodle_url('/enrol/wallet/extra/bulkedit.php'),
                navigation_node::TYPE_CUSTOM,
                'enrol_bulkedit',
                'enrol_bulkedit'
            );
            $parentnode->add_node($node);

            // Bulk edit wallet instances.
            $node = navigation_node::create(
                get_string('walletbulk', 'enrol_wallet'),
                new moodle_url('/enrol/wallet/extra/bulkinstances.php'),
                navigation_node::TYPE_CUSTOM,
                'enrol_wallet_bulkedit',
                'enrol_wallet_bulkedit'
            );
            $parentnode->add_node($node);
        }
    }
}

/**
 * Extend the signup form to add the referral code.
 * @param MoodleQuickForm $mform
 * @return void
 */
function enrol_wallet_extend_signup_form(MoodleQuickForm $mform) {
    $refenabled = get_config('enrol_wallet', 'referral_enabled');
    $maxref     = get_config('enrol_wallet', 'referral_max');
    if (!$refenabled) {
        return;
    }
    // Add field for the referral code.
    $mform->addElement('text', 'refcode', get_string('referral_code', 'enrol_wallet'));
    $mform->addHelpButton('refcode', 'referral_code_signup', 'enrol_wallet');
    $mform->setType('refcode', PARAM_ALPHANUM);

    $refcode = optional_param('refcode', '', PARAM_ALPHANUM);
    if (!empty($refcode)) {
        global $DB;
        $mform->setDefault('refcode', $refcode);
        if (!empty($maxref)) {
            // Check if this code exceeds the max limit.
            $refrecord = $DB->get_record('enrol_wallet_referral', ['code' => $refcode]);
            if (!empty($refrecord) && $refrecord->usetimes >= $maxref) {
                $mform->addElement('static', 'refcode_warn', get_string('referral_exceeded', 'enrol_wallet', $refcode));
                $mform->updateElementAttr('refcode_warn', ['class' => 'notify notify-error']);
            }
        }
    }
}

/**
 * Validate the data from signup request to validate the referral code.
 * @param array $data
 * @return array<string>
 */
function enrol_wallet_validate_extend_signup_form($data) {
    $refenabled = get_config('enrol_wallet', 'referral_enabled');
    $maxref     = get_config('enrol_wallet', 'referral_max');
    $errors = [];
    if (!$refenabled || empty($data['refcode']) || empty($maxref)) {
        return $errors;
    }

    global $DB;
    // Check if this code exceeds the max limit.
    $refrecord = $DB->get_record('enrol_wallet_referral', ['code' => $data['refcode']]);
    if (empty($refrecord)) {
        $errors['refcode'] = get_string('referral_notexist', 'enrol_wallet', $data['refcode']);
    } else if ($refrecord->usetimes >= $maxref) {
        $errors['refcode'] = get_string('referral_exceeded', 'enrol_wallet', $data['refcode']);
    }

    return $errors;
}

/**
 * Function to update or create user in wordpress.
 * @param stdClass $user
 * @return void
 */
function enrol_wallet_update_wordpress_user($user) {
    // Check the wallet source first.
    $source = get_config('enrol_wallet', 'walletsource');
    if ($source == enrol_wallet\transactions::SOURCE_WORDPRESS) {
        // Create or update corresponding user in wordpress.
        $wordpress = new \enrol_wallet\wordpress;
        $wordpress->create_wordpress_user($user, $user->password);
    }
}
/**
 * Callback after user signup to create wordpress user and check referral data.
 * @param object $user // The data submitted from the signup form.
 * @return void
 */
function enrol_wallet_post_signup_requests($user) {

    // Referral program.
    $refenabled = get_config('enrol_wallet', 'referral_enabled');
    $maxref     = get_config('enrol_wallet', 'referral_max');
    $amount     = get_config('enrol_wallet', 'referral_amount');

    // Check the referral code.
    if (!empty($user->refcode) && $refenabled && !empty($amount)) {
        global $DB;
        $refrecord = $DB->get_record('enrol_wallet_referral', ['code' => $user->refcode]); // The code should be unique.
        // Check if the reference code is available and the user didn't exceed the available referral times.
        // If $maxref is zero means no limit.
        if (!empty($refrecord) && (empty($maxref) || $refrecord->usetimes < $maxref)) {
            // Update the record.
            if (!empty($refrecord->users)) {
                $users = json_decode($refrecord->users, true);
            } else {
                $users = [];
            }
            $users[] = $user->username;

            $update = (object)[
                'id'       => $refrecord->id,
                'usetimes' => $refrecord->usetimes + 1,
                'users'    => json_encode($users),
            ];
            $DB->update_record('enrol_wallet_referral', $update);
            // Insert the hold record.
            $hold = [
                'referrer'    => $refrecord->userid,
                'referred'    => $user->username,
                'amount'      => $amount,
                'timecreated' => time()
            ];
            $DB->insert_record('enrol_wallet_hold_gift', $hold);
        }
    }

    // Update or create wordpress user.
    enrol_wallet_update_wordpress_user($user);
}

/**
 * Callback after set new password to update it in wordpress.
 * @param object $data
 * @param object $user
 * @return void
 */
function enrol_wallet_post_set_password_requests($data, $user) {
    $user->password = $data->password;
    return enrol_wallet_update_wordpress_user($user);
}

/**
 * Callback after set new password to update it in wordpress
 * @param object $data
 * @return void
 */
function enrol_wallet_post_change_password_requests($data) {
    global $USER;
    $user = $USER;
    $user->password = $data->newpassword1;
    return enrol_wallet_update_wordpress_user($user);
}

/**
 * Callback function in every page to notify users for low balance.
 * @return void
 */
function enrol_wallet_before_standard_top_of_body_html() {
    global $USER;
    // Don't display notice for guests or logged out.
    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Check if notice is enabled.
    $notice = get_config('enrol_wallet', 'lowbalancenotice');
    if (empty($notice)) {
        return;
    }

    // Check the conditions.
    $condition = get_config('enrol_wallet', 'noticecondition');
    $balance = \enrol_wallet\transactions::get_user_balance($USER->id);
    if ($balance !== false && is_numeric($balance) && $balance <= (int)$condition) {
        // Display the warning.
        \core\notification::warning(get_string('lowbalancenotification', 'enrol_wallet', $balance));
    }
}

/**
 * Callback to login the user to wordpress.
 * @return void
 */
function enrol_wallet_after_require_login() {
    global $USER, $CFG, $SESSION;
    require_once($CFG->dirroot.'/login/lib.php');
    if (isguestuser() || empty($USER->id)) {
        return;
    }

    // Prevent multiple calls.
    $done = get_user_preferences('enrol_wallet_wploggedin', false, $USER);
    if ($done) {
        return;
    }

    if (isset($SESSION->wantsurl)) {
        $return = (new moodle_url($SESSION->wantsurl))->out(false);
    } else {
        $return = (new moodle_url('/'))->out(false);
    }

    $wordpress = new \enrol_wallet\wordpress;
    $wordpress->login_logout_user_to_wordpress($USER->id, 'login', $return);
}
