<?php
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
 * Strings for component 'enrol_wallet', language 'en'.
 *
 * @package    enrol_wallet
 * @copyright  2023 Mo Farouk <phun.for.physics@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
$string['alreadyenroled'] = 'You are already enrolled in this course, may be your time is out or you got suspended <br> contact your TA or TS for more assistance';
$string['allusers'] = 'transactions for all users selected';
$string['allowmultiple'] = 'Number of allowed instances';
$string['allowmultiple_help'] = 'Select the number of instances allowed in a single course, 0 means unlimited.';
$string['applycoupon'] = 'Apply Coupon';
$string['applycoupon_help'] = 'Apply Coupon code to get discount or have a fixed value to charge your wallet. <br>
If the coupon is a fixed value and it is greater than the fee of the course you will get enrolled.';
$string['amount'] = 'Amount';
$string['awards'] = 'awards program';
$string['awards_help'] = 'enable or disable the awards program in this course';
$string['awardcreteria'] = 'condition for getting awarded';
$string['awardcreteria_help'] = 'Awards is works when the student completed a course. What is the percent of the full mark the student get awarded if he exceeds it?';
$string['awardvalue'] = 'Award value';
$string['awardvalue_help'] = 'How much did the student get for each one grade above the condition?';
$string['awardingdesc'] = 'The user get awarded by {$a->amount} in course {$a->courseshortname} for getting {$a->usergrade} out of {$a->maxgrade}';
$string['awardsalter'] = 'Alter awards';
$string['awardsalter_help'] = 'Alter the status of awards program';
$string['availablebalance'] = 'Available Balance:';

$string['balance_after'] = 'Balance after';
$string['balance_before'] = 'Balance before';
$string['bulkfolder'] = 'Extra by wallet enrollments';
$string['bulkeditor'] = 'Bulk edit for enrollments';
$string['bulkeditor_head'] = 'Bulk Enrollment Edit (for all users in selected courses)';
$string['bulk_instancestitle'] = 'Bulk wallet enrol instances edit';
$string['bulk_instanceshead'] = 'Bulk Enrollment Edit (for all instances courses)';
$string['bulk_instancesno'] = 'No instances created or updated';
$string['bulk_instancesyes'] = '{$a->updated} enrol instances has been updated AND {$a->created} has been created.';

