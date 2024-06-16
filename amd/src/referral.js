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
 * TODO describe module referral
 *
 * @module     enrol_wallet/referral
 * @copyright  2024 YOUR NAME <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
// eslint-disable-next-line camelcase
import {get_strings} from 'core/str';
import Prefetch from 'core/prefetch';

// Prefetch required string while the page loading.
Prefetch.prefetchStrings('enrol_wallet', ['referral_copy_to_clipboard', 'referral_copied']);

let copyTooltip;
let copiedTooltip;
let shareUrl;
let copyButtonCode;
let copyButtonUrl;
let uniqueId;
/**
 *
 */
function shareOnFacebook() {
    let fbBase = 'https://www.facebook.com/sharer/sharer.php?u=';
    window.open(fbBase + encodeURIComponent(shareUrl), 'facebook-share-dialog', 'width=800,height=600');
}

/**
 *
 */
function shareOnWhatsApp() {
    window.open('https://wa.me/?text=' + encodeURIComponent(shareUrl));
}

/**
 *
 */
function shareOnTelegram() {
    window.open('https://t.me/share/url?url=' + encodeURIComponent(shareUrl));
}

/**
 * Copy the text in the input value.
 * @param {string} target
 * @param {DOMElement} element
 */
function copyText(target, element) {
    let input = $('#' + target + '_' + uniqueId);

    navigator.clipboard.writeText(input[0].value);

    element.setAttribute('title', copiedTooltip);
}

const resetTooltip = (element) => {
    element.setAttribute('title', copyTooltip);
};

/**
 * Add listeners to copy and sharing buttons.
 */
function addListeners() {
    copyButtonUrl.addEventListener('click', () => {
        copyText('url', copyButtonUrl);
    });
    copyButtonUrl.addEventListener('mouseleave', () => {
        resetTooltip(copyButtonUrl);
    });
    copyButtonCode.addEventListener('click', () => {
        copyText('code', copyButtonCode);
    });
    copyButtonCode.addEventListener('mouseleave', () => {
        resetTooltip(copyButtonCode);
    });

    $('a[href="#shareOnFacebook"]').on("click", () => {
        shareOnFacebook();
    });
    $('a[href="#shareOnTelegram"]').on("click", () => {
        shareOnTelegram();
    });
    $('a[href="#shareOnWhatsApp"]').on("click", () => {
        shareOnWhatsApp();
    });
}

/**
 * Initiate the module.
 *
 * @param {string} url the referral url
 * @param {string} id the unique id of the template
 */
export const init = (url, id) => {
    var strings = [
        {
            key: 'referral_copy_to_clipboard',
            component: 'enrol_wallet'
        },
        {
            key: 'referral_copied',
            component: 'enrol_wallet',
        }
    ];
    get_strings(strings).then(function(results) {
        copyTooltip = results[0];
        copiedTooltip = results[1];
        return true;
    }).fail(() => {
        return false;
    });
    shareUrl = url;
    uniqueId = id;
    copyButtonUrl = document.getElementById('copy_url_' + id);
    copyButtonCode = document.getElementById('copy_code_' + id);

    addListeners();
};