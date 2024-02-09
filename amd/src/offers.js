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
let container;
let courseid;
let selector;
let i = 0;

/**
 * Add form fragment in the offers container.
 *
 * @param {string} type
 */
function addFragment(type) {
    let request = Ajax.call([{
        methodname: "enrol_wallet_get_offer_form_fragment",
        args: {
            type: type,
            increment: i,
            course: courseid,
        }
    }]);
    request[0].done((data) => {
        container.innerHTML = container.innerHTML + data.data;
        addDeleteButtonListener();
        addSelectListener();
    });
}

/**
 * Add listeners to delete buttons.
 */
function addDeleteButtonListener() {
    for (let j = 1; j <= i; j++) {
        var deletebutton = document.querySelector('[name=offer_delete_' + j + ']');
        if (!deletebutton) {
            continue;
        }
        document.querySelector('[name=offer_delete_'+j+']').onclick = (event) => {
            let number = event.target.getAttribute('data-action-delete');
            let set = document.getElementById('offer_group_' + number);
            set.remove();
        };
    }
}

/**
 * Add listener to select element to add new offers.
 */
function addSelectListener() {
    let select = document.getElementsByName('add_offer');
    selector = select[0];
    selector.onchange = () => {
        i++;
        addFragment(selector.value);
        selector.value = '';
    };
}

/**
 * Init
 * @param {number} cid the course id
 * @param {number} inc the increment
 */
export const init = (cid, inc = 0) => {
    i = inc;
    courseid = cid;
    container = document.getElementById('id_wallet_offerscontainer');
    if (i > 0) {
        addDeleteButtonListener();
    }
    addSelectListener();
};