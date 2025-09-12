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

namespace enrol_wallet\local\coupons;

use enrol_wallet\local\utils\timedate;
use progress_trace;
use null_progress_trace;

/**
 * Class generator
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generator {
    /**
     * Uppercase characters.
     * @var string
     */
    public const UPPERCASE_CHARSET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    /**
     * Lower case characters.
     * @var string
     */
    public const LOWERCASE_CHARSET = 'abcdefghijklmnopqrstuvwxyz';
    /**
     * Digits characters.
     * @var string
     */
    public const NUMBERS_CHARSET = '0123456789';
    /**
     * Creating a random coupon according to the options and length provided.
     * @param int $length length of the coupon
     * @param array $options characters options
     * @return string the random coupon generated.
     */
    public static function generate_random_coupon($length, $options) {
        $randomcoupon = '';
        $upper = $options['upper'];
        $lower = $options['lower'];
        $digits = $options['digits'];
        $charset = '';
        if ($upper) {
            $charset .= self::UPPERCASE_CHARSET;
        }
        if ($lower) {
            $charset .= self::LOWERCASE_CHARSET;
        }
        if ($digits) {
            $charset .= self::NUMBERS_CHARSET;
        }

        $count = strlen($charset);

        while ($length--) {
            $randomcoupon .= $charset[mt_rand(0, $count - 1)];
        }

        return $randomcoupon;
    }

    /**
     * Generating coupons.
     *
     * @param object $options the options from coupon form.
     * @param ?progress_trace $trace
     * @return array|string array of coupon, or string of error.
     */
    public static function create_coupons($options, ?progress_trace $trace = null) {
        global $DB;

        if (empty($trace)) {
            $trace = new null_progress_trace;
        }

        $number     = $options->number;
        $maxusage   = $options->maxusage;
        $maxperuser = $options->maxperuser;
        $from       = $options->from;
        $to         = $options->to;
        $type       = $options->type;
        $value      = $options->value ?? (($type == 'enrol') ? 0 : null);
        $code       = $options->code;

        $recorddata = (object)[
            'type'        => $type,
            'value'       => $value,
            'category'    => $options->category ?? '',
            'courses'     => $options->courses ?? '',
            'maxusage'    => $maxusage,
            'maxperuser'  => $maxperuser,
            'validfrom'   => $from,
            'validto'     => $to,
            'timecreated' => $options->timecreated ?? timedate::time(),
        ];

        $ids = [];
        if (!empty($code)) {
            $recorddata->code = $code;

            $ids[] = $DB->insert_record('enrol_wallet_coupons', $recorddata);
            $trace->output('Single coupon created...');
        } else {

            $length = $options->length;
            $lower  = $options->lower;
            $upper  = $options->upper;
            $digits = $options->digits;

            $lastprogress = 0;
            while (count($ids) < $number) {
                $progress = round(count($ids) / $number * 100);
                if ($progress > $lastprogress + 5) {
                    $trace->output('Generating coupons... ' . $progress . '%');
                    $lastprogress = $progress;
                }

                $gopt = [
                    'lower' => $lower,
                    'upper' => $upper,
                    'digits' => $digits,
                ];
                $recorddata->code = self::generate_random_coupon($length, $gopt);
                if (!$recorddata->code) {
                    return get_string('coupon_generator_error', 'enrol_wallet');
                }
                if ($DB->record_exists('enrol_wallet_coupons', ['code' => $recorddata->code])) {
                    continue;
                }
                $ids[] = $DB->insert_record('enrol_wallet_coupons', $recorddata);
            }
        }
        return $ids;
    }
}
