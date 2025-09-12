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

namespace enrol_wallet\admin;

use admin_setting;

/**
 * Display a notice for end of support for Wordpress wallet connection.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_wp_notice extends admin_setting {
    /**
     * Constructor.
     *
     * @param string $name The name of the setting.
     * @param string $templatename The name of the template to render.
     * @param array|\stdClass $context The context to pass to the template.
     */
    public function __construct(
        /** @var bool Initially hide the settings. */
        protected bool $hidden
    ) {
        $this->nosave = true;

        parent::__construct('enrol_wallet/wp_notice', 'enrol_wallet/wp_notice', '', '');
    }

    #[\Override]
    public function get_setting(): bool {
        return true;
    }

    #[\Override]
    public function get_defaultsetting(): bool {
        return true;
    }

    #[\Override]
    public function write_setting($data): string {
        return '';
    }

    #[\Override]
    public function output_html($data, $query = ''): string {
        global $OUTPUT;

        return $OUTPUT->render_from_template('enrol_wallet/wp_notice', ['hidden' => $this->hidden]);
    }
}
