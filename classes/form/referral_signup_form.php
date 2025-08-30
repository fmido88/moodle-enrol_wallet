<?php
namespace enrol_wallet\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/user/editlib.php');

class referral_signup_form extends \moodleform {
        public function definition() {
        global $CFG, $USER;

        $mform = $this->_form;

        $mform->addElement('text', 'username', get_string('username'), 'maxlength="100" size="12" autocapitalize="none" class="form-control"');
        $mform->setType('username', PARAM_RAW);
        $mform->addRule('username', get_string('missingusername'), 'required', null, 'client');

        $mform->addElement('text', 'email', get_string('email'), 'maxlength="100" size="25" class="form-control"');
        $mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', get_string('missingemail'), 'required', null, 'client');

        $mform->addElement('text', 'email2', get_string('emailagain'), 'maxlength="100" size="25" class="form-control"');
        $mform->setType('email2', PARAM_RAW_TRIMMED);
        $mform->addRule('email2', get_string('missingemail'), 'required', null, 'client');

        $namefields = useredit_get_required_name_fields();
        foreach ($namefields as $field) {
            $mform->addElement('text', $field, get_string($field), 'maxlength="100" size="30" class="form-control"');
            $mform->setType($field, PARAM_TEXT);
            $stringid = 'missing' . $field;
            if (!get_string_manager()->string_exists($stringid, 'moodle')) {
                $stringid = 'required';
            }
            $mform->addRule($field, get_string($stringid), 'required', null, 'client');
        }

        $mform->addElement('passwordunmask', 'password', get_string('password'), 'maxlength="32" size="12" class="form-control"');
        $mform->setType('password', PARAM_RAW);
        $mform->addRule('password', get_string('missingpassword'), 'required', null, 'client');

        $mform->addElement('hidden', 'refcode', $this->_customdata['refcode']);
        $mform->setType('refcode', PARAM_ALPHANUM);

        $this->add_action_buttons(true, get_string('createaccount'));
    }

    public function validation($data, $files) {
        global $CFG, $DB;
        $errors = parent::validation($data, $files);

        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        } else if ($DB->record_exists('user', array('email' => $data['email']))) {
            $errors['email'] = get_string('emailexists');
        }
        if (empty($data['email2'])) {
            $errors['email2'] = get_string('missingemail');
        } else if ($data['email2'] != $data['email']) {
            $errors['email2'] = get_string('invalidemail');
        }
        if (!empty($data['username'])) {
            if ($DB->record_exists('user', array('username' => $data['username'], 'mnethostid' => $CFG->mnet_localhost_id))) {
                $errors['username'] = get_string('usernameexists');
            }
        }

        return $errors;
    }
}