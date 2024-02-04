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
 * wallet enrolment plugin referral page.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');
require_once(__DIR__.'/../lib.php');
require_once($CFG->libdir.'/tablelib.php');
require_once($CFG->libdir.'/formslib.php');

$isparent = false;
if (file_exists("$CFG->dirroot/auth/parent/auth.php")) {
    require_once("$CFG->dirroot/auth/parent/auth.php");
    require_once("$CFG->dirroot/auth/parent/lib.php");
    $authparent = new auth_plugin_parent;
    $isparent = $authparent->is_parent($USER);
}

if ($isparent) {
    redirect(new moodle_url('/'), 'Parents not allow to access referral program.');
}

global $DB, $USER;
// Adding some security.
require_login();
$thisurl = new moodle_url('/enrol/wallet/extra/referral.php');

$amount = get_config('enrol_wallet', 'referral_amount');
$maxref = get_config('enrol_wallet', 'referral_max');

$exist = $DB->get_record('enrol_wallet_referral', ['userid' => $USER->id]);
if (!$exist) {
    $data = (object)[
        'userid' => $USER->id,
        'code' => random_string(15) . $USER->id,
    ];
    $DB->insert_record('enrol_wallet_referral', $data);
    $exist = $DB->get_record('enrol_wallet_referral', ['userid' => $USER->id]);
}

$PAGE->set_url($thisurl);

$context = context_user::instance($USER->id);
$PAGE->set_context($context);

$PAGE->set_title(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_heading(get_string('referral_user', 'enrol_wallet'));
$PAGE->set_pagelayout('frontpage');

$holdgift = $DB->get_record('enrol_wallet_hold_gift', ['referred' => $USER->username]);

$refusers = $DB->get_records('enrol_wallet_hold_gift', ['referrer' => $USER->id]);

$mform = new MoodleQuickForm('referral_info', 'get', $thisurl);

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
    $mform->disabledIf('refremain', 'disable', 'neq', 1);
}

echo $OUTPUT->header();

$referralAmount = get_config('enrol_wallet', 'referral_amount');
$a = new stdClass();
$a->amount = format_float($referralAmount, 2) . ' $';

?>

<div class='wrapper referral-page-content'>
    <div class='row' style='background-color: #D0EDE7;'>
        <div class='col-xs-12 col-md-12 col-lg-8'>
            <div>
                <h3 class='referral-title'><?php echo get_string('referral_header', 'enrol_wallet'); ?></h3>
                <p><?php echo get_string('referral_subheader', 'enrol_wallet', $a); ?></p>
                <div class='shareLink'>
                    <div class='permalink'>
                        <input class='textLink search-input' id='text' type='text' value='<?php echo $signup->out(false); ?>' readonly>
                        <span class='copyLink' id='copy' tooltip='<?php echo get_string('copy_to_clipboard', 'enrol_wallet'); ?>'>
                            <i class='fa-regular fa-copy'></i>
                        </span>
                    </div>
                </div>
                <div class='shareReferral'>
                    <div class='shareSocial'>
                        <h3 class='socialTitle'><?php echo get_string('share_referral', 'enrol_wallet'); ?></h3>
                        <ul class='socialList'>
                        <li><a href='#' onclick='shareOnFacebook("<?php echo $signup->out(false); ?>"); return false;'><i class='fa-brands fa-facebook-f'></i></a></li>
                        <li><a href='#' onclick='shareOnWhatsApp("<?php echo $signup->out(false); ?>"); return false;'><i class='fa-brands fa-whatsapp'></i></a></li>
                        <li><a href='#' onclick='shareOnViber("<?php echo $signup->out(false); ?>"); return false;'><i class='fa-brands fa-viber'></i></a></li>
                        <li><a href='mailto:?subject=<?php echo rawurlencode(get_string('referral_share_subject', 'enrol_wallet')); ?>&body=<?php echo rawurlencode(get_string('referral_share_body', 'enrol_wallet')); ?>'><i class='fa-solid fa-envelope'></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class='col-xs-12 col-md-12 col-lg-4'>
        </div>
    </div>
</div>

<div class="wrapper referral-page-past-invites">
<?php

// Check if there is a pending gift record
if (!empty($holdgift)) {
    $referrer = \core_user::get_user($holdgift->referrer);
    $a = new stdClass();
    $a->name = fullname($referrer);
    $a->amount = format_float($holdgift->amount, 2);
    $message = get_string('referral_holdgift', 'enrol_wallet', $a);
    echo $OUTPUT->notification($message);
}

