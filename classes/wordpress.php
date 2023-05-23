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
 * Connection to wordpress.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace enrol_wallet;
use enrol_wallet_plugin;

/**
 * The class containing  the function which handls wordpress requests.
 *
 */
class wordpress {

    private const ENDPOINT = '/wp-json/moo-wallet/v1/';
    /**
     * Make an HTTP POST request to the moo-wallet plugin endpoint.
     * Of the external WordPress site.
     * @param string $method
     * @param array $data
     * @return mixed
     */
    public function request($method, $data) {

        $wordpressurl = get_config('enrol_wallet', 'wordpress_url');

        $url = $wordpressurl . self::ENDPOINT . $method;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));
        $response = curl_exec($curl);
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpcode != 200) {
            // Endpoint returned an error.
            return get_string('endpoint_error', 'enrol_wallet');
        }

        return json_decode($response, true);
    }

    /**
     * Deduct amount from user's wallet.
     * @param int $userid
     * @param float $amount
     * @param string $coursename
     * @param int $charger \\ id of user did the operation.
     * @return mixed
     */
    public function debit($userid, float $amount, $coursename = '', $charger = '') {
        $data = array(
            'moodle_user_id' => $userid,
            'amount' => $amount,
            'course' => $coursename, // COURSE name at which the request occured.
            'charger' => $charger
        );

        return $this->request('debit', $data);
    }

    /**
     * Add credit to user's balance in Tera wallet.
     * return array contain error msg and success status.
     *
     * @param float $amount
     * @param int $userid
     * @param string $description
     * @param string $charger
     * @return array|string
     */
    public function credit($amount, $userid, $description = '', $charger = '') {
        $data = array(
            'amount' => $amount,
            'moodle_user_id' => $userid,
            'description' => $description,
            'charger' => $charger
        );

        $responsedata = $this->request('wallet_topup', $data);
        if (!isset($responsedata['success'])) {
            if (!isset($responsedata['err'])) {
                // Response format is incorrect.
                return get_string('endpoint_incorrect', 'enrol_wallet');
            } else {
                // Print the error from wordpress site.
                return $responsedata['err'];
            }
        }
        if ($responsedata['success'] == 'false') {
            // Response format is incorrect.
            return $responsedata['err'];
        }

        return $responsedata;
    }

    /**
     * Get the coupon value and type from woocommerce.
     * return array of the value and type or string in case of error
     * @param string $coupon
     * @param int $userid
     * @param int $instanceid
     * @param bool $apply
     * @return array|string
     */
    public function get_coupon($coupon, $userid, $instanceid, $apply) {
        $method = 'get_coupon_value';
        $data = array(
            'coupon' => $coupon,
            'moodle_user_id' => $userid,
            'instanceid' => $instanceid,
            'apply' => $apply,
        );

        $responsedata = $this->request($method, $data);

        if (!isset($responsedata['coupon_value'])) {
            if (!isset($responsedata['err'])) {
                // Response format is incorrect.
                return get_string('endpoint_incorrect', 'enrol_wallet');
            } else {
                // Print the error from wordpress site.
                return $responsedata['err'];
            }
        }

        $couponvalue = $responsedata['coupon_value'];
        $coupontype = $responsedata['coupon_type'];

        if (!is_numeric($couponvalue)) {
            // Response format is incorrect.
            return $responsedata['err'];
        }

        if ($couponvalue == 0) {
            return get_string('coupon_novalue', 'enrol_wallet');
        }

        if ($coupontype == 'fixed_cart') {
            $coupontype = 'fixed';
        } else if (strpos($coupontype, 'percent')) {
            $coupontype = 'percent';
        }

        $coupondata = [
            'value' => $couponvalue,
            'type' => $coupontype,
        ];
        return $coupondata;
    }

    /**
     * Get the user's balance in Tera wallet.
     * return the user's balance or string in case of error.
     * @param int $userid
     * @return float|string
     */
    public function get_user_balance($userid) {
        $data = ['moodle_user_id' => $userid];
        $method = 'balance';

        $response = $this->request($method, $data);

        if ($response == 'no associated wordpress user') {
            // Create new wordpress user.
            $this->create_wordpress_user($userid);
            // Redo the request.
            $response = $this->request($method, $data);
        }

        return $response;
    }

    /**
     * Creating wordpress user accociative with the moodle user.
     * return wordpress user's id.
     * @param int $userid
     * @return int
     */
    private function create_wordpress_user($userid) {
        $user = \core_user::get_user($userid);
        $data = [
            'username' => $user->username,
            'password' => random_string(12),
            'email' => $user->email,
            'moodle_user_id' => $userid,
        ];

        return $this->request('create_user', $data);
    }
}

