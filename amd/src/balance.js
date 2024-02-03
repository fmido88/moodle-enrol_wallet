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
 * module balance
 *
 * @module     enrol_wallet/balance
 * @copyright  2024 2024, Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

let holder;
let button;
let form;
let userInput;

/**
 * Get the balance data through ajax call.
 */
function get_data() {
    let userid = parseInt(userInput.value);

    if (userid && !isNaN(userid)) {

        let request = Ajax.call([{
                                    methodname: 'enrol_wallet_get_balance_details',
                                    args: {
                                        userid: userid
                                    }
                                }]);

        request[0].catch((e) => {
            holder.innerHTML = '<pre>' + e.message + e.backtrace + '</pre>';
        });
        request[0].done((data) => {
            holder.innerHTML = data.details;
            let uniqIdHolder = holder.querySelector('[data-identifier=uniqid');
            let uniqId = uniqIdHolder.getAttribute('data-uniqid');
            require(['enrol_wallet/expenders'], function(ex) {
                ex.init(uniqId);
            });
        });
    }
}
export const init = (formid) => {
    form = document.getElementById(formid);
    holder = form.querySelector("[data-purpose=balance-holder]");
    button = form.querySelector("[name=displaybalance]");
    userInput = form.querySelector("[name=userlist]");

    button.onclick = () => {
        get_data();
    };
    userInput.onchange = () => {
        holder.innerHTML = '';
    };
};
