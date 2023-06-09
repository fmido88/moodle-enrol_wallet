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

/** Form to apply coupons in the enrolment page.
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\form;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir.'/formslib.php');

/** Form to apply coupons.
 *
 */
class applycoupon_form extends \moodleform {
    /**
     * Overriding this function to get unique form id for multiple wallet enrollments,
     * or multiple wallet activity restriction.
     *
     * @return string form identifier
     */
    protected function get_form_identifier() {
        $data = (object)$this->_customdata;
        if (!empty($data->id)) {
            $formid = $data->id.'_'.get_class($this);

        } else if (!empty($data->cmid)) {
            $formid = $data->cmid.'_'.get_class($this);

        } else if (!empty($data->sectionid)) {
            $formid = $data->sectionid.'_'.get_class($this);

        } else {
            $formid = parent::get_form_identifier();
        }

        return $formid;
    }

    /**
     * Form definition. Abstract method - always override!
     * @return void
     */
    public function definition() {
        $mform = $this->_form;
        $instance = $this->_customdata->instance;
        $url = new \moodle_url('course/view.php', ['id' => $instance->courseid]);

        $wallet = enrol_get_plugin('wallet');
        $coupon = $wallet->check_discount_coupon();
        $coupongroup = [];

        if (!empty($coupon)) {
            $html = '<span>coupon code ( '.$coupon.' ) applied.';
            $coupongroup[] = $mform->createElement('html', $html);
            $coupongroup[] = $mform->createElement('cancel');
        } else {
            $coupongroup[] = $mform->createElement('text', 'coupon', get_string('applycoupon', 'enrol_wallet'), '"maxlength"="50"');
            $mform->setType('coupon', PARAM_TEXT);
            $coupongroup[] = $mform->createElement('submit', 'submitcoupon', get_string('applycoupon', 'enrol_wallet'));
        }

        if (empty($instance->cmid) && empty($instance->sectionid)) {
            $mform->addGroup($coupongroup, 'applycoupon', get_string('applycoupon', 'enrol_wallet'), null, false);
            $mform->addHelpButton('applycoupon', 'applycoupon', 'enrol_wallet');
        } else {
            $mform->addGroup($coupongroup, 'coupons', null, null, false);
        }

        if (!empty($instance->id)) {
            $mform->addElement('hidden', 'instanceid');
            $mform->setType('instanceid', PARAM_INT);
            $mform->setDefault('instanceid', $instance->id);
        }

        if (!empty($instance->cmid)) {
            $mform->addElement('hidden', 'cmid');
            $mform->setType('cmid', PARAM_INT);
            $mform->setDefault('cmid', $instance->cmid);
        }

        if (!empty($instance->sectionid)) {
            $mform->addElement('hidden', 'sectionid');
            $mform->setType('sectionid', PARAM_INT);
            $mform->setDefault('sectionid', $instance->sectionid);
        }

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);
        $mform->setDefault('courseid', $instance->courseid);

        $mform->addElement('hidden', 'url');
        $mform->setType('url', PARAM_URL);
        $mform->setDefault('url', $url);

        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_TEXT);
        $mform->setDefault('sesskey', sesskey());
    }
}
