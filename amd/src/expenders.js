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
 * module expenders
 *
 * @module     enrol_wallet/expenders
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = (uniqid) => {
    var enrolWalletPolicyUrl = document.getElementById('enrol_wallet_transactions-policy-url-' + uniqid);
    if (enrolWalletPolicyUrl) {
        enrolWalletPolicyUrl.addEventListener("click", function() {
            var policy = document.getElementById('enrol_wallet_transactions-policy-' + uniqid);
            if (policy.style.display === "none") {
                policy.style.display = "block";
            } else {
                policy.style.display = "none";
            }
        });
    }
    var moreBalanceDetails = document.getElementById('more-details-' + uniqid);
    if (moreBalanceDetails) {
        moreBalanceDetails.addEventListener("click", function() {
            var details = document.getElementById('balance-details-' + uniqid);
            if (details.style.display === "none") {
                details.style.display = "flex";
            } else {
                details.style.display = "none";
            }
        });
    }
};