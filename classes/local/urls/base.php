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

namespace enrol_wallet\local\urls;

use core\url;

/**
 * Base method to retrieve urls.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait base {
    /**
     * Get the url of the page.
     * @param array $params
     * @return url
     */
    public function url($params = []): url {
        return new url($this->get_relative_path(), $params);
    }
    /**
     * Return the url of the page as string.
     * @param array $params
     * @param bool $escape
     * @return string
     */
    public function out($params = [], $escape = false): string {
        return $this->url($params)->out($escape);
    }
    /**
     * Get the url relative path to wwwroot.
     * @return string
     */
    public function get_relative_path(): string {
        return "/enrol/wallet/{$this->value}";
    }
    /**
     * Get the full directory of the page file.
     * @return string
     */
    public function get_dir(): string {
        global $CFG;
        $path = $this->get_relative_path();
        $dir = $CFG->dirroot . $path;
        return str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $dir);
    }

    /**
     * Set the page url to this url.
     * @param array $params
     * @return url
     */
    public function set_page_url_to_me($params = []): url {
        global $PAGE;
        $url = $this->url($params);
        $PAGE->set_url($url);
        return $url;
    }
}
