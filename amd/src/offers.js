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
 * Handle the offers form adding and deleting cases.
 *
 * @module     enrol_wallet/offers
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import $ from 'jquery';
import Templates from 'core/templates';
/**
 * @type {JQuery<HTMLElement>}
 */
let container;
let courseid;
let i = 0;
let setsInc = {};
/**
 * Add form fragment in the offers container.
 *
 * @param {string} type
 * @param {string} parentSet
 * @param {JQuery<HTMLElement} placeholder
 * @param {Number} increment
 */
async function addFragment(type, parentSet, placeholder, increment) {
    let request = Ajax.call([{
        methodname: "enrol_wallet_get_offer_form_fragment",
        args: {
            type: type,
            increment: increment,
            course: courseid,
            parentset: parentSet
        }
    }]);
    let data = await request[0];
    placeholder.append(data.data);
    addDeleteButtonListener();
    addSelectListener();
}

/**
 * Add listeners to delete buttons.
 */
function addDeleteButtonListener() {
    let buttons = $('[data-action="deleteoffer"]');
    buttons.off('click');
    buttons.on('click', function(event) {
        event.preventDefault();
        event.stopPropagation();
        let set = $(this).closest('[data-action="offer-set"]');
        set.remove();
    });
}

/**
 * Add listener to select element to add new offers.
 */
function addSelectListener() {
    let select = $('select[name="add_offer"]');
    select.off('change');
    select.on('change', async function() {
        i++;
        let selector = $(this);
        await setLoading(selector, container);
        await addFragment(selector.val(), null, container, i);
        await setLoading(selector, container, false);
        selector.val('');
    });
    let subselect = $('select[data-action="add-sub-offer"]');
    subselect.off('change');
    subselect.on('change', async function() {
        let selector = $(this);
        let i = selector.data("group-increment");
        let container = selector.closest('div[data-action="offer-set"]');
        let groupName = selector.data("group-name");
        let placeholder = container.find('div[data-action="sub-offer-placeholder"][data-group-name="' + groupName + '"]');

        let inc;
        if (!setsInc[i]) {
            inc = selector.data("set-increment") + 1;
            selector.data("set-increment", inc);
            setsInc[i] = inc;
        } else {
            setsInc[i]++;
            inc = setsInc[i] + 1;
        }

        await setLoading(selector, placeholder);
        await addFragment(selector.val(), groupName, placeholder, inc);
        await setLoading(selector, placeholder, false);

        selector.val('');
        // eslint-disable-next-line no-console
        console.log(groupName, inc, i, selector, container, placeholder);
    });
}
/**
 *
 * @param {JQuery<HTMLElement} selector
 * @param {JQuery<HTMLElement} container
 * @param {Boolean} loading
 */
async function setLoading(selector, container, loading = true) {
    selector.attr('disabled', loading);
    if (loading) {
        let loading = document.createElement('div');
        loading.setAttribute("data-action", "loading-overlay");
        loading.innerHTML = await Templates.render('core/loading');
        container.append(loading);
    } else {
        container.find('div[data-action="loading-overlay"]').remove();
    }
}
/**
 * Init
 * @param {number} cid the course id
 * @param {number} inc the increment
 */
export const init = (cid, inc = 0) => {
    i = inc;
    courseid = cid;
    container = $('#id_wallet_offerscontainer');

    addDeleteButtonListener();
    addSelectListener();
};