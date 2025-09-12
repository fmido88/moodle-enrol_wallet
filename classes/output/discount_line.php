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

use core\output\named_templatable;
use core\output\renderable;
use core_course_category;
use enrol_wallet\local\discounts\discount_rules as rules;
use stdClass;

/**
 * Discount lines widget.
 *
 * @package    enrol_wallet
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discount_line implements renderable, named_templatable {
    /**
     * If there is any bundle to show.
     * @var bool
     */
    protected bool $hasbundles = true;

    /**
     * If multilines (all categories) or single line (one category).
     * @var bool
     */
    protected bool $multilines = false;

    /**
     * Discount rules records.
     * @var array
     */
    protected array $records = [];

    /**
     * Constructor.
     * @param  int  $catid
     * @return void
     */
    public function __construct(int $catid = 0) {
        $enabled = (bool)get_config('enrol_wallet', 'conditionaldiscount_apply');

        if (!$enabled) {
            $this->hasbundles = false;

            return;
        }

        $this->multilines = $catid < 0;

        $this->records = ($catid < 0)
                        ? rules::get_all_available_discount_rules()
                        : rules::get_current_discount_rules($catid);

        if (empty($this->records)) {
            $this->hasbundles = false;
        }
    }

    /**
     * Get maximum conditions for each category.
     * @return array
     */
    public function get_maximum_conditions(): array {
        $maxconditions = [];

        foreach ($this->records as $record) {
            if ($record->cond > ($maxconditions[$record->category ?? 0] ?? 0)) {
                $maxconditions[$record->category] = $record->cond;
            }
        }

        return $maxconditions;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     * @param  \core\output\renderer_base                                   $output
     * @return array{data: array, hasbundles: bool|array{hasbundles: bool}}
     */
    public function export_for_template(\core\output\renderer_base $output) {
        if (!$this->hasbundles) {
            return ['hasbundles' => false];
        }

        $maxconditions = $this->get_maximum_conditions();

        $currency = get_config('enrol_wallet', 'currency');

        $data      = [];
        $discounts = [];
        $catid     = -1;

        foreach ($this->records as $id => $record) {
            if ($catid != $record->category) {
                if (isset($data[$catid])) {
                    $data[$catid]->discounts = array_values($discounts);
                    $discounts               = [];
                }

                $catid = $record->category;

                $data[$catid] = new \stdClass();

                $data[$catid]->catid = $catid;
                $data[$catid]->count = 0;

                if (empty($catid)) {
                    $name = get_string('site');
                } else {
                    $category = core_course_category::get($catid, IGNORE_MISSING);

                    if ($category) {
                        $name = $category->get_nested_name(false);
                    } else {
                        // Don't display hidden or deleted categories.
                        continue;
                    }
                }

                $data[$catid]->heading = $name;

                $prevwidth = 0;
            }

            $maxcondition = $maxconditions[$catid] * 1.2;
            $data[$catid]->count++;

            $cond = (float)$record->cond;

            $discounts[$id] = new stdClass();

            $discounts[$id]->percent   = (100 - ($cond / $maxcondition) * 100) - $prevwidth;
            $discounts[$id]->order     = (int)round($cond / $maxcondition * 10);
            $discounts[$id]->color     = (int)round((1 - $cond / $maxcondition) * 255);
            $discounts[$id]->condition = '> ' . format_float($cond, 2) . " $currency";
            $discounts[$id]->discount  = format_float($record->percent, 2) . '%';

            $prevwidth = $discounts[$id]->percent;
        }
        
        if (!empty($discounts) && isset($data[$catid])) {
            $data[$catid]->discounts = array_values($discounts);
        }

        return [
            'hasbundles' => true,
            'data'       => array_values($data),
        ];
    }

    /**
     * Get the name of the template to use for this renderable.
     * @param  \core\output\renderer_base $renderer
     * @return string
     */
    public function get_template_name(\core\output\renderer_base $renderer): string {
        return 'enrol_wallet/discount-line';
    }
}
