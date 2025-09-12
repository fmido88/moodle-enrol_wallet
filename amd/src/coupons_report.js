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
 * Handle coupons report bulk actions.
 *
 * @module     enrol_wallet/coupons_report
 * @copyright  2025 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import * as reportSelectors from 'core_reportbuilder/local/selectors';
import * as tableEvents from 'core_table/local/dynamic/events';
import * as FormChangeChecker from 'core_form/changechecker';
import * as CustomEvents from 'core/custom_interaction_events';
import $ from 'jquery';

const Selectors = {
    bulkDeleteForm: 'form#enrolwallet_coupondelete',
    couponsReportWrapper: '[data-region="coupons-table-report-wrapper"]',
    checkbox: 'input[type="checkbox"][data-togglegroup="report-select-all"][data-toggle="slave"]',
    masterCheckbox: 'input[type="checkbox"][data-togglegroup="report-select-all"][data-toggle="master"]',
    checkedRows: '[data-togglegroup="report-select-all"][data-toggle="slave"]:checked',
};

export const init = () => {
    let deleteForm = $(Selectors.bulkDeleteForm);
    let reportRegion = deleteForm.closest(Selectors.couponsReportWrapper).find(reportSelectors.regions.report);
    if (deleteForm.length < 1 || reportRegion.length < 1) {
        return;
    }

    let deleteButton = deleteForm.find('button[name="delete"]');
    CustomEvents.define(deleteButton, [CustomEvents.events.accessibleChange]);

    deleteButton.on(CustomEvents.events.accessibleChange + ', click', function() {
        const e = new Event('submit', {cancelable: true});
        deleteForm[0].dispatchEvent(e);
        if (!e.defaultPrevented) {
            FormChangeChecker.markFormSubmitted(deleteForm[0]);
            deleteForm.trigger('submit');
        }
    });

    // Every time the checkboxes in the report are changed, update the list of coupons id in the form values.
    const updateIds = () => {
        const selectedCoupons = [...reportRegion.find(Selectors.checkedRows)];
        const selectedCouponsIds = selectedCoupons.map(check => parseInt(check.value));
        deleteForm.find('[name="ids"]').val(selectedCouponsIds.join(','));

        // Disable the action selector if nothing selected, and reset the current selection.
        deleteButton.attr('disabled', selectedCoupons.length === 0);
    };

    updateIds();

    document.addEventListener('change', event => {
        // When checkboxes are checked next to individual users or the master toggle (Select all/none).
        if ((event.target.matches(Selectors.checkbox) || event.target.matches(Selectors.masterCheckbox))
                && reportRegion.find(event.target).length !== 0) {
            updateIds();
        }
    });

    document.addEventListener(tableEvents.tableContentRefreshed, event => {
        // When the report contents is updated (i.e. page is changed, filters applied, etc).
        if (reportRegion.find(event.target).length !== 0) {
            updateIds();
        }
    });
};