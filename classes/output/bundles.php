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

namespace enrol_wallet\output;

use core\output\renderable;
use core\output\single_button;
use core\output\templatable;
use core_course_category;
use enrol_wallet\local\config;
use enrol_wallet\local\discounts\discount_rules;
use enrol_wallet\local\urls\pages;
use renderer_base;
use stdClass;

/**
 * Class bundles
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bundles implements renderable, templatable {
    /**
     * Export a single bundle for template.
     * @param stdClass $record
     * @param renderer_base $output
     * @return array{after: float, before: mixed, currency: mixed, description: string, discount: string}
     */
    protected function export_single_bundle(stdClass $record, renderer_base $output): array {

        $before = $record->bundle;

        $discount = discount_rules::get_applied_discount($before, $record->category ?? 0);
        $after    = discount_rules::get_the_before($before, $record->category ?? 0, $discount);

        $desc = !empty($record->bundledesc) ? format_text($record->bundledesc, $record->descformat) : '';

        $config = config::make();

        $data = new \stdClass;
        $data->category = $record->category ?? 0;
        $data->value = $before;
        $data->instanceid = 0;
        $data->courseid = SITEID;
        $data->account = $config->account;
        $data->currency = $config->currency;

        $topupurl = pages::TOPUP->url((array)$data);

        $context = [
            'discount'    => format_float($discount, 2, true, true),
            'after'       => format_float($after, 2),
            'before'      => format_float($before, 2),
            'description' => $desc,
            'currency'    => $data->currency,
        ];

        if (empty($data->category)) {
            $context['category'] = get_string('site');
        } else {
            $category = core_course_category::get($data->category, IGNORE_MISSING);
            if (!$category) {
                // The category was deleted.
                return [];
            }
            $context['category'] = $category->get_nested_name();
        }

        $button = new single_button($topupurl, '');
        $buttoncontext = (array)$button->export_for_template($output);
        return $context + $buttoncontext;
    }
    /**
     * Export all bundles for template.
     * @param renderer_base $output
     * @return array{bundles: array, hasbundles: bool}
     */
    public function export_for_template(renderer_base $output) {
        $bundles = [];
        $records = discount_rules::get_bundles_records();

        foreach ($records as $record) {
            $bundle = $this->export_single_bundle($record, $output);
            if (!empty($bundle)) {
                $bundles[] = $bundle;
            }
        }

        return [
            'bundles'    => $bundles,
            'hasbundles' => !empty($bundles),
        ];
    }
}
