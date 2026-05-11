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
 * Functions to handle all coupons operations.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_wallet\local\coupons;

use core\exception\coding_exception;
use enrol_wallet\local\config;
use enrol_wallet\local\coupons\areas\empty_area;
use enrol_wallet\local\entities\instance;
use enrol_wallet\local\utils\timedate;
use enrol_wallet\local\wallet\balance;

/**
 * Class to handle coupons operations.
 */
class coupons {
    /**
     * Applying coupon on enrol area.
     * @deprecated Please use coupons\areas\enrol::AREA
     */
    public const AREA_ENROL = areas\enrol::AREA;

    /**
     * Applying coupon on cm (for availability_wallet).
     * @deprecated Please use coupons\areas\cm::AREA
     */
    public const AREA_CM = areas\cm::AREA;

    /**
     * Applying coupon on section (for availability_wallet).
     * @deprecated Please use coupons\areas\section::AREA
     */
    public const AREA_SECTION = areas\section::AREA;

    /**
     * Applying coupon in topping up form.
     * @deprecated Please use coupons\areas\topup::AREA
     */
    public const AREA_TOPUP = areas\topup::AREA;

    /**
     * The coupon code.
     * @var string
     */
    protected string $code;

    /**
     * The user id.
     * @var int
     */
    protected int $userid;

    /**
     * Is the coupon valid?
     * @var bool
     */
    protected bool $valid;

    /**
     * Error string.
     * @var string
     */
    protected string $error;

    /**
     * The coupons source.
     * @var int
     */
    private int $source;

    /**
     * Applying area.
     * @var areas\base
     */
    public areas\base $area;

    /**
     * Coupon type.
     * @var types\base
     */
    public types\base $coupon;

    /**
     * Constructor function for coupons operations, This will retrieve the coupon data and validate it.
     * @param string $code   the coupon code.
     * @param int    $userid The id of the user checking for the coupon, 0 means the current user.
     */
    public function __construct(string $code, int $userid = 0) {
        global $USER;
        $this->source = config::make()->walletsource;
        $this->code = $code;
        $this->userid = empty($userid) ? $USER->id : $userid;

        $this->set_coupon_data($code, $this->userid);
        // Default area.
        $this->area = new empty_area();
    }

    /**
     * Check the coupon's data and cashing it.
     * @param string $coupon the coupon code to check.
     * @param int    $userid
     */
    protected function set_coupon_data(string $coupon, int $userid): void {
        global $DB;

        if (!$this->check_enabled()) {
            $this->coupon = new types\null_type($coupon);

            return;
        }

        if ($this->source === balance::WP) {
            $notice = 'The connection with wordpress is not supported any more';
            $notice .= 'and may cause errors and future versions will be removed';
            debugging($notice, DEBUG_NONE);
            $wordpress = new \enrol_wallet\wordpress();
            $coupondata = $wordpress->get_coupon($coupon, $userid, 0, false);

            if (!\is_array($coupondata)) {
                // Error from wordpress.
                $this->valid = false;
                $this->error = $coupondata;
                $this->coupon = new types\null_type($coupon);

                return;
            }
            $this->coupon = types\base::make((object)$coupondata);
        } else {
            // If it is on moodle website.
            // Get the coupon data from the database.
            $couponrecord = $DB->get_record('enrol_wallet_coupons', ['code' => $coupon]);

            if (!$couponrecord) {
                $this->valid = false;
                $this->error = get_string('coupon_notexist', 'enrol_wallet') . "($coupon)";
                $this->coupon = new types\null_type($coupon);

                return;
            }

            $this->coupon = types\base::make($couponrecord);
        }
    }

    /**
     * Check if the current coupon type is enabled.
     * @return bool
     */
    protected function check_enabled(): bool {
        if (!$this->is_enabled()) {
            // This means that coupons is disabled in the site.
            $this->valid = false;
            $this->error = 'Coupons are disabled in this site.';

            return false;
        }

        // First check if this type is enabled in the website.
        if (!$this->is_enabled_type()) {
            $name = $this->coupon->get_visible_name();
            $this->error = get_string('coupon_disabled', 'enrol_wallet', $name);
            $this->valid = false;

            return false;
        }

        return true;
    }

    /**
     * Check if the current type is enabled or not.
     * @return bool
     */
    public function is_enabled_type(): bool {
        if (empty($this->coupon)) {
            // Not initialized yet no need to un-validate.
            return $this->is_enabled();
        }

        return $this->coupon->is_enabled();
    }