$string['cashbackdesc'] = 'added by cashback due to enrolment in {$a}';
$string['cashbackenable'] = 'Enable cashback';
$string['cashbackenable_desc'] = 'When this is enabled the student will receive a percentage cashback amount each time he uses the wallet to buy a course.';
$string['cashbackpercent'] = 'Percentage amount for cashback';
$string['cashbackpercent_help'] = 'The percentage amount as a cashback to the wallet from the paid amount by the wallet ballance.';
$string['canntenrol'] = 'Enrolment is disabled or inactive';
$string['canntenrolearly'] = 'You cannot enrol yet; enrolment starts on {$a}.';
$string['canntenrollate'] = 'You cannot enrol any more, since enrolment ended on {$a}.';
$string['checkout'] = '{$a->credit_cost} EGP will be deducted from your balance of {$a->user_balance} EGP.';
$string['checkout_discounted'] = '<del>{$a->credit_cost} EGP</del> {$a->after_discount} EGP will be deducted from your balance of {$a->user_balance} EGP.';
$string['characters'] = 'Characters in code.';
$string['characters_help'] = 'Choose the type of characters in the generated codes.';
$string['charger_novalue'] = 'No valid value entered.';
$string['charger_nouser'] = 'No user selected';
$string['charger_credit_desc'] = 'charging manually by {$a}';
$string['charger_debit_desc'] = '(deduct manually by {$a})';
$string['charger_debit_err'] = 'The value ({$a->value}) is greater that the user\'s balance ({$a->before})';
$string['charger_invalid_operation'] = 'Invalid operation.';
$string['chargingoptions'] = 'Charging user\'s wallet';
$string['chargingoperation'] = 'Operation';
$string['chargingvalue'] = 'Value';
$string['clear_filter'] = 'Clear Filters';
$string['cohortnonmemberinfo'] = 'Only members of cohort \'{$a}\' can enrol.';
$string['cohortonly'] = 'Only cohort members';
$string['cohortonly_help'] = 'Enrolment may be restricted to members of a specified cohort only. Note that changing this setting has no effect on existing enrollments.';
$string['conditionaldiscount'] = 'Conditional Discount';
$string['conditionaldiscount_link_desc'] = 'Add, Edit or delete conditional discount rules';
$string['conditionaldiscount_apply'] = 'Conditional discounts';
$string['conditionaldiscount_apply_help'] = 'Enable conditional discount for the whole website';
$string['condition'] = 'Condition';
$string['conditionaldiscount_condition'] = 'Condition for applying discount';
$string['conditionaldiscount_condition_help'] = 'Discounts won\'t be applied unless the user\'s wallet charged by more than or equal the value entered here.';
$string['conditionaldiscount_desc'] = 'charge wallet due to conditional discounts by {$a->rest} for charging wallet for more than {$a->condition}';
$string['conditionaldiscount_percent'] = 'The percentage amount of discount';
$string['conditionaldiscount_percent_help'] = 'The users get credited by this percent. (Applied only for charging the wallet)<br>
Important note: If the user choose to top-up the wallet by 400 and the discount percent set to 15%, the user pay only 340 and then a 60 will be add automatically.';
$string['conditionaldiscount_percentage'] = 'Percentage';
$string['conditionaldiscount_timeto'] = 'Available till';
$string['conditionaldiscount_timeto_help'] = 'Available till the date set, after it the condition is no longer applicable.';
$string['conditionaldiscount_timefrom'] = 'Available before';
$string['conditionaldiscount_timefrom_help'] = 'Available before the date set, before it the condition isn\'t applicable.';
$string['confirmbulkdeleteenrolment'] = 'Are you sure you want to delete these user enrollments?';
$string['confirmdeletecoupon'] = 'Are you sure you want to delete coupons with ids {$a}. This operation is irreversible.';
$string['confirmpayment'] = 'Confirm payment of {$a->value} {$a->currency}. Note that: press yes means that you have agreed to refund policy.<br> {$a->policy}';
$string['confirmpayment_discounted'] = 'Confirm payment of <del>{$a->before} {$a->currency}</del> {$a->value} {$a->currency}. Note that: press yes means that you have agreed to refund policy.<br> {$a->policy}';
$string['coupons'] = 'Coupons';
$string['coupon_applydiscount'] = 'You now have discounted by {$a}%';
$string['coupon_applyerror'] = 'ERROR invalid coupon code: <br> {$a}';
$string['coupon_applyfilter'] = 'Apply filter';
$string['coupon_applyfixed'] = 'Coupon code applied successfully with value of {$a->value} {$a->currency}.';
$string['coupon_applynocourse'] = 'Error during applying coupon, course not found.';
$string['coupon_applynothere'] = 'Cannot apply discount coupon here.';
$string['coupon_code'] = 'Coupon code';
$string['coupon_code_help'] = 'Enter the coupon code you want.';
$string['coupon_edit_title'] = 'coupon edit';
$string['coupon_edit_heading'] = 'Edit Coupon';
$string['coupon_exceedusage'] = 'This coupon exceeds the maximum usage';
$string['coupon_expired'] = 'This coupon is expired';
$string['coupon_generation'] = 'Create coupons';
$string['coupon_generation_title'] = 'generate coupons';
$string['coupon_generation_heading'] = 'Add new coupons';
$string['coupon_generation_method'] = 'Generation method';
$string['coupon_generation_method_help'] = 'Choose if you need to create a single coupon with a code of your choice or generate a number of random coupons';
$string['coupons_generation_success'] = '{$a} coupon codes successfully generated.';
$string['coupon_generator_nonumber'] = 'No number of coupons specified.';
$string['coupon_generator_error'] = 'Error while try to generate coupons.';
$string['coupons_length'] = 'Length';
$string['coupons_length_help'] = 'How many characters in a single coupon';
$string['coupon_novalue'] = 'the coupon return with no value, likely the coupon code not exist';
$string['coupon_notexist'] = 'This coupon not exist';
$string['coupon_notvalidyet'] = 'This coupon is not valid until {$a}';
$string['coupons_number'] = 'Number of coupons';
$string['coupons_number_help'] = 'Please don\'t set a large number to not overload the database.';
$string['coupons_maxusage'] = 'Maximum Usage';
$string['coupons_maxusage_help'] = 'How many times the coupon could be used. (0 means unlimited)';
$string['coupon_perpage'] = 'Coupons per page';
$string['coupon_resetusetime'] = 'Reset used';
$string['coupon_resetusetime_help'] = 'Reset the usage of the coupon to zero.';
$string['coupon_t_code'] = 'Code';
$string['coupon_t_value'] = 'Value';
$string['coupon_t_type'] = 'Type';
$string['coupon_t_usage'] = 'Usage';
$string['coupon_t_lastuse'] = 'Last use';
$string['coupon_t_timecreated'] = 'Time Created';
$string['coupon_table'] = 'View Wallet Coupons';
$string['coupon_type'] = 'Type of coupons';
$string['coupon_type_help'] = 'choose the type of coupons to generate.';
$string['coupon_value'] = 'Coupon value';
$string['coupon_value_help'] = 'Value of the coupon, fixed or percentage discounted value.';
$string['coupon_usetimes'] = 'Used times';
$string['coupon_update_failed'] = 'Failed to update the coupon.';
$string['coupon_update_success'] = 'The coupon updated successfully.';
$string['couponsall'] = 'Allow all types';
$string['couponsdeleted'] = '{$a} Coupons are deleted successfully';
$string['couponsdiscount'] = 'Discount coupons only';
$string['couponsfixed'] = 'Fixed amount coupons only';
$string['couponstype'] = 'Allow coupons';
$string['couponstype_help'] = 'Choose either to disable coupons, allow certain type or allow all.';
$string['coursesrestriction'] = 'Another course restriction';
$string['coursesrestriction_help'] = 'Only users enrolled in more than or equal the required number from the selected courses could purchase this course.';
$string['coursesrestriction_num'] = 'Number of required courses';
$string['coursesrestriction_num_help'] = 'Select the minimum required courses that the user must be enrolled in to purchase this course using this instance.';
$string['createdfrom'] = 'Created after';
$string['createdto'] = 'Created before';
$string['credit_cost'] = 'Cost';
$string['credit_cost_help'] = 'The fee that will be deducted when enrolling.';
$string['currency'] = 'Currency';
$string['currency_help'] = 'select the currency for payment for the course.';
$string['customcurrency'] = 'Custom Currency';
$string['customcurrency_desc'] = 'Adding custom currency name for the wallet credit.<br>Note that this is not valid along with using actual payment gateway.<br>If left blank a Wallet Coins will be added to currencies list.';
$string['customcurrencycode'] = 'Custom Currency Code';
$string['customcurrencycode_desc'] = 'Adding a code for the custom currency, Something like USD but make sure that this code not already exist as an available currency code in the available payment gateways because it will not ne overridden, but you can override Moodle Wallet Coin (MWC).';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}
* User email {$a->email}
* User fullname {$a->fullname}';