// Checking if there is a list of users who have been used as referrals
if (!empty($refusers)) {
    echo $OUTPUT->heading(get_string('referral_past', 'enrol_wallet'));
    $table = new html_table();
    $table->head = [
        get_string('user'),
        get_string('status'),
        get_string('referral_amount', 'enrol_wallet'),
        get_string('referral_timecreated', 'enrol_wallet'),
        get_string('referral_timereleased', 'enrol_wallet')
    ];
    foreach ($refusers as $data) {
        $referred = \core_user::get_user_by_username($data->referred);
        $status = empty($data->released) ? get_string('referral_hold', 'enrol_wallet') : get_string('referral_done', 'enrol_wallet');
        $table->data[] = [
            fullname($referred),
            $status,
            format_float($data->amount, 2),
            userdate($data->timecreated),
            !empty($data->timemodified) ? userdate($data->timemodified) : get_string('referral_notyet', 'enrol_wallet')
        ];
    }
    echo html_writer::table($table);} else {
        // Display the message "No past invitations" only if there are no refusers
        echo $OUTPUT->heading(get_string('referral_past', 'enrol_wallet'));
        echo html_writer::div(get_string('noreferraldata', 'enrol_wallet'), 'notice-box');
    }
?>
</div>

<script>
var copyTooltip = '<?php echo get_string('referral_copy_to_clipboard', 'enrol_wallet'); ?>';
var copiedTooltip = '<?php echo get_string('referral_copied', 'enrol_wallet'); ?>';
var shareUrl = '<?php echo $signup->out(false); ?>';

function shareOnFacebook(url) {
    window.open('https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(shareUrl), 'facebook-share-dialog', 'width=800,height=600');
}

function shareOnWhatsApp(url) {
    window.open('https://wa.me/?text=' + encodeURIComponent(shareUrl));
}

function shareOnViber(url) {
    window.open('viber://forward?text=' + encodeURIComponent(shareUrl));
}

// Copy link
function copyText() {
    const copyButton = document.getElementById('copy');
    const input = document.getElementById('text');
    input.select(); //select input value
    document.execCommand('copy');
    copyButton.setAttribute('tooltip', copiedTooltip);
}

const resetTooltip = () => {
    const copyButton = document.getElementById('copy');
    copyButton.setAttribute('tooltip', copyTooltip);
};

document.addEventListener('DOMContentLoaded', function() {
    const copyButton = document.getElementById('copy');
    copyButton.addEventListener('click', copyText);
    copyButton.addEventListener('mouseleave', resetTooltip);
});
</script>

<style>
.shareReferral {
display: flex;
flex-flow: column;
width: 100%;
}

.shareSocial {
display: flex;
flex-flow: row;
align-items: center;
margin-bottom: 30px;
}
@media (max-width: 767px) {
.shareSocial {
flex-flow: column;
}
}
.shareSocial .socialTitle {
margin: 0 15px 0 0;
font-size: 20px;
}
@media (max-width: 767px) {
.shareSocial .socialTitle {
margin-bottom: 15px;
text-align: center;
}
}
.shareSocial .socialList {
list-style: none;
margin: 0;
padding: 0;
display: flex;
justify-content: flex-start;
justify-content: center;
flex-flow: row wrap;
}
.shareSocial .socialList li {
margin: 5px;
}
.shareSocial .socialList li:first-child {
padding-left: 0;
}
.shareSocial .socialList li a {
position: relative;
display: flex;
justify-content: center;
align-items: center;
width: 50px;
height: 50px;
border-radius: 100%;
text-decoration: none;
background-color: #999;
color: #fff;
transition: 0.35s;
}
.shareSocial .socialList li a i {
position: absolute;
top: 50%;
left: 50%;
transform-origin: top left;
transform: scale(1) translate(-50%, -50%);
transition: 0.35s;
}
.shareSocial .socialList li a:hover i {
transform: scale(1.5) translate(-50%, -50%);
}
.shareSocial .socialList li:nth-child(1) a {
background-color: #135cb6;
}
.shareSocial .socialList li:nth-child(2) a {
background-color: #075e54;
}
.shareSocial .socialList li:nth-child(3) a {
background-color: #59267c;
}
.shareSocial .socialList li:nth-child(4) a {
background-color: #111111;
}
.shareLink .permalink {
position: relative;
border-radius: 30px;
}
.shareLink .permalink .textLink {
padding: 12px 60px 12px 30px;
font-size: 14px;
letter-spacing: 0.3px;
width: 100%;
}}
.shareLink .permalink .copyLink {
position: absolute;
top: 50%;
right: 25px;
cursor: pointer;
transform: translateY(-50%);
}
.shareLink .permalink .copyLink {
position: absolute;
right: 14px;
top: 14px;
}
.shareLink .permalink .copyLink:hover:after {
opacity: 1;
transform: translateY(0) translateX(-50%);
}
.shareLink .permalink .copyLink:after {
content: attr(tooltip);
width: 140px;
bottom: -40px;
left: 50%;
padding: 5px;
border-radius: 4px;
font-size: 0.8rem;
opacity: 0;
pointer-events: none;
position: absolute;
background-color: #000000;
color: #ffffff;
transform: translateY(-10px) translateX(-50%);
transition: all 300ms ease;
text-align: center;
z-index: 1100;
}
.shareLink .permalink .copyLink i {
font-size: 18px;
color: #9500d8;
}
@media (min-width: 1100px) {
.referral-page-content .rui-img-rounded--lg {
    position: relative;
}
.referral-page-content .rui-img-rounded--lg img {
    transform: scale(1.6);
    position: absolute;
    top: 4px;
    right: 80px;
}
}
</style>

<?php
echo $OUTPUT->footer();
?>