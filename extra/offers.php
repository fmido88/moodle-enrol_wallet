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
 * TODO describe file offers
 *
 * @package    enrol_wallet
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../config.php');

require_login();

$PAGE->set_context(context_course::instance(SITEID));
$PAGE->set_url(new moodle_url('/enrol/wallet/extra/offers.php'));
$PAGE->set_heading('Offers');
$PAGE->set_title('Offers');
$PAGE->set_pagelayout('frontpage');

global $DB;
$renderer = $PAGE->get_renderer('core', 'course');

$courses = enrol_wallet\util\offers::get_courses_with_offers();

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

$out = $OUTPUT->header();

$rules = enrol_wallet\util\discount_rules::get_the_discount_line(-1);
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

$out .= $OUTPUT->footer();

echo $out;