$string['datefrom'] = 'From';
$string['dateto'] = 'To';
$string['debitdesc_user'] = 'The user get charged by {$a->amount} by user of id {$a->charger}';
$string['debitdesc_course'] = 'The user get charged by {$a->amount} for enrolment in course {$a->coursename}';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during enrolment';
$string['deleteselectedusers'] = 'Delete selected user enrollments';
$string['digits'] = 'Digits (numbers)';
$string['discountscopouns'] = 'Discounts & Coupons';
$string['discountscopouns_desc'] = 'Choose if you want to apply percentage discounts to users using a custom profile field. <br>
Also, applying coupons for this plugin.';
$string['discountcoupondisabled'] = 'Discount coupons disabled in this website.';
$string['editselectedusers'] = 'Edit selected user enrollments';

$string['enablerefund'] = 'Enable refund';
$string['enablerefund_desc'] = 'If not checked, all credits from now on will be nonrefundable, don\'t forget to make it clear to users in refund policy';
$string['endpoint_error'] = 'endpoint return error';
$string['endpoint_incorrect'] = 'incorrect response';
$string['enrol_wallet'] = 'Enrol with wallet balance';
$string['enrol_type'] = 'Enrolment type';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can enrol themselves until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrollmentupdated'] = 'enrollment(s) has been updated';
$string['enrolme'] = 'Enrol me';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrolment is valid. If set to zero, the enrolment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrolment is valid, starting with the moment the user enrols themselves. If disabled, the enrolment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can enrol themselves from this date onward only.';
$string['event_transactions'] = 'Wallet Transaction Event';
$string['event_transaction_debit_description'] = 'The wallet balance of the user with id {$a->relateduserid} has been deducted by {$a->amount} by user of id {$a->userid} <br> more info: {$a->reason}';
$string['event_transaction_credit_description'] = 'The wallet balance of the user with id {$a->relateduserid} charged by {$a->amount} {$a->refundable} by user of id {$a->userid} <br> more info: {$a->reason}';
$string['event_award'] = 'Wallet award received';
$string['event_award_desc'] = 'User with id {$a->userid} get awarded by {$a->amount} due to getting a grade {$a->grade}% in course of id {$a->courseid}';
$string['event_cashback'] = 'Wallet cashback';
$string['event_cashback_desc'] = 'User with id {$a->userid} get a cashback in their wallet by amount({$a->amount}) due to paying {$a->original} to be enrolled in the course with id {$a->courseid}';
$string['event_coupon'] = 'Wallet Coupon used';
$string['event_coupon_desc'] = 'The coupon ( {$a->code} ) has been used by user of id {$a->userid}';
$string['event_newuser_gifted'] = 'New user gifted';
$string['event_newuser_gifted_desc'] = 'New user with id {$a->userid} gifted by {$a->amount} as a wallet ballance.';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course un-enrolment.';
$string['expirymessageenrollersubject'] = 'Enrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Enrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:
<br>
{$a->users}

