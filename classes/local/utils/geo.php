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

namespace enrol_wallet\local\utils;

defined('MOODLE_INTERNAL') || die();

/**
 * Geo-location and country detection utility for multi-currency regional pricing.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class geo {
    /** Region constant for Egypt */
    public const REGION_EGYPT = 'EG';

    /** Region constant for Gulf / GCC countries */
    public const REGION_GULF = 'GULF';

    /** Region constant for Global / Rest of the World */
    public const REGION_GLOBAL = 'GLOBAL';

    /** Switcher visibility constants */
    public const SWITCHER_DISABLED = 0;
    public const SWITCHER_ADMINS_ONLY = 1;
    public const SWITCHER_EVERYONE = 2;

    /**
     * Check if the current user is an admin, manager, or has course enrol config capability.
     *
     * @param ?\context $context
     * @return bool
     */
    public static function is_manager(?\context $context = null): bool {
        global $USER;
        if (empty($USER->id)) {
            return false;
        }
        if (is_siteadmin($USER->id) || has_capability('moodle/site:config', \context_system::instance())) {
            return true;
        }
        if ($context && has_capability('moodle/course:enrolconfig', $context)) {
            return true;
        }
        return false;
    }

    /**
     * Check if the country switcher should be visible to the current user.
     *
     * @param ?\context $context
     * @return bool
     */
    public static function can_view_switcher(?\context $context = null): bool {
        $config = \enrol_wallet\local\config::make();
        if (isset($config->countryswitcher_access)) {
            $access = (int)$config->countryswitcher_access;
        } else if (isset($config->showcountryswitcher)) {
            $access = !empty($config->showcountryswitcher) ? self::SWITCHER_ADMINS_ONLY : self::SWITCHER_DISABLED;
        } else {
            $access = self::SWITCHER_ADMINS_ONLY;
        }

        if ($access === self::SWITCHER_DISABLED) {
            return false;
        }

        if ($access === self::SWITCHER_EVERYONE) {
            return true;
        }

        // SWITCHER_ADMINS_ONLY: strictly check management/admin capabilities.
        return self::is_manager($context);
    }

    /**
     * Detect the user's country code (2-letter ISO uppercase).
     *
     * @param ?\context $context
     * @return string 2-letter country code or 'GLOBAL'
     */
    public static function get_user_country(?\context $context = null): string {
        global $SESSION, $USER;

        $canoverride = self::can_view_switcher($context);

        // 1. Manual switch via URL parameter (admins/managers or if switcher is public).
        $requestedregion = optional_param('wallet_region', '', PARAM_ALPHAEXT);
        if (!empty($requestedregion) && $canoverride) {
            $requestedregion = strtoupper(trim($requestedregion));
            $SESSION->wallet_selected_country = $requestedregion;
            return $requestedregion;
        }

        // 2. Previously selected country in this session (only if allowed to switch).
        if (!empty($SESSION->wallet_selected_country) && $canoverride) {
            return $SESSION->wallet_selected_country;
        }

        // 3. Cloudflare header (instant 0ms edge detection).
        if (!empty($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            $cfcountry = strtoupper(trim($_SERVER['HTTP_CF_IPCOUNTRY']));
            if (strlen($cfcountry) === 2 && !in_array($cfcountry, ['XX', 'T1'], true)) {
                return $cfcountry;
            }
        }

        // 4. Standard server / proxy GeoIP headers.
        $proxyheaders = ['HTTP_X_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE', 'HTTP_GEOIP_COUNTRY_CODE', 'HTTP_X_GEOIP_COUNTRY'];
        foreach ($proxyheaders as $header) {
            if (!empty($_SERVER[$header])) {
                $code = strtoupper(trim($_SERVER[$header]));
                if (strlen($code) === 2) {
                    return $code;
                }
            }
        }

        // 5. Logged-in Moodle user profile country.
        if (!empty($USER->country) && strlen($USER->country) === 2) {
            return strtoupper(trim($USER->country));
        }

        // 6. Cached IP lookup in session.
        if (!empty($SESSION->wallet_geo_detected)) {
            return $SESSION->wallet_geo_detected;
        }

        // 7. Fast IP geolocation lookup (fallback for non-Cloudflare environments).
        $clientip = self::get_client_ip();
        if (!empty($clientip) && !in_array($clientip, ['127.0.0.1', '::1', 'unknown'], true) && !self::is_private_ip($clientip)) {
            $country = self::lookup_ip_country($clientip);
            if (!empty($country)) {
                $SESSION->wallet_geo_detected = $country;
                return $country;
            }
        }

        // 8. Timezone heuristic (essential for localhost / private IP development).
        $tzcountry = self::country_from_timezone($USER->timezone ?? null);
        if (!empty($tzcountry)) {
            return $tzcountry;
        }

        // 9. Browser Accept-Language header heuristic.
        $langcountry = self::country_from_accept_language();
        if (!empty($langcountry)) {
            return $langcountry;
        }

        // 10. Configured default fallback region (defaults to Egypt for local testing).
        $config = \enrol_wallet\local\config::make();
        if (!empty($config->defaultfallbackregion)) {
            return strtoupper(trim($config->defaultfallbackregion));
        }

        return self::REGION_EGYPT;
    }

    /**
     * Extract real client IP, inspecting common reverse proxy headers.
     *
     * @return string
     */
    public static function get_client_ip(): string {
        $headers = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $raw = trim($_SERVER[$header]);
                if (str_contains($raw, ',')) {
                    $parts = explode(',', $raw);
                    $raw = trim($parts[0]);
                }
                if (filter_var($raw, FILTER_VALIDATE_IP)) {
                    return $raw;
                }
            }
        }

        return (string)getremoteaddr();
    }

    /**
     * Map timezone to country code.
     *
     * @param ?string $tz
     * @return string
     */
    public static function country_from_timezone(?string $tz): string {
        if (empty($tz) || $tz === '99') {
            $tz = \core_date::get_server_timezone();
        }
        $tz = trim((string)$tz);
        $map = [
            'Africa/Cairo' => 'EG',
            'Egypt' => 'EG',
            'Asia/Riyadh' => 'SA',
            'Asia/Dubai' => 'AE',
            'Asia/Kuwait' => 'KW',
            'Asia/Qatar' => 'QA',
            'Asia/Bahrain' => 'BH',
            'Asia/Muscat' => 'OM',
        ];

        return $map[$tz] ?? '';
    }

    /**
     * Detect country code from HTTP Accept-Language header.
     *
     * @return string
     */
    public static function country_from_accept_language(): string {
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return '';
        }

        if (preg_match('/[a-z]{2}[-_]([A-Z]{2})/i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $matches)) {
            return strtoupper($matches[1]);
        }

        return '';
    }

    /**
     * Map a country code to a predefined regional zone.
     *
     * @param string $code Country or region code
     * @return string 'EG', 'GULF', or 'GLOBAL'
     */
    public static function get_region_for_country(string $code): string {
        $code = strtoupper(trim($code));

        if ($code === self::REGION_EGYPT) {
            return self::REGION_EGYPT;
        }

        // Saudi Arabia, UAE, Kuwait, Qatar, Bahrain, Oman.
        $gulfcountries = ['GULF', 'SA', 'AE', 'KW', 'QA', 'BH', 'OM'];
        if (in_array($code, $gulfcountries, true)) {
            return self::REGION_GULF;
        }

        return self::REGION_GLOBAL;
    }

    /**
     * Returns predefined regional options for dropdowns.
     *
     * @return array key => label
     */
    public static function get_region_options(): array {
        return [
            self::REGION_GLOBAL => get_string('region_global', 'enrol_wallet'),
            self::REGION_EGYPT  => get_string('region_egypt', 'enrol_wallet'),
            self::REGION_GULF   => get_string('region_gulf', 'enrol_wallet'),
        ];
    }

    /**
     * Returns a human-friendly flag and label for a region code.
     *
     * @param string $regioncode
     * @return string
     */
    public static function get_region_label(string $regioncode): string {
        $options = self::get_region_options();
        return $options[$regioncode] ?? $regioncode;
    }

    /**
     * Safely unpack region and accent color stored in customchar1.
     *
     * @param ?string $customchar1
     * @return array ['region' => string, 'color' => string]
     */
    public static function unpack_card_meta(?string $customchar1): array {
        if (empty($customchar1)) {
            return ['region' => self::REGION_GLOBAL, 'color' => ''];
        }

        $decoded = json_decode($customchar1, true);
        if (is_array($decoded)) {
            return [
                'region' => $decoded['region'] ?? self::REGION_GLOBAL,
                'color'  => $decoded['color'] ?? '',
            ];
        }

        // Legacy format: if it was a plain color hex string like "#0d6efd".
        return [
            'region' => self::REGION_GLOBAL,
            'color'  => $customchar1,
        ];
    }

    /**
     * Pack region and accent color into a compact JSON string for customchar1.
     *
     * @param string $region
     * @param string $color
     * @return string JSON string
     */
    public static function pack_card_meta(string $region, string $color): string {
        $data = [
            'region' => !empty($region) ? strtoupper(trim($region)) : self::REGION_GLOBAL,
            'color'  => trim($color),
        ];

        return (string)json_encode($data);
    }

    /**
     * Checks if an IP is private/local.
     *
     * @param string $ip
     * @return bool
     */
    private static function is_private_ip(string $ip): bool {
        return !filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Fast non-blocking lookup for IP country using cURL with short timeout.
     *
     * @param string $ip
     * @return string 2-letter country code or ''
     */
    private static function lookup_ip_country(string $ip): string {
        if (!function_exists('curl_init')) {
            return '';
        }

        $ch = curl_init("http://ip-api.com/json/{$ip}?fields=countryCode");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Moodle-enrol_wallet');
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpcode === 200 && !empty($response)) {
            $data = json_decode($response, true);
            if (!empty($data['countryCode']) && strlen($data['countryCode']) === 2) {
                return strtoupper(trim($data['countryCode']));
            }
        }

        return '';
    }
}
