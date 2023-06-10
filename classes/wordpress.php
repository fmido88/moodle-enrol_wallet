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
 * The class containing  the function which handles wordpress requests.
 *
 */
class wordpress {
    /**
     * Moo-Wallet plugin endpoint.
     */
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
        if (empty($wordpressurl)) {
            return;
        }
        $url = $wordpressurl . self::ENDPOINT . $method;
        $encrypted = $this->encrypt_data($data);
        $sendingdata = [
            'encdata' => $encrypted,
        ];
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($sendingdata),
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
     * encrypt data before sent it.
     * @param array $data data to encrypt (in form of an array)
     * @return string
     */
    public static function encrypt_data($data) {
        $key = get_config('enrol_wallet', 'wordpress_secretkey');
        $data['sk'] = $key;
        $query = http_build_query( $data, 'flags_' );
        $token = $query;

        $encryptmethod = 'AES-128-CTR';

        $encryptkey = openssl_digest( $key, 'SHA256', true );

        $encryptiv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($encryptmethod));
        $crypttext = openssl_encrypt($token, $encryptmethod, $encryptkey, 0, $encryptiv) . "::" . bin2hex($encryptiv);

        $encdata = base64_encode($crypttext);
        $encdata = str_replace(array('+', '/', '='), array('-', '_', ''), $encdata);

        $encrypteddata = trim($encdata);
        return $encrypteddata;
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
            'course' => $coursename, // COURSE name at which the request occurred.
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

        if ($coupontype == 'fixed_cart' || $coupontype == 'fixed_product') {
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
     * Creating wordpress user associative with the moodle user.
     * return wordpress user's id.
     * @param int $userid
     * @return int|bool
     */
    private function create_wordpress_user($userid) {
        $user = \core_user::get_user($userid);
        if (!$user) {
            return false;
        }
        $data = [
            'username' => $user->username,
            'password' => random_string(12),
            'email' => $user->email,
            'moodle_user_id' => $userid,
        ];

        return $this->request('create_user', $data);
    }

    /**
     * Login and logout the user to the wordpress site.
     * @param int $userid
     * @param string $method either login or logout
     * @return bool|string
     */
    public function login_logout_user_to_wordpress($userid, $method) {
        $allowed = get_config('enrol_wallet', 'wordpressloggins');
        if (!$allowed) {
            return false;
        }
        $data = ['moodle_user_id' => $userid, 'method' => $method];
        $response = $this->request('login_user', $data);
        if ($response) {
            return true;
        } else {
            $this->create_wordpress_user($userid);
            return $this->request('login_user', $data);
        }
    }
}