To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrolledsubject'] = 'Enrolment expiry notification';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},
<br>
This is a notification that your enrolment in the course \'{$a->course}\' is due to expire on {$a->timeend}.
<br>
If you need help, please contact {$a->enroller}.';

$string['filter_coupons'] = 'Filter Coupons';
$string['filter_transaction'] = 'Filter Transactions';
$string['fixedvaluecoupon'] = 'Fixed value coupon';
$string['fixedcoupondisabled'] = 'Fixed value coupons are disabled in this website.';

$string['giftdesc'] = 'New user with id {$a->userid} at {$a->time} had a gift of amount {$a->amount} on his wallet.';
$string['giftvalue'] = 'New users gift value';
$string['giftvalue_help'] = 'The value which will be added to new users wallet.';

$string['insufficient_balance'] = 'You have insufficient wallet balance to enroll. {$a->cost_before} EGP are required, your balance is {$a->user_balance} EGP.';
$string['insufficient_balance_discount'] = 'You have insufficient wallet balance to enroll. <del>{$a->cost_before}EGP</del> {$a->cost_after} EGP are required, your balance is {$a->user_balance} EGP.';
$string['insufficientbalance'] = 'Sorry, you have insufficient balance for this operation. You need {$a->amount} while you have only {$a->balance}';
$string['inyourwallet'] = 'in your wallet.';
$string['invalidpercentcoupon'] = 'Invalid value for percentage coupon, cannot exceed 100.';
$string['invalidcoupon_operation'] = 'Invalid coupon operation, This coupon type may be disabled is this site or invalid configuration.';
$string['invalidvalue'] = 'Invalid Value, please enter a valid value.';

$string['longtimenosee'] = 'Un-enrol inactive after';
$string['longtimenosee_help'] = 'If users haven\'t accessed a course for a long time, then they are automatically unenrolled. This parameter specifies that time limit.';
$string['lowbalancenotification'] = 'Low Wallet Balance<br>Your balance is {$a}.';
$string['lowbalancenotify'] = 'Low Balance Notification.';
$string['lowbalancenotify_desc'] = 'If enabled and the user\'s balance is smaller than or equal the condition, a warrning notifications appears in every page in the website.';
$string['lowbalancenotice'] = 'Enable low balance notice';
$string['lowerletters'] = 'lower case';

