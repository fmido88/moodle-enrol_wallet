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
use enrol_wallet\local\config;
use enrol_wallet\local\wallet\balance;
use curl;
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
        global $CFG;
        require_once($CFG->libdir .'/filelib.php');

        $wordpressurl = config::make()->wordpress_url;
        $wordpressurl = clean_param($wordpressurl, PARAM_URL);
        if (empty($wordpressurl)) {
            return;
        }

        $url = $wordpressurl . self::ENDPOINT . $method;
        $encrypted = $this->encrypt_data($data);
        $sendingdata = ['encdata' => $encrypted];

        $curl = new curl();

        $curlsetopt = [
            'url'            => $url,
            'returntransfer' => true,
            'failonerror'    => false,
            'post'           => true,
            'postfields'     => $sendingdata,
        ];
        $curl->setopt($curlsetopt);

        $response = $curl->post($url, $sendingdata);
        $info = $curl->get_info();
        $errorno = $curl->get_errno();
        $curl->cleanopt();

        if (empty($info['http_code']) || $info['http_code'] != 200 || !empty($errorno)) {
            // Endpoint returned an error.
            if (is_string($response)) {
                debugging($response);
            }
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
        $key = config::make()->wordpress_secretkey;
        $data['sk'] = $key;
        $token = http_build_query( $data, 'flags_' );

        $encryptkey = openssl_digest( $key, 'SHA256', true );

        $encryptiv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('AES-128-CTR'));
        $crypttext = openssl_encrypt($token, 'AES-128-CTR', $encryptkey, 0, $encryptiv) . "::" . bin2hex($encryptiv);

        $encdata = base64_encode($crypttext);
        $encdata = str_replace(['+', '/', '='], ['-', '_', ''], $encdata);

        $encrypteddata = trim($encdata);
        return $encrypteddata;
    }
    /**
     * Deduct amount from user's wallet.
     * @param int $userid
     * @param float $amount
     * @param string $coursename COURSE name at which the request occurred.
     * @param int $charger id of user did the operation.
     * @return mixed
     */
    public function debit($userid, float $amount, $coursename = '', $charger = '') {
        $data = [
            'moodle_user_id' => $userid,
            'amount'         => $amount,
            'course'         => $coursename,
            'charger'        => $charger,
        ];

        return $this->request('debit', $data);
    }

    /**
     * Add credit to user's balance in Tera wallet.
     * return array contain error msg and success status.
     *
     * @param float $amount
     * @param int $userid
     * @param string $description
     * @param int $charger
     * @return array|string
     */
    public function credit($amount, $userid, $description = '', $charger = '') {
        $data = [
            'amount'         => $amount,
            'moodle_user_id' => $userid,
            'description'    => $description,
            'charger'        => $charger,
        ];

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
    public function get_coupon($coupon, $userid, $instanceid, $apply = false) {
        $method = 'get_coupon_value';
        $data = [
            'coupon'         => $coupon,
            'moodle_user_id' => $userid,
            'instanceid'     => $instanceid,
            'apply'          => $apply,
        ];

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

        if (stripos($coupontype, 'fixed') !== false) {
            $coupontype = 'fixed';
        } else if (stripos($coupontype, 'percent') !== false) {
            $coupontype = 'percent';
        } else {
            return get_string('coupon_invalidreturntype', 'enrol_wallet');
        }

        $coupondata = [
            'value' => $couponvalue,
            'type'  => $coupontype,
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
     * Creating or updating wordpress user associative with the moodle user.
     * return wordpress user's id.
     * @param int|object $user user id or user object.
     * @param string $password raw password before hashing.
     * @return int|bool wordpress user id or false on fail.
     */
    public function create_wordpress_user($user, $password = null) {

        if (is_number($user)) {
            $user = \core_user::get_user($user);
        }

        if (empty($user) || !is_object($user)) {
            return false;
        }

        // If the request is from post signup request, create the user here to get user id.
        if (!get_complete_user_data('username', $user->username) || empty($user->id)) {
            $auth = get_auth_plugin($user->auth);
            $auth->user_signup($user, false);

            if (!$user = get_complete_user_data('username', $user->username)) {
                return false;
            }
        }

        $data = [
            'username'       => $user->username,
            'password'       => (empty($password)) ? generate_password() : $password,
            'email'          => $user->email,
            'moodle_user_id' => empty($user->id) ? '' : $user->id,
        ];

        return $this->request('create_user', $data);
    }

    /**
     * Login and logout the user to the wordpress site.
     * @param int $userid moodle user id
     * @param string $method either login or logout
     * @param string $redirect redirection url after login or logout from wordpress website
     */
    public function login_logout_user_to_wordpress($userid, $method, $redirect) {
        $config = config::make();
        $walletsource = $config->walletsource;
        $allowed = $config->wordpressloggins;
        $wordpressurl = $config->wordpress_url;
        $wordpressurl = clean_param($wordpressurl, PARAM_URL);

        $user = \core_user::get_user($userid);

        if (
            $walletsource != balance::WP
            || empty($allowed) // Check if this option allowed in the settings.
            || empty($wordpressurl) // If the wp url is not set.
            || !$user // If this is a valid user.
            || isguestuser($user) // Not guest.
            || ($method == 'login' && !isloggedin())
            ) {
            return;
        }

        if ($method == 'logout' && isloggedin()) {
            redirect($redirect);
        }

        if ($method == 'login') {
            $done = get_user_preferences('enrol_wallet_wploggedin', false, $user);
            if ($done) {
                return;
            }

            set_user_preference('enrol_wallet_wploggedin', true, $user);
        } else {
            unset_user_preference('enrol_wallet_wploggedin', $user);
        }

        // The data to send to wordpress.
        $data = [
            'moodle_user_id' => $userid,
            'method'         => $method,
            'url'            => $redirect,
            'email'          => $user->email,
            'username'       => $user->username, // ...username and email used for signup only in case user need to be created.
        ];
        $encdata = $this->encrypt_data($data);
        $moodleurl = urlencode((new \moodle_url('/'))->out(false));
        redirect($wordpressurl . '?encdata=' . $encdata . '&moodleurl=' . $moodleurl);
    }
}