    /**
     * Check if coupons is enabled on this site or not.
     * @return bool
     */
    public static function is_enabled() {
        return !empty(types\base::get_enabled_types());
    }

    /**
     * Set the area of applying the coupon and its id.
     * @param int|string $area the area code.
     * @param int        $id   The instance, cm or section id.
     */
    protected function set_area(int|string $area, int $id = 0) {
        $this->area = areas\base::make($area, $id);
    }

    /**
     * Return the type as string as to be stored in database.
     * @param  bool       $string
     * @return string|int
     */
    public function get_type(bool $string = true): string|int {
        if ($string) {
            return $this->coupon->type;
        }

        return $this->coupon::TYPE;
    }

    /**
     * Validate the coupon's record (time usage, number of usage ...).
     * @return bool
     */
    protected function validate_record(): bool {
        if (!empty($this->error)) {
            $this->valid = false;

            return false;
        }

        $valid = $this->coupon->is_valid_record($this->userid, $error);

        if (!$valid) {
            $this->error = $error;
            $this->valid = false;
        }

        return $valid;
    }

    /**
     * Check if the area is valid for applying this coupon.
     * MUST CALL ::set_area before using this method.
     * @return bool
     */
    protected function validate_area(): bool {
        if (!$this->area->record_exists()) {
            $area = $this->area::get_visible_name();
            $this->error = get_string('couponareanotexist', 'enrol_wallet', ['area' => $area, 'id' => $this->area->areaid]);
            $this->valid = false;

            return false;
        }

        $notvalid = empty($this->coupon) || !empty($this->error);
        $notvalid = $notvalid || !$this->area->is_valid_for_type($this->coupon);

        if ($notvalid) {
            $this->error = get_string('coupon_applynothere', 'enrol_wallet');

            return false;
        }

        return true;
    }

    /**
     * Check if the area to be validated is the same stored here.
     * @param  ?int             $area
     * @param  int              $areaid
     * @throws coding_exception
     * @return bool
     */
    protected function is_same_area_input(?int $area, int $areaid): bool {
        global $DB;

        if ($area === null) {
            return true;
        }

        if (!\in_array($area, areas\base::get_areas())) {
            throw new coding_exception("Non recognized coupon apply area $area");
        }

        if ($area === $this->area::AREA && ($area === areas\topup::AREA || $areaid === $this->area->areaid)) {
            return true;
        }

        return false;
    }

    /**
     * Check if the coupon is valid to be used in this area.
     * returns string on error and true if valid.
     * @param  ?int        $area   code, value, type, courses, category
     * @param  int         $areaid the area at which the coupon applied (instanceid, cmid, sectionid)
     * @return true|string
     */
    public function validate_coupon(?int $area = null, int $areaid = 0): bool|string {
        if ($this->is_same_area_input($area, $areaid) && !empty($this->error)) {
            return $this->error;
        }
        unset($this->error);

        if (isguestuser($this->userid) || empty($this->userid)) {
            $this->valid = false;
            $this->error = get_string('guestnousecoupons', 'enrol_wallet');

            return $this->error;
        }

        $this->valid = true;

        if ($area !== null) {
            $this->set_area($area, $areaid);
        }

        if ($this->coupon instanceof null_type) {
            return !empty($this->error) ? $this->error : get_string('coupon_notexist', 'enrol_wallet');
        }

        if (!$this->check_enabled() || !$this->validate_record()) {
            return $this->error;
        }

        $this->validate_area();

        if (!empty($this->error)) {
            return $this->error;
        }

        $this->valid = $this->coupon->validate_coupon($this->area, $this->userid, $error);

        if ($error) {
            $this->error = $error;
        }

        if (!empty($this->error)) {
            return $this->error;
        }

        return $this->valid;
    }

    /**
     * Return the value of this coupon.
     * @return float
     */
    public function get_value(): float {
        return $this->coupon->value;
    }

    /**
     * Get the code of the coupon.
     * @return string
     */
    public function get_code(): string {
        return $this->code;
    }

    /**
     * Return the error due to validation process.
     * false if there is none and the coupon is valid.
     * @return bool
     */
    public function has_error(): bool {
        $this->check_validation();

        return !empty($this->error) || !$this->valid;
    }