$string['maxenrolled'] = 'Max enrolled users';
$string['maxenrolled_help'] = 'Specifies the maximum number of users that can enrol. 0 means no limit.';
$string['maxenrolledreached'] = 'Maximum number of users allowed to enrol was already reached.';
$string['messagesubject'] = 'Wallet Transactions ({$a})';
$string['messagebody_credit'] = 'Your wallet has been charged by {$a->amount}
<br>
Your balance before was {$a->before}
<br>
Your balance now is: {$a->balance}
<br>
more info: {$a->desc}. at: {$a->time}';
$string['messagebody_debit'] = 'An amount of {$a->amount} deduct from your wallet
<br>
Your balance before was {$a->before}
<br>
Your balance now is: {$a->balance}
<br>
more info: {$a->desc}. at: {$a->time}';
$string['messageprovider:expiry_notification'] = 'Wallet enrolment expiry notifications';
$string['messageprovider:wallet_transaction'] = 'Wallet transaction notifications';
$string['mustselectchar'] = 'Must select at least one charachter type.';
$string['MWC'] = 'Wallet Coins';
$string['mywallet'] = 'My Wallet';

$string['newenrols'] = 'Allow new enrolments';
$string['newenrols_desc'] = 'Allow users to enrol into new courses by default.';
$string['newenrols_help'] = 'This setting determines whether a user can enrol into this course.';
$string['newusergift'] = 'New users gifts';
$string['newusergift_desc'] = 'Apply wallet gift for new user in moodle website';
$string['newusergift_enable'] = 'Enable new user gifts';
$string['newusergift_enable_help'] = 'If enabled, new users will have the gift you decided in their wallet.';
$string['noaccount'] = 'No Account';
$string['nodiscountstoshow'] = 'No discounts to show.';
$string['not_set'] = 'Not set';
$string['notrefund'] = ' Nonrefundable (extra): ';
$string['nonrefundable'] = 'Nonrefundable';
$string['nonrefundable_transform_desc'] = "Transform the transaction to nonrefundable due to expiring of refund period.\n";
$string['nochange'] = 'No change';
$string['nocost'] = 'this course has invalid cost';
$string['nocoupons'] = 'Disable coupons';
$string['noticecondition'] = 'Min balance for notify';
$string['noticecondition_desc'] = 'If the balance is smaller than or equal this condition, a notification appear to the user.';

$string['othercourserestriction'] = 'Unable to enrol your self in this course unless you are enrolled in these courses {$a}';

$string['paymentaccount'] = 'Payment account';
$string['paymentaccount_help'] = 'choose the payment account in which you will accept payments';
$string['paymentrequired'] = 'You can pay for this course directly using available payment methods';
$string['paymenttopup_desc'] = 'Payment to topup the wallet';
$string['percentdiscountcoupon'] = 'Percentage discount coupon';
$string['pluginname'] = 'Wallet enrolment';
$string['pluginname_desc'] = '';
$string['purchase'] = 'Purchase';
$string['purchasedescription'] = 'Enrolment in course {$a}';
$string['profile_field_map'] = 'Profile field mapping';
$string['profile_field_map_help'] = 'Select the profile field that stores informations about discounts in user profiles.';
$string['privacy:metadata'] = 'The Wallet enrolment plugin does not store any personal data.';

$string['randomcoupons'] = 'Random Coupons';
$string['refundpolicy'] = 'Manual Refund Policy';
$string['refundpolicy_help'] = 'Define custom refund policy for users to be aware of the condition of how they get back their money or not before they topping up their wallet. This policy will be displayed to users in any form to recharge their wallet, or displying their balance.';
$string['refundpolicy_default'] = '<h5>Refund Policy</h5>
please note that:<br>
Payment to top-up your wallet cannot be refunded in the following cases:<br>
1- If this amount is due to new user gift, reward or a cashback.<br>
2- If the grace of refund period expired (14 days).<br>
3- Any amount already used in enrolment aren\'t refundable.<br>
When charging your wallet by any method means you agreed to this policy.';
$string['refundperiod'] = 'Refunding grace period';
$string['refundperiod_desc'] = 'The time after which user\'s cannot refunded for what they pay to top-up their wallets. 0 mean refund any time.';
$string['refunduponunenrol_desc'] = 'Refunded by amount of {$a->credit} after deduction unenrol fee of {$a->fee} in the course: {$a->coursename}.';
$string['receiver'] = 'Receiver';
$string['role'] = 'Default assigned role';

