<?php
define('NO_AUTH_REQUIRED', true);
define('NO_MOODLE_COOKIES', true);

require_once('../../config.php');
require_once($CFG->libdir.'/authlib.php');
require_once($CFG->dirroot.'/login/lib.php');
require_once($CFG->dirroot.'/enrol/wallet/classes/form/referral_signup_form.php');

// Prevent access if user is already logged in
if (isloggedin() && !isguestuser()) {
    redirect($CFG->wwwroot . '/index.php', get_string('alreadyloggedin', 'error', fullname($USER)));
}

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_title($SITE->fullname . ': ' . get_string('referral_signup_heading', 'enrol_wallet'));
$PAGE->set_heading($SITE->fullname);

$refcode = required_param('refcode', PARAM_ALPHANUM);

$referrer = $DB->get_record('user', ['username' => $refcode]);

$referrername = fullname($referrer);

// Check if custom referral signup is enabled
if (!get_config('enrol_wallet', 'enable_custom_referral_signup')) {
    // If not enabled, redirect to standard signup form
    redirect(new moodle_url('/login/signup.php', ['refcode' => $refcode]));
}

$PAGE->set_url('/enrol/wallet/referral_signup.php');
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('referral_signup_heading', 'enrol_wallet'));

$mform = new \enrol_wallet\form\referral_signup_form(null, ['refcode' => $refcode]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/'));
} else if ($user = $mform->get_data()) {
    // Registration process
    $user = signup_user($user);

    // Add refcode to user_info_data
    profile_save_data((object)['id' => $user->id, 'profile_field_refcode' => $refcode]);

    complete_user_login($user);
    redirect(new moodle_url('/'));
}

$templatecontext = [
    'referrer_name' => $referrername ?: get_string('unknown', 'enrol_wallet'),
    'form' => $mform->render(),
    'wwwroot' => $CFG->wwwroot,
    'sitename' => $SITE->fullname,
];

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('enrol_wallet/referral_signup_page', $templatecontext);
echo $OUTPUT->footer();