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
    // Manual refund policy.
    var enrolWalletPolicyUrl = document.getElementById('enrol_wallet_transactions-policy-url-' + uniqid);
    var policy = document.getElementById('enrol_wallet_transactions-policy-' + uniqid);
    if (enrolWalletPolicyUrl && policy) {
        enrolWalletPolicyUrl.addEventListener("click", function() {
            if (policy.style.display === "none") {
                policy.style.display = "block";
            } else {
                policy.style.display = "none";
            }
        });
    }

    // More balance details.
    var moreBalanceDetails = document.getElementById('more-details-' + uniqid);
    var details = document.getElementById('balance-details-' + uniqid);
    if (moreBalanceDetails && details) {
        moreBalanceDetails.addEventListener("click", function() {
            if (details.style.display === "none") {
                details.style.display = "flex";
            } else {
                details.style.display = "none";
            }
        });
    }

    // Show top up options after confirm the agreement.
    var walletPolicyAgreed = document.getElementById('wallet_topup_policy_confirm_' + uniqid);
    var topUpBox = document.getElementById('enrol_wallet_topup_box_' + uniqid);
    if (walletPolicyAgreed && topUpBox) {
        // As the user may click the check box while the page not loaded yet.
        setTimeout(function() {
            showHideTopUp();
        });

        walletPolicyAgreed.addEventListener('change', function() {
            showHideTopUp();
        });

        const showHideTopUp = () => {
            if (walletPolicyAgreed.checked == true) {
                topUpBox.style.display = 'block';
            } else {
                topUpBox.style.display = 'none';
            }
        };
    }
};