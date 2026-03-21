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

namespace enrol_wallet\hook;

use core\output\renderer_base;
use core_text;
use stdClass;

/**
 * Class extend_topup_methods.
 *
 * @package    enrol_wallet
 * @copyright  2026 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('wallet', 'enrol')]
#[\core\attribute\label('Adding extra options for topping up the wallet.')]
class extend_topup_options {
    /**
     * Available topup options.
     * @var array<array{content: string, label: string, key: string}>
     */
    protected array $options = [];

    /**
     * Extend the topup options.
     * @param stdClass      $user
     * @param renderer_base $renderer
     */
    public function __construct(
        /** @var stdClass The user object uses the topping up. */
        protected stdClass $user,
        /** @var renderer_base The output renderer. */
        protected renderer_base $renderer,
    ) {
    }

    /**
     * Add a topup option.
     * @param  string  $content
     * @param  string  $label
     * @param  ?string $key
     * @return string  return the key of this option.
     */
    public function add_option(string $content, string $label, ?string $key = null): string {
        $option = [
            'content' => $content,
            'label'   => $label,
            'key'     => !empty($key) ? core_text::strtolower($key) : self::get_random_key(),
        ];

        if (\in_array($option['key'], $this->get_keys())) {
            debugging('Duplicate option key detected: ' . $option['key'] . '. Each option key must be unique.', DEBUG_DEVELOPER);
            $option['key'] = self::get_random_key();
        }
        $this->options[] = $option;

        return $option['key'];
    }

    /**
     * Change the sort order of a single option.
     * @param  string $key
     * @param  int    $order 0 means first.
     * @return void
     */
    public function order_option(string $key, int $order) {
        $options = fullclone($this->options);
        $target  = null;

        foreach ($options as $k => $option) {
            if ($key === $option['key']) {
                $target = $options[$k];
                unset($option[$k]);
                break;
            }
        }

        if (!$target) {
            debugging("The key $key is not exist for topup options.");

            return;
        }

        $this->options = [];

        if ($order >= \count($options)) {
            $options[]     = $target;
            $this->options = $option;

            return;
        }

        $i = 0;

        foreach ($options as $option) {
            if ($order === $i) {
                $this->options[] = $target;
                $i++;
            }
            $this->options[] = $option;
            $i++;
        }
    }

    /**
     * Add an option.
     * @param  ?array $options array of options each is array
     *                         with keys 'content', 'label' and optional 'key'
     * @return void
     */
    public function add_options(?array $options) {
        if (empty($options)) {
            return;
        }

        if (!array_is_nested($options) && \array_key_exists('content', $options)) {
            $options = [$options];
        }

        foreach ($options as $option) {
            if (empty($option)) {
                continue;
            }

            if (isset($option['content'], $option['label'])) {
                $this->add_option($option['content'], $option['label'], $option['key'] ?? null);
            } else {
                debugging('Invalid option format. Each option must have "content" and "label".', DEBUG_DEVELOPER);
            }
        }
    }

    /**
     * Get the current options.
     * @return array
     */
    public function get_options(): array {
        return $this->options;
    }

    /**
     * Get the current user.
     * @return stdClass
     */
    public function get_user(): stdClass {
        return $this->user;
    }

    /**
     * Get the output renderer.
     * @return renderer_base
     */
    public function get_output(): renderer_base {
        return $this->renderer;
    }

    /**
     * Generate a random key.
     * @return string
     */
    protected static function get_random_key() {
        static $increment = 0;
        $increment++;

        return "hookkey{$increment}";
    }

    /**
     * Return the keys of the current options.
     * @return array
     */
    protected function get_keys() {
        return array_column($this->options, 'key');
    }
}
