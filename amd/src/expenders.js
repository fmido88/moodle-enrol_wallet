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

import $ from 'jquery';

export const init = (uniqid) => {

    // Manual refund policy.
    let policyContainer = $('[data-wallet-purpose="policy-container"]');
    let policyLink = policyContainer.find('[data-wallet-action="toggle-policy"]');

    // eslint-disable-next-line no-console
    console.log(policyContainer, policyLink);
    policyLink.off("click");
    policyLink.on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();

        let policy = $(this).parent().siblings('[data-wallet-role="policy"]');

        // eslint-disable-next-line no-console
        console.log(policy);
        policy.toggle();
    });

    // More balance details.
    let moreBalanceDetails = $('#more-details-' + uniqid);
    let details = $('#balance-details-' + uniqid);

    moreBalanceDetails.off("click");
    moreBalanceDetails.on("click", function(e) {
        e.preventDefault();
        e.stopPropagation();
        e.stopImmediatePropagation();
        details.toggle();
    });

    // Show top up options after confirm the agreement.
    let walletPolicyAgreed = $('#wallet_topup_policy_confirm_' + uniqid);
    let topUpBox = $('#enrol_wallet_topup_box_' + uniqid);
    if (walletPolicyAgreed && topUpBox) {
        // As the user may click the check box while the page not loaded yet.
        setTimeout(function() {
            showHideTopUp();
        });

        walletPolicyAgreed.off('change');
        walletPolicyAgreed.on('change', function() {
            showHideTopUp();
        });

        const showHideTopUp = () => {
            if (walletPolicyAgreed.prop('checked')) {
                topUpBox.show();
            } else {
                topUpBox.hide();
            }
        };
    }
};