$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'When a user enrols in the course, they may be sent a welcome message email. If sent from the course contact (by default the teacher), and more than one user has this role, the email is sent from the first user to be assigned the role.';
$string['sender'] = 'Sender';
$string['sendexpirynotificationstask'] = "Wallet enrolment send expiry notifications task";
$string['sendpaymentbutton'] = 'direct payment';
$string['singlecoupon'] = 'Single coupon';
$string['status'] = 'Allow existing enrolments';
$string['status_desc'] = 'Enable Wallet enrolment method in new courses.';
$string['status_help'] = 'If enabled together with \'Allow new enrolments\' disabled, only users who enrolled previously can access the course. If disabled, this enrolment method is effectively disabled, since all existing enrolments are suspended and new users cannot enrol.';
$string['sourcemoodle'] = 'Internal moodle wallet';
$string['sourcewordpress'] = 'External Tera-wallet (WooWallet)';
$string['submit_coupongenerator'] = 'Create';
$string['syncenrolmentstask'] = 'Wallet enrolment synchronise enrolments task';

$string['mainbalance'] = 'Main balance: ';
$string['topup'] = 'topup';
$string['topupvalue'] = 'TopUp Value';
$string['topupvalue_help'] = 'Value to topup your wallet by using payment methods';
$string['topupcoupon_desc'] = 'by coupon code {$a}';
$string['topuppayment_desc'] = 'Topping up the wallet by payment of {$a} using payment gateway.';
$string['transactions'] = 'Wallet Transactions';
$string['transaction_type'] = 'Type of transaction';
$string['transaction_perpage'] = 'Trasactions per page';
$string['transfer'] = 'Transfer balance to other user';
$string['transfer_desc'] = 'Enable or disable the ability of users to transfer balance to other users and determine the transfer fee per each operation.';
$string['transfer_enabled'] = 'Transfer to other user';
$string['transfer_enabled_desc'] = 'Enable or disable the abiity for users to transfer balance to other users by email.';
$string['transfer_notenabled'] = 'User to user transfer isn\'t enabled in this site.';
$string['transferfee_desc'] = 'Note that there is a {$a->fee}% will be deducted from the {$a->from}.';
$string['transferfee_from'] = 'Deduct fees from:';
$string['transferfee_from_desc'] = 'Select how the fees get deducted.<br>
From sender: means that amount completely transfered and extra credit deducted from the sender.<br>
From receiver: means that the amount transfered to the reciever less than the sent amount by the fees.';
$string['transferop_desc'] = 'Transfering a net amount of {$a->amount} with a transfer fees {$a->fee} to {$a->receiver}';
$string['transferpercent'] = 'Transfer fees %';
$string['transferpercent_desc'] = 'In order to transfer some amount to other user a percentage fees will be deducted from the sender by default. Set it to 0 so there is no fee deducted.';
$string['transferpage'] = 'Transfer ballance';
$string['turn_not_refundable_task'] = 'Turn balance to non-refundable.';

