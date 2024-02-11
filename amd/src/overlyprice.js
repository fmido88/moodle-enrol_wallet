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
 * module overlyprice
 *
 * @module     enrol_wallet/overlyprice
 * @copyright  2024 Mohammad Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const config = {attributes: true, childList: true, subtree: true};
// Create an observer instance linked to the callback function
let observer;
let regionMain;

const callback = (mutationList) => {
    for (const mutation of mutationList) {
        if (mutation.type === "childList" && mutation.addedNodes.length > 0) {
            for (let i = 0; i < mutation.addedNodes.length; i++) {
                let node = mutation.addedNodes[i];
                if (node instanceof HTMLElement) {
                     searchAndInject(node);
                }
            }
        }
    }
};

/**
 * Search for enrol wallet icons and inject price and offers.
 * @param {HTMLElement} node
 */
function searchAndInject(node = document) {
    stopObserver();
    let icons = node.querySelectorAll('[data-selector=enrol_wallet_icon]');

    for (let i = 0; i < icons.length; i++) {
        let instanceId = icons[i].getAttribute('data-instance-id');
        let parent = icons[i].parentNode;
        let id = 'enrol-wallet-cost-overlay-' + instanceId;
        let exist = parent.querySelector('[data-identifier=' + id + ']');
        if (exist) {
            continue;
        }

        let cost = icons[i].getAttribute('data-cost');
        let discount = icons[i].getAttribute('data-discount');
        var titleElement = document.createElement('div');
        titleElement.textContent = cost;
        titleElement.dataIdentifier = id;
        titleElement.title = icons[i].title;
        titleElement.classList.add('enrol_wallet_walletcost');

        let container = document.createElement('div');
        container.className = 'wallet-icon';
        container.style.position = 'relative';
        container.style.display = 'flex';
        icons[i].className = 'icon';
        parent.style.display = 'flex';
        parent.style.justifyContent = 'center';
        parent.style.alignItems = 'center';

        container.appendChild(icons[i].cloneNode(true));
        container.appendChild(titleElement);

        if (discount > 0) {
            var discountLabel = document.createElement('div');
            discountLabel.classList.add('enrol_wallet_offer');
            discountLabel.innerHTML = '<div class="enrol-wallet-inner-offer">' + discount + '%' + '</div>';
            let found = false;
            while (!found) {
                if (parent.getAttribute('data-courseid') || parent.getAttribute('data-course-id')) {
                    parent.style.position = 'relative';
                    parent.appendChild(discountLabel);
                    found = true;
                    break;
                }
                parent = parent.parentNode;
                if (parent.tagName == 'BODY') {
                    break;
                }
            }
        }
        icons[i].replaceWith(container);
    }
    startObserver();
}

/**
 * Start document mutation observation.
 */
function startObserver() {
    regionMain = document.getElementById("region-main");
    observer.observe(regionMain, config);
}

/**
 * Stop mutation observer.
 */
function stopObserver() {
    observer.disconnect();
}

/**
 * Init
 */
export const init = () => {
    observer = new MutationObserver(callback);
    setTimeout(function() {
        searchAndInject();
    }, 1000);
};