    /**
     * Return the last error when applying the coupon if
     * any existed.
     * @return string|null
     */
    public function get_error(): ?string {
        $this->check_validation();

        return $this->error ?? null;
    }

    /**
     * Is the coupon valid or not?
     * @return bool
     */
    public function is_valid(): bool {
        $this->check_validation();

        return $this->valid;
    }

    /**
     * Check if the coupon has been validated or not.
     * @throws coding_exception
     * @return void
     */
    protected function check_validation(): void {
        if (!isset($this->valid)) {
            throw new coding_exception('Cannot check error or valid status before call ::validate_coupoun()');
        }
    }

    /**
     * Apply the coupon for enrolment or topping up the wallet.
     *
     * @param  int  $area
     * @param  int  $areaid
     * @return void
     */
    public function apply_coupon(int $area = areas\topup::AREA, int $areaid = 0): void {
        if (!isset($this->valid)) {
            $this->validate_coupon($area, $areaid);
        }

        if (!$this->valid) {
            \core\notification::error($this->error ?? get_string('error', 'error'));

            return;
        }

        $used = $this->coupon->apply_coupon($this->area, $this->userid);

        if ($used) {
            // Mark the coupon as used.
            $this->mark_coupon_used();
        }
    }

    /**
     * Called when the coupon get used and mark it as used.
     * MUSN'T be called before validation.
     * @return void
     */
    public function mark_coupon_used() {
        global $DB;

        if (PHPUNIT_TEST && !isset($this->valid)) {
            $this->validate_coupon();
        }

        if (!isset($this->valid)) {
            throw new \coding_exception('cannot be called before validation');
        }

        if (!$this->valid) {
            if (PHPUNIT_TEST) {
                debugging('Cannot mark an invalid coupon as used.');
            }

            return;
        }

        // Unset the session coupon to make sure not used again.
        self::unset_session_coupon();

        $instanceid = $this->area->get_instance_id();

        if ($this->source === balance::WP) {
            if ($this->coupon::get_type() === 'discount') {
                // It is already included in the wordpress plugin code.
                $wordpress = new \enrol_wallet\wordpress();
                $wordpress->get_coupon($this->code, $this->userid, $instanceid, true);
            }
        } else {
            $olduse = $DB->count_records('enrol_wallet_coupons_usage', ['code' => $this->get_code()]);
            $usage = max($this->coupon->usetimes, $olduse) + 1;
            $this->coupon->lastuse = timedate::time();
            $this->coupon->usetimes = $usage;
            $DB->update_record('enrol_wallet_coupons', $this->coupon->get_record());
        }

        // Logging the usage in the coupon usage table.
        $logdata = [
            'code'       => $this->get_code(),
            'type'       => $this->coupon->get_type(),
            'value'      => $this->get_value(),
            'userid'     => $this->userid,
            'area'       => $this->area::AREA,
            'areaid'     => $this->area->areaid,
            'instanceid' => $instanceid,
            'timeused'   => timedate::time(),
        ];
        $id = $DB->insert_record('enrol_wallet_coupons_usage', (object)$logdata);

        unset($logdata['userid'], $logdata['timeused']);
        $eventdata = [
            'userid'        => $this->userid,
            'relateduserid' => $this->userid,
            'objectid'      => !empty($id) ? $id : null,
            'other'         => $logdata,
        ];

        $eventdata['context'] = $this->area->get_context();

        $event = \enrol_wallet\event\coupon_used::create($eventdata);
        $event->trigger();
    }

    /**
     * Check if there is coupon code in session or as a parameter.
     * @return string|null return the coupon code, or null if not found.
     */
    public static function check_discount_coupon(): ?string {
        global $SESSION;
        $coupon = !empty($SESSION->enrol_wallet_coupon)
                  ? clean_param($SESSION->enrol_wallet_coupon, PARAM_ALPHANUM)
                  : null;

        return $coupon;
    }

    /**
     * Set coupon in the session.
     * @param string $code the coupon code.
     */
    public static function set_session_coupon(string $code) {
        global $SESSION;
        $SESSION->enrol_wallet_coupon = $code;
        instance::reset_static_cache();
    }

    /**
     * Unset any session coupons.
     */
    public static function unset_session_coupon(): void {
        global $SESSION;
        $SESSION->enrol_wallet_coupon = null;
        instance::reset_static_cache();
    }
}