$string['unenrol'] = 'Unenrol user';
$string['unenrollimitafter'] = 'Cannot unenrol self after:';
$string['unenrollimitafter_desc'] = 'Users cannot enrol themselfs after this period from enrolment start date. 0 means unlimited.';
$string['unenrollimitbefor'] = 'Cannot unenrol self before:';
$string['unenrollimitbefor_desc'] = 'Users cannot unenrol themselfs before this period from enrolment end date. 0 means no limit.';
$string['unenrolrefund'] = 'Refund upon unenrol?';
$string['unenrolrefund_desc'] = 'If enabled users will be refunded if they unenrolled from the corse.';
$string['unenrolrefundperiod'] = 'Refund upon unenrol grace period';
$string['unenrolrefundperiod_desc'] = 'If the user unenrolled within this period from the enrol start date he will be refunded.';
$string['unenrolrefundfee'] = 'Refund percentage fee';
$string['unenrolrefundfee_desc'] = 'Choose a percentage amount that will not be refunded after unenrol as a fee.';
$string['unenrolrefundpolicy'] = 'Unenrol Refunding Policy';
$string['unenrolrefundpolicy_help'] = 'If refunding upon unernol enabled, this policy will be visible to users befor enrol themselves to courses using wallet enrolment.<br>
placing {fee} in the policy will be replaced by the percentage fee.<br>
placing {period} will be replaced by the grace period in days.';
$string['unenrolrefundpolicy_default'] = '<p dir="ltr" style="text-align: left;"><strong>Conditions for refunding upon unenrol:</strong></p>
<p dir="ltr" style="text-align: left;">
If you are unenrolled from the course within {period} days from the start date you will be refunded with the amount you pay after deducting a {fee}% from the paid amount.
 This amount will return to your wallet and can use it to enrol in other courses but not be able to be manually refunded.<br>
By pressing purchace means you have agreed to this conditions.
</p>';
$string['unenrolrefund_head'] = 'Refund users upon unenrol.';
$string['unenrolrefund_head_desc'] = 'Return the paid fee of a course after unenrol from the course.';
$string['unenrolselfconfirm'] = 'Do you really want to unenrol yourself from course "{$a}"?';
$string['unenrolselfenabled'] = 'Enable self unenrol';
$string['unenrolselfenabled_desc'] = 'If enable, then users are allowed to unenrol themselfs from the course.';
$string['unenrolself_notallowed'] = 'You are not unenrol yourself from this course.';
$string['unenroluser'] = 'Do you really want to unenrol "{$a->user}" from course "{$a->course}"?';
$string['unenrolusers'] = 'Unenrol users';
$string['upperletters'] = 'UPPER case';
$string['usernotfound'] = 'No user found with this email {$a}';

$string['value'] = 'Amount per transaction';

$string['wallet:bulkedit'] = 'Bulk edit the enrolments in all courses';
$string['wallet:config'] = 'Configure Wallet enrol instances';
$string['wallet:creditdebit'] = 'Credit and Debit other users';
$string['wallet:createcoupon'] = 'Creating wallet coupons';
$string['wallet:deletecoupon'] = 'Deleting wallet coupon';
$string['wallet:downloadcoupon'] = 'Downloading wallet coupons';
$string['wallet:manage'] = 'Manage enrolled users';
$string['wallet:unenrol'] = 'Unenrol users from course';
$string['wallet:unenrolself'] = 'Unenrol self from the course';
$string['wallet:transaction'] = 'View the transaction table';
$string['wallet:viewcoupon'] = 'View wallet coupons table';
$string['wallet:viewotherbalance'] = 'View the wallet balance of others';
$string['walletcashback'] = 'Cashback for using wallet';
$string['walletcashback_desc'] = 'Enables the cashback program across the whole site';
$string['walletcredit'] = 'Wallet Credit';
$string['walletbulk'] = 'Wallet enrol instances bulk edit';
$string['walletsource'] = 'Wallet source';
$string['walletsource_help'] = 'Choose either to connect the wallet with external woocommerce Tera wallet, or just use internal wallet in moodle';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

If you have not done so already, you should edit your profile page so that we can learn more about you:

  {$a->profileurl}';
$string['wordpressurl'] = 'Wordpress url';
$string['wordpressurl_desc'] = 'Wordpress url with woo-wallet (tera wallet) plugin on it';
$string['wordpressloggins'] = 'Login/logout user from wordpress';
$string['wordpressloggins_desc'] = 'If enabled users is logged in and out from wordpress website when they logged in or out from moodle. (note that is one way only)';
$string['wordpress_secretkey'] = 'Secret Key';
$string['wordpress_secretkey_help'] = 'Admin must add any value here and the same value in moo-wallet setting in wordpress site.';
$string['wrongemailformat'] = 'Wrong Email format.';
$string['validfrom'] = 'Valid from';
$string['validto'] = 'Valid to';

$string['youhavebalance'] = 'You have balance:';
