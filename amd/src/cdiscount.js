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
 * Handling and calculate the values before and after discount in top up form and charger form.
 *
 * @module     enrol_wallet/cdiscount
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import {get_string} from 'core/str';

let form;
let valueInput;
let opInput;
let categoryInput;
let valueAfterInput;
let chargingLabel = '';
let calculateValueHolder;
// let conditions = [];
// let discounts = [];
// let categories = [];
let rules = [];

/**
 * For the charger form.
 * calculate the actual charge value and display it.
 */
function calculateCharge() {
    var value = parseFloat(valueInput.value);
    var op = opInput.value;
    var cat = parseInt(categoryInput.value);

    var maxDiscount = 0;
    var calculatedValue = value;
    for (var i = 0; i < rules.length; i++) {
        var category = rules[i].category;
        if (category !== cat) {
            continue;
        }
        var discount = rules[i].discount;
        var condition = rules[i].condition;
        var valueBefore = value + (value * discount / (1 - discount));

        if (valueBefore >= condition && discount > maxDiscount) {
            maxDiscount = discount;
            calculatedValue = valueBefore;
        }
    }

    if (op == "credit") {
        calculateValueHolder.innerHTML = chargingLabel + Math.round(calculatedValue * 100) / 100;
        calculateValueHolder.style.display = '';
    } else {
        calculateValueHolder.innerHTML = "";
        calculateValueHolder.style.display = 'none';
    }
}

/**
 * Add listeners for the inputs of charger form.
 */
function addListenersChargerForm() {
    valueInput.addEventListener('change', () => {
        calculateCharge();
    });
    valueInput.addEventListener('keyup', () => {
        calculateCharge();
    });
    opInput.addEventListener('change', () => {
        calculateCharge();
    });
    categoryInput.addEventListener('change', () => {
        calculateCharge();
    });
}

/**
 * continue the procedure of the charger form.
 */
function proceedChargerForm() {
    calculateValueHolder = form.querySelector("[data-holder=calculated-value]");
    opInput = form.querySelector("[name=op]");
    addListenersChargerForm();
    get_string('charging_value', 'enrol_wallet').done((data) => {
        chargingLabel = data;
    });
}

/**
 * Calculate the value after discount and put it in the discounted input.
 */
function calculateAfter() {
    var value = parseFloat(valueInput.value);
    var cat = parseInt(categoryInput.value);
    var maxDiscount = 0;
    for (var i = 0; i < rules.length; i++) {
        var category = rules[i].category;
        if (category !== cat) {
            continue;
        }
        var discount = rules[i].discount;
        var condition = rules[i].condition;

        if (value >= condition && discount > maxDiscount) {
            maxDiscount = discount;
        }
    }

    var calculatedValue = value - (value * maxDiscount);
    valueAfterInput.value = calculatedValue;
}

/**
 * Calculate the value before the discount and put it in the value input.
 */
function calculateBefore() {
    var value = parseFloat(valueAfterInput.value);
    var cat = parseInt(categoryInput.value);
    var maxDiscount = 0;
    for (var i = 0; i < rules.length; i++) {
        var category = rules[i].category;
        if (category !== cat) {
            continue;
        }
        var discount = rules[i].discount;
        var condition = rules[i].condition;

        var valueBefore = value / (1 - discount);
        if (valueBefore >= condition && discount > maxDiscount) {
            maxDiscount = discount;
        }
    }

    var realValueBefore = value / (1 - maxDiscount);
    valueInput.value = realValueBefore;
}

/**
 * Adding event listeners to the top up form.
 */
function addListenersTopUpForm() {
    valueInput.onchange = calculateAfter;
    valueInput.onkeyup = calculateAfter;
    valueAfterInput.onchange = calculateBefore;
    valueAfterInput.onkeyup = calculateBefore;
}

/**
 * Continue the procedure for the top up form.
 */
function proceedTopUpForm() {
    valueAfterInput = form.querySelector('[name=value-after]');
    addListenersTopUpForm();
}

export const init = (formid, formType) => {
    form = document.getElementById(formid);
    valueInput = form.querySelector("[name=value]");
    categoryInput = form.querySelector("[name=category]");

    for (let i = 1; ; i++) {
        let element = form.querySelector("[name=discount_rule_"+ i +"]");
        if (!element) {
            break;
        }
        var object = JSON.parse(element.value);
        object.condition = parseFloat(object.condition);
        object.discount = parseFloat(object.discount);
        object.category = parseInt(object.category);
        rules.push(object);
    }

    // for (let i = 1; ; i++) {
    //     let element = form.querySelector("[name=condition"+ i +"]");
    //     if (!element) {
    //         break;
    //     }
    //     conditions.push(parseFloat(element.value));
    // }

    // for (let i = 1; ; i++) {
    //     let element = form.querySelector("[name=category"+ i +"]");
    //     if (!element) {
    //         break;
    //     }
    //     categories.push(parseInt(element.value));
    // }

    if (formType == 'charge') {
        proceedChargerForm();
    } else {
        proceedTopUpForm();
    }
};