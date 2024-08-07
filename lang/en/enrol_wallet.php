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


$string['MWC'] = 'Wallet Coins';


$string['addbundle'] = 'Add bundle';
$string['addbundle_help'] = 'Add a quick top up value associated with this conditional discount rule.';
$string['agreepolicy_intro'] = 'In order to perform any wallet top up process it means that you have <strong>read and agreed</strong> the manual refund policy.<br/>
Click on the link below to read the policy.<br/>';
$string['agreepolicy_label'] = 'I agree to the manual refund policy.';
$string['allowmultiple'] = 'Number of allowed instances';
$string['allowmultiple_help'] = 'Select the number of instances allowed in a single course, 0 means unlimited.';
$string['allusers'] = 'Transactions for all users selected';
$string['alreadyenroled'] = 'You are already enrolled in this course, may be your time is out or you got suspended <br> contact your TA or TS for more assistance';
$string['amount'] = 'Amount';
$string['applycoupon'] = 'Apply coupon';
$string['applycoupon_help'] = 'Apply Coupon code to get a discount or have a fixed value to charge your wallet. <br>
If the coupon is a fixed value and it is greater than the fee of the course you will get enrolled.';
$string['availability_form_desc'] = 'Note that some of the availability restrictions depends on the modules on this course like activity completion or grade, but now we can just include other courses by increase the number of required courses and select all the courses we need and save the form, return again and all the parameters needed from this courses will be included.';
$string['availability_plugins'] = 'Availability plugins';
$string['availability_plugins_desc'] = 'Choose from here the only suitable availability condition plugin that suits your logical need to prevent course creator to ad non-suitable restrictions.';
$string['availablebalance'] = 'Available balance:';
$string['awardcreteria'] = 'Condition for getting awarded';
$string['awardcreteria_help'] = 'Awards\' works when the student completed a course. What is the percentage of the full mark the student gets awarded if he exceeds it?';
$string['awardingdesc'] = 'The user get awarded by {$a->amount} in course {$a->courseshortname} for getting {$a->usergrade} out of {$a->maxgrade}';
$string['awards'] = 'Awards program';
$string['awards_help'] = 'Enable or disable the awards program in this course';
$string['awardsalter'] = 'Alter awards';
$string['awardsalter_help'] = 'Alter the status of awards program';
$string['awardssite'] = 'Enable awards';
$string['awardssite_help'] = 'Enable the ability for the course creator to set awards for the course.';
$string['awardvalue'] = 'Award value';
$string['awardvalue_help'] = 'How much did the student get for each one grade above the condition?';


$string['balance'] = 'Balance';
$string['balance_after'] = 'Balance after';
$string['balance_before'] = 'Balance before';
$string['borrow'] = 'Borrowing balance';
$string['borrow_desc'] = 'Enable and set condition to make trusted users able to get enrolled in courses without having sufficient balance, their balance become negative and they had to recharge the wallet to pay for it later.';
$string['borrow_enable'] = 'Enable borrowing';
$string['borrow_enable_help'] = 'If enabled, students met the conditions will be able to be enrolled in the courses even with insufficient balance.';
$string['borrow_period'] = 'Transactions period for borrowing.';
$string['borrow_period_help'] = 'The period for at which the user perform the previous number of transaction to be eligible for borrowing.';
$string['borrow_trans'] = 'Transactions for borrowing';
$string['borrow_trans_help'] = 'Number of credit transactions in a given time period required so the user will be eligible for borrowing balance.';
$string['bulk_instanceshead'] = 'Bulk Enrollment Edit (for all instances courses)';
$string['bulk_instancesno'] = 'No instances created or updated';
$string['bulk_instancestitle'] = 'Bulk wallet enrol instances edit';
$string['bulk_instancesyes'] = '{$a->updated} enrol instances has been updated AND {$a->created} has been created.';
$string['bulkeditor'] = 'Bulk edit for enrollments';
$string['bulkeditor_head'] = 'Bulk Enrollment Edit (for all users in selected courses)';
$string['bulkfolder'] = 'Extra by wallet enrollments';
$string['bundle_desc'] = 'The bundle description';
$string['bundle_desc_help'] = 'Add a description for this bundle. (ex. enough for 11 courses in the price of 9)';
$string['bundle_value'] = 'Quick top up value';
$string['bundle_value_error'] = 'bundle value should be greater than or equals the condition';
$string['bundle_value_help'] = 'This value must be greater than or equal to the condition. Also make sure this is the value before discount.';
$string['bundlevalidin'] = 'valid to be used in';


$string['cachedef_balance'] = 'This is store the user\'s balance details';
$string['cachedef_coupon'] = 'Storing the coupon data';
$string['cachedef_offers'] = 'Offers cache data';
$string['cannotdeductbalance'] = 'Cannot deduct balance due to an error. Please try again and if the problem still exist contact site support.';
$string['canntenrol'] = 'Enrolment is disabled or inactive';
$string['canntenrolearly'] = 'You cannot enrol yet; enrolment starts on {$a}.';
$string['canntenrollate'] = 'You cannot enrol any more, since enrolment ended on {$a}.';
$string['cashback'] = 'Cashback';
$string['cashback_desc'] = 'You will get a {$a}% cashback each time you purchase a course use wallet enrollment method.';
$string['cashbackdesc'] = 'added by cashback due to enrolment in {$a}';
$string['cashbackenable'] = 'Enable cashback';
$string['cashbackenable_desc'] = 'When this is enabled the student will receive a percentage cashback amount each time he uses the wallet to buy a course.';
$string['cashbackpercent'] = 'Percentage amount for cashback';
$string['cashbackpercent_help'] = 'The percentage amount as a cashback to the wallet from the paid amount by the wallet balance.';
$string['catbalance'] = 'Category balance';
$string['catbalance_desc'] = 'If enabled, then balance could be specified for each category separately and only be used in this category also there is still there a site balance which could be used anywhere';
$string['category_options'] = 'Category';
$string['category_options_help'] = 'Same as fixed coupons except it is restricted to be used unless in the chosen category';
$string['categorycoupon'] = 'Category coupon';
$string['categorycoupondisabled'] = 'Category coupons disabled.';
$string['ch_result_after'] = '<p>Balance After: <b>{$a}</b></p>';
$string['ch_result_before'] = '<p>Balance Before: <b>{$a}</b></p>';
$string['ch_result_error'] = '<p style = "text-align: center;"><b> ERROR <br>{$a}<br> Please go back and check it again</b></p>';
$string['ch_result_info_balance'] = '<span style="text-align: center; width: 100%;"><h5>
the user: {$a->userfull} is having a balance of {$a->before}
</h5></span>';
$string['ch_result_info_charge'] = '<span style="text-align: center; width: 100%;">
<h5>the user: {$a->userfull} is now having a balance of {$a->after} after charging him/her by {$a->after_before}...</h5>
</span>';
$string['ch_result_negative'] = '<p><b>THIS USER HAS A NEGATIVE BALANCE</b></p>';
$string['characters'] = 'Characters in code.';
$string['characters_help'] = 'Choose the type of characters in the generated codes.';
$string['charge'] = 'Charge';
$string['charger_credit_desc'] = 'charging manually by {$a}';
$string['charger_debit_desc'] = '(deduct manually by {$a})';
$string['charger_debit_err'] = 'The value ({$a->value}) is greater that the user\'s balance ({$a->before})';
$string['charger_invalid_operation'] = 'Invalid operation.';
$string['charger_nouser'] = 'No user selected';
$string['charger_novalue'] = 'No valid value entered.';
$string['charging_value'] = 'Charging value: ';
$string['chargingoperation'] = 'Operation';
$string['chargingoptions'] = 'Charging user\'s wallet';
$string['chargingvalue'] = 'Value';
$string['checkout'] = '{$a->credit_cost} {$a->currency} will be deducted from your balance of {$a->user_balance} {$a->currency}.';
$string['checkout_borrow'] = '{$a->credit_cost} {$a->currency} needed for enrolment, your balance {$a->user_balance} {$a->currency} will be deducted and borrow {$a->borrow}.';
$string['checkout_borrow_discounted'] = '<del>{$a->credit_cost} {$a->currency}</del> {$a->after_discount} {$a->currency} needed for enrolment, your balance {$a->user_balance} {$a->currency} will be deducted and borrow {$a->borrow}.';
$string['checkout_discounted'] = '<del>{$a->credit_cost} {$a->currency}</del> {$a->after_discount} {$a->currency} will be deducted from your balance of {$a->user_balance} {$a->currency}.';
$string['cleanupwalletitemstask'] = 'Cleanup orphaned and expired wallet items';
$string['clear_filter'] = 'Clear filters';
$string['cohortnonmemberinfo'] = 'Only members of cohort \'{$a}\' can enrol.';
$string['cohortonly'] = 'Only cohort members';
$string['cohortonly_help'] = 'Enrolment may be restricted to members of a specified cohort only. Note that changing this setting has no effect on existing enrollments.';
$string['condition'] = 'Condition';
$string['conditionaldiscount'] = 'Conditional discount';
$string['conditionaldiscount_apply'] = 'Conditional discounts';
$string['conditionaldiscount_apply_help'] = 'Enable conditional discount for the whole website';
$string['conditionaldiscount_condition'] = 'Condition for applying discount';
$string['conditionaldiscount_condition_help'] = 'Discounts won\'t be applied unless the user\'s wallet charged by more than or equal the value entered here.';
$string['conditionaldiscount_desc'] = 'charge wallet due to conditional discounts by {$a->rest} for charging wallet for more than {$a->condition}';
$string['conditionaldiscount_link_desc'] = 'Add, edit or delete conditional discount rules';
$string['conditionaldiscount_percent'] = 'The percentage amount of discount';
$string['conditionaldiscount_percent_help'] = 'The users get credited by this percent. (Applied only for charging the wallet)<br>
Important note: If the user choose to top-up the wallet by 400 and the discount percent set to 15%, the user pay only 340 and then a 60 will be add automatically.';
$string['conditionaldiscount_percentage'] = 'Percentage';
$string['conditionaldiscount_timefrom'] = 'Available after';
$string['conditionaldiscount_timefrom_help'] = 'Available after the date set, before it the condition isn\'t applicable.';
$string['conditionaldiscount_timeto'] = 'Available till';
$string['conditionaldiscount_timeto_help'] = 'Available till the date set, after it the condition is no longer applicable.';
$string['confirm_additional_credit'] = '<strong> With addition to {$a} due to conditional discount.</strong>';
$string['confirm_credit'] = 'You are about to add an amount of {$a->amount} to the user {$a->name} <strong>{$a->category} wallet</strong> who already got a balance of {$a->balance} valid in it.';
$string['confirm_debit'] = 'You are about to deduct an amount of {$a->amount} from the user {$a->name} in the <strong>{$a->category} balance</strong> whose current balance is {$a->balance} valid to be used in {$a->category}. The balance after transaction should be {$a->after}';
$string['confirm_enrol_confirm'] = 'You are about to get enrolled is the course <strong>{$a->course}</strong>. <br>
This require a <strong>{$a->cost} {$a->currency}</strong> to be deducted from you balance. <br>
Your current balance is {$a->balance}<br>
<p>{$a->policy}</p>
<strong> This operation is nonreversible.<br>
Are you sure?</strong>';
$string['confirm_enrol_error'] = 'Invalid access to enrol page.';
$string['confirm_negative'] = '<b>Negative balance warning:</b> the user balance will be with negative value after this transaction.';
$string['confirmbulkdeleteenrolment'] = 'Are you sure you want to delete these user enrollments?';
$string['confirmdeletecoupon'] = 'Are you sure you want to delete coupons with ids {$a}. This operation is irreversible.';
$string['confirmedit'] = 'Confirm edit';
$string['confirmpayment'] = 'Confirm payment of {$a->value} {$a->currency}. Note that: press yes means that you have agreed to refund policy.<br> {$a->policy}';
$string['confirmpayment_discounted'] = 'Confirm payment of <del>{$a->before} {$a->currency}</del> {$a->value} {$a->currency}. Note that: press yes means that you have agreed to refund policy.<br> {$a->policy}';
$string['coupon_applydiscount'] = 'You now have discounted by {$a}%';
$string['coupon_applyerror'] = 'ERROR invalid coupon code: <br> {$a}';
$string['coupon_applyfilter'] = 'Apply filter';
$string['coupon_applyfixed'] = 'Coupon code applied successfully with value of {$a->value} {$a->currency}.';
$string['coupon_applynocourse'] = 'Error during applying coupon, course not found.';
$string['coupon_applynothere'] = 'Cannot apply this type of coupons here.';
$string['coupon_applynothere_category'] = 'Cannot apply category coupon here as it is meant to be used in specific category only.';
$string['coupon_applynothere_discount'] = 'Cannot apply discount coupon here.';
$string['coupon_applynothere_enrol'] = 'Cannot apply enrolment coupons here. Please use it on the course page';
$string['coupon_cat_notsufficient'] = 'The value of this coupon is not sufficient to be used in this course.';
$string['coupon_categoryapplied'] = 'The coupon has been applied.';
$string['coupon_categoryfail'] = 'Sorry, this coupon can be only applied in this category: {$a}';
$string['coupon_code'] = 'Coupon code';
$string['coupon_code_applied'] = 'Coupon code {$a} applied.';
$string['coupon_code_error'] = 'Please enter a code or select random method';
$string['coupon_code_help'] = 'Enter the coupon code you want.';
$string['coupon_edit_heading'] = 'Edit coupon';
$string['coupon_edit_title'] = 'Coupon edit';
$string['coupon_enrolapplied'] = 'The coupon has been applied';
$string['coupon_enrolerror'] = 'Sorry, this coupon can be only applied in these courses:<br>{$a}';
$string['coupon_exceedusage'] = 'This coupon exceeds the maximum usage';
$string['coupon_exist'] = 'This coupon code already existed.';
$string['coupon_expired'] = 'This coupon is expired';
$string['coupon_generation'] = 'Create coupons';
$string['coupon_generation_heading'] = 'Add new coupons';
$string['coupon_generation_method'] = 'Generation method';
$string['coupon_generation_method_help'] = 'Choose if you need to create a single coupon with a code of your choice or generate a number of random coupons';
$string['coupon_generation_title'] = 'Generate coupons';
$string['coupon_generator_error'] = 'Error while try to generate coupons.';
$string['coupon_generator_nonumber'] = 'No number of coupons specified.';
$string['coupon_generator_peruser_gt_max'] = 'Max allowed usage per user should not exceeds the maximum usage of a coupon.';
$string['coupon_invalidid'] = 'A coupon record with this id not exist or doesn\'t match the code.';
$string['coupon_invalidrecord'] = 'Invalid coupon record.';
$string['coupon_invalidreturntype'] = 'Invalid coupon type returned, there is an endpoint error or this in not a valid coupon code.';
$string['coupon_invalidtype'] = 'Invalid coupon type, only fixed, percent, enrol and category allowed.';
$string['coupon_nocode'] = 'There is no code.';
$string['coupon_notexist'] = 'This coupon not exist';
$string['coupon_notvalidyet'] = 'This coupon is not valid until {$a}';
$string['coupon_novalue'] = 'the coupon return with no value, likely the coupon code not exist';
$string['coupon_perpage'] = 'Coupons per page';
$string['coupon_resetusetime'] = 'Reset used';
$string['coupon_resetusetime_help'] = 'Reset the usage of the coupon to zero.';
$string['coupon_t_code'] = 'Code';
$string['coupon_t_lastuse'] = 'Last use';
$string['coupon_t_timecreated'] = 'Time Created';
$string['coupon_t_type'] = 'Type';
$string['coupon_t_usage'] = 'Usage';
$string['coupon_t_value'] = 'Value';
$string['coupon_table'] = 'View wallet coupons';
$string['coupon_type'] = 'Type of coupons';
$string['coupon_type_help'] = 'choose the type of coupons to generate.<br>
Fixed value coupons: used any where and top up the user\'s wallet by its value, and if used in enrollment page, it will enrol the user to the course if it is sufficient.<br>
Percentage discount coupons: Used to get a percentage discount on the course cost.
Category coupons: same as fixed coupons except it cannot be used anywhere, only to enrol user in the selected category.
Courses coupons: these coupons has no value, it is used to enrol the users in one of the selected courses.';
$string['coupon_update_failed'] = 'Failed to update the coupon.';
$string['coupon_update_success'] = 'The coupon updated successfully.';
$string['coupon_usage'] = 'Coupons usage history';
$string['coupon_usetimes'] = 'Used times';
$string['coupon_value'] = 'Coupon value';
$string['coupon_value_help'] = 'Value of the coupon, fixed or percentage discounted value.';
$string['coupons'] = 'Coupons';
$string['coupons_category_error'] = 'Must select category';
$string['coupons_courseserror'] = 'Must select at least one course.';
$string['coupons_delete_selected'] = 'Delete selected coupons';
$string['coupons_discount_error'] = 'Discount value cannot exceed 100%';
$string['coupons_generation_success'] = '{$a} coupon codes successfully generated.';
$string['coupons_ids'] = 'Coupon id(s) separated by (,)';
$string['coupons_length'] = 'Length';
$string['coupons_length_help'] = 'How many characters in a single coupon';
$string['coupons_maxperuser'] = 'Maximum usage / user';
$string['coupons_maxperuser_help'] = 'How many time a single user could use this coupon. (0 means max allowed usage)';
$string['coupons_maxusage'] = 'Maximum usage';
$string['coupons_maxusage_help'] = 'How many times the coupon could be used. (0 means unlimited)';
$string['coupons_number'] = 'Number of coupons';
$string['coupons_number_help'] = 'Please don\'t set a large number to not overload the database.';
$string['coupons_uploadcreated'] = '{$a} coupons has been successfully created.';
$string['coupons_uploaderrors'] = '{$a} coupons counters errors and not either updated or created.';
$string['coupons_uploadtotal'] = '{$a} of total coupons in the file.';
$string['coupons_uploadupdated'] = '{$a} coupons has been successfully updated.';
$string['coupons_valueerror'] = 'Value required';
$string['couponsall'] = 'Allow all types';
$string['couponsdeleted'] = '{$a} Coupons are deleted successfully';
$string['couponsdiscount'] = 'Discount coupons only';
$string['couponsfixed'] = 'Fixed amount coupons only';
$string['couponstype'] = 'Allow coupons';
$string['couponstype_help'] = 'Choose either to disable coupons, allow certain type or allow all.';
$string['courses_options'] = 'Courses';
$string['courses_options_help'] = 'Choose the courses to direct enrol the user using these coupons.';
$string['coursesrestriction'] = 'Another course restriction';
$string['coursesrestriction_help'] = 'Only users enrolled in more than or equal the required number from the selected courses could purchase this course.';
$string['coursesrestriction_num'] = 'Number of required courses';
$string['coursesrestriction_num_help'] = 'Select the minimum required courses that the user must be enrolled in to purchase this course using this instance.';
$string['courseswithdiscounts'] = 'Available courses with discounts';
$string['createdfrom'] = 'Created after';
$string['createdto'] = 'Created before';
$string['credit'] = 'Credit';
$string['credit_cost'] = 'Cost';
$string['credit_cost_help'] = 'The fee that will be deducted when enrolling.';
$string['credit_wallet_transformation_desc'] = 'Your credit has been transformed to your wallet.';
$string['csvfile'] = 'CSV file';
$string['csvfile_help'] = 'Only files with extension *.csv accepted';
$string['currency'] = 'Currency';
$string['currency_help'] = 'select the currency for payment for the course.';
$string['customcurrency'] = 'Custom currency';
$string['customcurrency_desc'] = 'Adding custom currency name for the wallet credit.<br>Note that this is not valid along with using actual payment gateway.<br>If left blank a Wallet Coins will be added to currencies list.';
$string['customcurrencycode'] = 'Custom currency code';
$string['customcurrencycode_desc'] = 'Adding a code for the custom currency, Something like USD but make sure that this code not already exist as an available currency code in the available payment gateways because it will not be overridden, but you can override Moodle Wallet Coin (MWC).';
$string['customwelcomemessage'] = 'Custom welcome message';
$string['customwelcomemessage_help'] = 'A custom welcome message may be added as plain text or Moodle-auto format, including HTML tags and multi-lang tags.

The following placeholders may be included in the message:

* Course name {$a->coursename}
* Link to user\'s profile page {$a->profileurl}
* User email {$a->email}
* User fullname {$a->fullname}';


$string['datefrom'] = 'From';
$string['dateto'] = 'To';
$string['debit'] = 'Debit';
$string['debitdesc_course'] = 'The user get charged by {$a->amount} for enrolment in course {$a->coursename}';
$string['debitdesc_instance'] = 'The user get charged by {$a->amount} for enrolment in course {$a->coursename} using the instance {$a->instance}';
$string['debitdesc_user'] = 'The user get charged by {$a->amount} by the user {$a->charger}';
$string['debitnegative'] = 'Allow negative in debit';
$string['defaultrole'] = 'Default role assignment';
$string['defaultrole_desc'] = 'Select role which should be assigned to users during enrolment';
$string['deleteselectedusers'] = 'Delete selected user enrollments';
$string['digits'] = 'Digits (numbers)';
$string['discount'] = 'discount';
$string['discount_behavior'] = 'Discount behavior';
$string['discount_behavior_desc'] = 'Users could be eligible for more than one discount or offer rule, choose how these discounts will be calculated (sequential, sum, max).<br>
* recursive: discount will calculate the cost of the course after discounts and then calculated again for the resulted value with the other discount rule.<br>
* sum: Will add all discounts together with a max (100%) and apply it.<br>
* max: Only apply the max eligible discount.';
$string['discount_behavior_max'] = 'Apply max discount';
$string['discount_behavior_sequential'] = 'Apply discounts sequentially';
$string['discount_behavior_sum'] = 'Apply the sum of discounts';
$string['discountcoupondisabled'] = 'Discount coupons disabled in this website.';
$string['discounts'] = 'Discounts';
$string['discountscopouns'] = 'Discounts & Coupons';
$string['discountscopouns_desc'] = 'Choose if you want to apply percentage discounts to users using a custom profile field. <br>
Also, applying coupons for this plugin.';


$string['editselectedusers'] = 'Edit selected user enrollments';
$string['enablerefund'] = 'Enable refund';
$string['enablerefund_desc'] = 'If not checked, all credits from now on will be nonrefundable, don\'t forget to make it clear to users in refund policy';
$string['endpoint_error'] = 'Endpoint return error';
$string['endpoint_incorrect'] = 'Incorrect response';
$string['enrol_type'] = 'Enrolment type';
$string['enrol_wallet'] = 'Enrol with wallet balance';
$string['enrolcoupon'] = 'Enrol coupon';
$string['enrolcoupondisabled'] = 'Enrolment coupons are disabled from this website.';
$string['enrolenddate'] = 'End date';
$string['enrolenddate_help'] = 'If enabled, users can enrol themselves until this date only.';
$string['enrolenddaterror'] = 'Enrolment end date cannot be earlier than start date';
$string['enrollmentupdated'] = 'enrollment(s) has been updated';
$string['enrolme'] = 'Enrol me';
$string['enrolpage_viewed_desc'] = 'The user with id {$a->userid} has viewed the enrol page of the course of id {$a->courseid}.';
$string['enrolpage_viewed_event'] = 'Enrol wallet enrol options viewed.';
$string['enrolperiod'] = 'Enrolment duration';
$string['enrolperiod_desc'] = 'Default length of time that the enrollment is valid. If set to zero, the enrollment duration will be unlimited by default.';
$string['enrolperiod_help'] = 'Length of time that the enrollment is valid, starting with the moment the user enrols themselves. If disabled, the enrollment duration will be unlimited.';
$string['enrolstartdate'] = 'Start date';
$string['enrolstartdate_help'] = 'If enabled, users can enrol themselves from this date onward only.';
$string['entervalue'] = 'Please enter a value.';
$string['equalsto'] = 'Equals to';
$string['event_award'] = 'Wallet award received';
$string['event_award_desc'] = 'User with id {$a->userid} get awarded by {$a->amount} due to getting a grade {$a->grade}% in course of id {$a->courseid}';
$string['event_cashback'] = 'Wallet cashback';
$string['event_cashback_desc'] = 'User with id {$a->userid} get a cashback in their wallet by amount({$a->amount}) due to paying {$a->original} to be enrolled in the course with id {$a->courseid}';
$string['event_coupon'] = 'Wallet Coupon used';
$string['event_coupon_desc'] = 'The coupon ( {$a->code} ) has been used by user of id {$a->userid}';
$string['event_newuser_gifted'] = 'New user gifted';
$string['event_newuser_gifted_desc'] = 'New user with id {$a->userid} gifted by {$a->amount} as a wallet balance.';
$string['event_transaction_credit_description'] = 'The wallet balance of the user with id {$a->relateduserid} charged by {$a->amount} {$a->refundable} by user of id {$a->userid} <br> more info: {$a->reason}';
$string['event_transaction_debit_description'] = 'The wallet balance of the user with id {$a->relateduserid} has been deducted by {$a->amount} by user of id {$a->userid} <br> more info: {$a->reason}';
$string['event_transactions'] = 'Wallet Transaction Event';
$string['expiredaction'] = 'Enrolment expiry action';
$string['expiredaction_help'] = 'Select action to carry out when user enrolment expires. Please note that some user data and settings are purged from course during course un-enrolment.';
$string['expirymessageenrolledbody'] = 'Dear {$a->user},
<br>
This is a notification that your enrollment in the course \'{$a->course}\' is due to expire on {$a->timeend}.
<br>
If you need help, please contact {$a->enroller}.';
$string['expirymessageenrolledsubject'] = 'Enrolment expiry notification';
$string['expirymessageenrollerbody'] = 'Enrolment in the course \'{$a->course}\' will expire within the next {$a->threshold} for the following users:
<br>
{$a->users}
To extend their enrolment, go to {$a->extendurl}';
$string['expirymessageenrollersubject'] = 'Enrolment expiry notification';


$string['filter_coupons'] = 'Filter Coupons';
$string['filter_transaction'] = 'Filter Transactions';
$string['fixedcoupondisabled'] = 'Fixed value coupons are disabled in this website.';
$string['fixedvaluecoupon'] = 'Fixed value coupon';
$string['freecourses'] = 'Free courses in this website';
$string['frontpageoffers'] = 'Offers page link in frontpage navigation';
$string['frontpageoffers_desc'] = 'Add a link to offers page in front page navigation';


$string['giftdesc'] = 'New user with id {$a->userid} at {$a->time} had a gift of amount {$a->amount} on his wallet.';
$string['giftvalue'] = 'New users gift value';
$string['giftvalue_help'] = 'The value which will be added to new users wallet.';
$string['greaterthan'] = 'Greater than';
$string['greaterthanorequal'] = 'Greater than or equal';


$string['insufficient_balance'] = 'You have insufficient wallet balance to enroll. {$a->cost_before} {$a->currency} are required, your balance is {$a->user_balance} {$a->currency}.';
$string['insufficient_balance_discount'] = 'You have insufficient wallet balance to enroll. <del>{$a->cost_before} {$a->currency}</del> {$a->cost_after} {$a->currency} are required, your balance is {$a->user_balance} {$a->currency}.';
$string['insufficientbalance'] = 'Sorry, you have insufficient balance for this operation. You need {$a->amount} while you have only {$a->balance}';
$string['invalidcoupon_operation'] = 'Invalid coupon operation, This coupon type may be disabled is this site or invalid configuration.';
$string['invalidpercentcoupon'] = 'Invalid value for percentage coupon, cannot exceed 100.';
$string['invalidvalue'] = 'Invalid Value, please enter a valid value.';
$string['inyourwallet'] = 'in your wallet.';


$string['longtimenosee'] = 'Un-enrol inactive after';
$string['longtimenosee_help'] = 'If users haven\'t accessed a course for a long time, then they are automatically unenrolled. This parameter specifies that time limit.';
$string['lowbalancenotice'] = 'Enable low balance notice';
$string['lowbalancenotification'] = 'Low Wallet Balance<br>Your balance is {$a}.';
$string['lowbalancenotify'] = 'Low Balance Notification.';
$string['lowbalancenotify_desc'] = 'If enabled and the user\'s balance is smaller than or equal the condition, a warning notifications appears in every page in the website.';
$string['lowerletters'] = 'lower case';


$string['mainbalance'] = 'Main balance: ';
$string['manualrefundboxlabel'] = 'Check the following box to display the top up options.';
$string['maxenrolled'] = 'Max enrolled users';
$string['maxenrolled_help'] = 'Specifies the maximum number of users that can enrol. 0 means no limit.';
$string['maxenrolledreached'] = 'Maximum number of users allowed to enrol was already reached.';
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
$string['messagesubject'] = 'Wallet Transactions ({$a})';
$string['migrate_enrollments_task'] = 'Migrate enrollments and users credits from enrol_credit to enrol_wallet';
$string['mintransfer'] = 'The minimum transfer amount is {$a}';
$string['mintransfer_config'] = 'Minimum allowed transfer';
$string['mintransfer_config_desc'] = 'Minimum allowed transfer amount, users cannot transfer balance to each others less than this amount.';
$string['mustselectchar'] = 'Must select at least one character type.';
$string['mywallet'] = 'My Wallet';


$string['negativebalance'] = 'Balance cannot be negative. Trying to deduct {$a->amount} from a balance of {$a->before}';
$string['newenrols'] = 'Allow new enrollments';
$string['newenrols_desc'] = 'Allow users to enrol into new courses by default.';
$string['newenrols_help'] = 'This setting determines whether a user can enrol into this course.';
$string['newusergift'] = 'New users gifts';
$string['newusergift_desc'] = 'Apply wallet gift for new user in moodle website';
$string['newusergift_enable'] = 'Enable new user gifts';
$string['newusergift_enable_help'] = 'If enabled, new users will have the gift you decided in their wallet.';
$string['noaccount'] = 'No Account';
$string['nochange'] = 'No change';
$string['nocost'] = 'this course has invalid cost';
$string['nocoupons'] = 'Disable coupons';
$string['nodiscountstoshow'] = 'No discounts to show.';
$string['nonrefundable'] = 'Nonrefundable';
$string['nonrefundable_transform_desc'] = 'Transform the transaction to non refundable due to expiring of refund period.';
$string['noreferraldata'] = 'No Past Referrals.';
$string['not_set'] = 'Not set';
$string['notequal'] = 'Not Equal to';
$string['noticecondition'] = 'Min balance for notify';
$string['noticecondition_desc'] = 'If the balance is smaller than or equal to this condition, a notification appears to the user.';
$string['notrefund'] = ' Nonrefundable (extra): ';


$string['offers'] = 'Offers';
$string['offers_ce_desc'] = '{$a->discount}% DISCOUNT if you are enrolled in {$a->condition} of these courses:<br> {$a->courses}';
$string['offers_course_enrol_based'] = 'Another course enrolment based offer';
$string['offers_desc'] = 'Offers and Free courses';
$string['offers_error_ce'] = 'Please select at least one course';
$string['offers_error_discountvalue'] = 'Invalid discount value.';
$string['offers_error_ncnumber'] = 'Please choose number of courses.';
$string['offers_error_otherccoursesexceed'] = 'This number exceeds the actual number of courses in this category';
$string['offers_error_othercnocourses'] = 'Please select number of courses.';
$string['offers_error_othercnotexist'] = 'Category not exist.';
$string['offers_error_pfnovalue'] = 'Please enter a value.';
$string['offers_error_pfselect'] = 'Please select a profile field';
$string['offers_error_timefrom'] = 'The time cannot exceed the "time to".';
$string['offers_error_timeto'] = 'Cannot select time in the past.';
$string['offers_location_based'] = 'Geo Location based offer';
$string['offers_nc_desc'] = '{$a->discount}% DISCOUNT if you are already enrolled in at least {$a->number} courses inside the category {$a->catname}';
$string['offers_number_courses_base'] = 'Number of courses based offer';
$string['offers_other_category_courses_based'] = 'number of courses in other category';
$string['offers_pf_desc'] = '{$a->discount}% DISCOUNT if your profile field {$a->field} {$a->op} "{$a->value}"';
$string['offers_pfop_contains'] = 'Contains';
$string['offers_pfop_doesnotcontain'] = 'Does not contain';
$string['offers_pfop_endswith'] = 'Ends with';
$string['offers_pfop_isempty'] = 'Is empty';
$string['offers_pfop_isequalto'] = 'Is equal to';
$string['offers_pfop_isnotempty'] = 'Is not empty';
$string['offers_pfop_startswith'] = 'Starts with';
$string['offers_please_select'] = 'Please select a type of offers to add';
$string['offers_profile_field'] = 'Profile Field';
$string['offers_profile_field_based'] = 'Profile field based offer';
$string['offers_time_based'] = 'Discount in a certain period of time';
$string['offers_time_desc'] = '{$a->discount}% DISCOUNT if you purchase this course in the period from {$a->from} to {$a->to}';
$string['offersnav'] = 'Add offers in primary navigation';
$string['offersnav_desc'] = 'or you can click here to add offers to primary navigation';
$string['othercourserestriction'] = 'Unable to enrol yourself in this course unless you are enrolled in at least {$a->number} of these courses {$a->courses}';


$string['paymentaccount'] = 'Payment account';
$string['paymentaccount_help'] = 'choose the payment account in which you will accept payments';
$string['paymentrequired'] = 'You can pay for this course directly using available payment methods';
$string['paymenttopup_desc'] = 'Payment to top up the wallet';
$string['percent_error'] = 'Percentage value should be between 0-100';
$string['percentcoupondisabled'] = 'Discount coupons disabled in this website.';
$string['percentdiscountcoupon'] = 'Percentage discount coupon';
$string['pluginconfig'] = 'Enrol wallet configuration';
$string['pluginname'] = 'Wallet enrolment';
$string['pluginname_desc'] = '';
$string['privacy:metadata'] = 'The Wallet enrolment plugin does not store any personal data.';
$string['privacy:metadata:enrol_wallet_awards'] = 'Hold information about the awards that the user gets.';
$string['privacy:metadata:enrol_wallet_awards:amount'] = 'The amount of the award.';
$string['privacy:metadata:enrol_wallet_awards:courseid'] = 'The id of the completed course.';
$string['privacy:metadata:enrol_wallet_awards:grade'] = 'The total grade that the user get in the course.';
$string['privacy:metadata:enrol_wallet_awards:userid'] = 'The id of the user.';
$string['privacy:metadata:enrol_wallet_balance'] = 'Hold details about the user\'s balance';
$string['privacy:metadata:enrol_wallet_balance:catbalance'] = 'All information about the category balances';
$string['privacy:metadata:enrol_wallet_balance:freegift'] = 'How much of the site balance obtained by gifts';
$string['privacy:metadata:enrol_wallet_balance:nonrefundable'] = 'The non-refundable amount in the site balance';
$string['privacy:metadata:enrol_wallet_balance:refundable'] = 'The refundable amount in the site balance';
$string['privacy:metadata:enrol_wallet_balance:userid'] = 'The user\'s id';
$string['privacy:metadata:enrol_wallet_cond_discount'] = 'Conditional discounts rules, this holds no personal information';
$string['privacy:metadata:enrol_wallet_cond_discount:usermodified'] = 'The id of the user modified the discount rule';
$string['privacy:metadata:enrol_wallet_coupons_usage'] = 'Hold information about the coupons used by each user.';
$string['privacy:metadata:enrol_wallet_coupons_usage:instanceid'] = 'The id of the instance at which the coupon used.';
$string['privacy:metadata:enrol_wallet_coupons_usage:userid'] = 'The id of the user.';
$string['privacy:metadata:enrol_wallet_hold_gift'] = 'Store information about referral gifts in hold.';
$string['privacy:metadata:enrol_wallet_hold_gift:amount'] = 'The amount of the referral gift.';
$string['privacy:metadata:enrol_wallet_hold_gift:courseid'] = 'The id of the course that the referred user enrolled in and get the gift.';
$string['privacy:metadata:enrol_wallet_hold_gift:referred'] = 'The username of the referred user.';
$string['privacy:metadata:enrol_wallet_hold_gift:referrer'] = 'The id of the referrer user.';
$string['privacy:metadata:enrol_wallet_items'] = 'Store some non-critical information before each payment contains the amount and the currency and what the user paying for.';
$string['privacy:metadata:enrol_wallet_items:cost'] = 'The cost of the item either a topping up fake item or enrol instance.';
$string['privacy:metadata:enrol_wallet_items:currency'] = 'The currency of the item.';
$string['privacy:metadata:enrol_wallet_items:instanceid'] = 'The enrol instance id if exists.';
$string['privacy:metadata:enrol_wallet_items:userid'] = 'The id of the user.';
$string['privacy:metadata:enrol_wallet_referral'] = 'Hold information about the referral program.';
$string['privacy:metadata:enrol_wallet_referral:code'] = 'A unique referral code.';
$string['privacy:metadata:enrol_wallet_referral:userid'] = 'The id of the user.';
$string['privacy:metadata:enrol_wallet_referral:users'] = 'Usernames of the users get referred by this user.';
$string['privacy:metadata:enrol_wallet_referral:usetimes'] = 'The number of times the user used the code.';
$string['privacy:metadata:enrol_wallet_transactions'] = 'Hold information about each wallet transaction.';
$string['privacy:metadata:enrol_wallet_transactions:amount'] = 'The amount of transaction.';
$string['privacy:metadata:enrol_wallet_transactions:balance'] = 'The balance after transaction.';
$string['privacy:metadata:enrol_wallet_transactions:balbefore'] = 'The balance before transaction.';
$string['privacy:metadata:enrol_wallet_transactions:description'] = 'The description of the transaction which contain a details about the course or the method by which the user topped up their wallet.';
$string['privacy:metadata:enrol_wallet_transactions:norefund'] = 'If this transaction amount is refundable of not.';
$string['privacy:metadata:enrol_wallet_transactions:type'] = 'The type of the transaction (debit or credit).';
$string['privacy:metadata:enrol_wallet_transactions:userid'] = 'The id of the user.';
$string['privacy:metadata:wordpress'] = 'Hold critical information about the user which is sent to the linked wordpress website to auto create a user account their.';
$string['privacy:metadata:wordpress:email'] = 'The email address.';
$string['privacy:metadata:wordpress:password'] = 'The raw password after the user get created or some random string if the user already exists.';
$string['privacy:metadata:wordpress:userid'] = 'The id of the user.';
$string['privacy:metadata:wordpress:username'] = 'The username.';
$string['profile_field_map'] = 'Profile field mapping';
$string['profile_field_map_help'] = 'Select the profile field that stores information about discounts in user profiles.';
$string['purchase'] = 'Purchase';
$string['purchasedescription'] = 'Enrolment in course {$a}';


$string['randomcoupons'] = 'Random Coupons';
$string['receiver'] = 'Receiver';
$string['referral'] = 'Referral';
$string['referral_amount'] = 'Referral Amount.';
$string['referral_amount_desc'] = 'The gift amount that both referring and referred users will receive in their wallets.';
$string['referral_amount_help'] = 'The gift amount that you and the new user will receive in the wallet.';
$string['referral_code'] = 'Referral Code';
$string['referral_code_help'] = 'Instate of the referral URL you can send this referral code instead and the new user enter it in the signup page.';
$string['referral_code_signup'] = '';
$string['referral_code_signup_help'] = 'If this is empty, enter a referral code to receive the referral gift.';
$string['referral_copied'] = 'Copied!';
$string['referral_copy_to_clipboard'] = 'Copy to clipboard';
$string['referral_data'] = 'Referral Data';
$string['referral_done'] = 'Gift granted';
$string['referral_enabled'] = 'Enable Referral Program';
$string['referral_exceeded'] = 'The referral code: {$a} exceeds it\'s max usage.';
$string['referral_gift'] = 'Due to referral code from user: {$a}';
$string['referral_header'] = 'Share link and earn credits in Wallet!';
$string['referral_hold'] = 'Gift in hold';
$string['referral_holdgift'] = 'You have a holding gift ({$a->amount}) due to use of referral code from {$a->name}, buy a course to get your gift.';
$string['referral_max'] = 'Maximum Referrals';
$string['referral_max_desc'] = 'The maximum times a user can receive referral gifts (0 means unlimited).';
$string['referral_noparents'] = 'Parents not allow to access referral program';
$string['referral_not_enabled'] = 'Referrals not enabled';
$string['referral_notexist'] = 'The code: \'{$a}\' not exist in the database.';
$string['referral_notyet'] = 'Not yet received!';
$string['referral_past'] = 'Past Referrals';
$string['referral_plugins'] = 'Enrol plugins';
$string['referral_plugins_desc'] = 'As users does not receive the referral gift until the referred user get enrolled in a course to make sure that this is an active user.<br/>Choose the enrolment methods allowed to make the users receive this gift';
$string['referral_program'] = 'Referrals Program';
$string['referral_program_desc'] = 'Existence users can refer new user to join this website and both receive a referral gift.';
$string['referral_remain'] = 'Remained Referrals.';
$string['referral_remain_help'] = 'Remained times available to receive the referral gift.';
$string['referral_share_body'] = 'Greetings;

You have been invited to join {$a->site} and get a free {$a->amount} as joining gift at your wallet.

Please use the following link to signup.

{$a->url}';
$string['referral_share_subject'] = 'Join {$a->site} and get a free credit on your wallet.';
$string['referral_site_desc'] = 'This site has a referral program, where you can send the referral code to your friend and when join us in at least one of our course both will get a gift on your wallets, for more information ';
$string['referral_subheader'] = 'Simple share link and when someone registers and purchases a course, you\'ll receive {$a} in your Wallet.';
$string['referral_timecreated'] = 'Signed up time';
$string['referral_timereleased'] = 'Gifted at:';
$string['referral_topup'] = 'Due to referral for user: {$a}.';
$string['referral_url'] = 'Referral URL';
$string['referral_url_help'] = 'Send this url to your friend to signup in this website and get a referral gift with the following amount in your wallet.';
$string['referral_user'] = 'Referrals';
$string['refundperiod'] = 'Refunding grace period';
$string['refundperiod_desc'] = 'The time after which user\'s cannot refunded for what they pay to top-up their wallets. 0 mean refund any time.';
$string['refundpolicy'] = 'Manual Refund Policy';
$string['refundpolicy_default'] = '<h5>Refund Policy</h5>
please note that:<br>
Payment to top-up your wallet cannot be refunded in the following cases:<br>
1- If this amount is due to new user gift, reward or a cashback.<br>
2- If the grace of refund period expired (14 days).<br>
3- Any amount already used in enrolment aren\'t refundable.<br>
When charging your wallet by any method means you agreed to this policy.';
$string['refundpolicy_help'] = 'Define custom refund policy for users to be aware of the condition of how they get back their money or not before topping up their wallet. This policy will be displayed to users in any form to recharge their wallet, or displaying their balance.';
$string['refunduponunenrol_desc'] = 'Refunded by amount of {$a->credit} after deduction un-enrol fee of {$a->fee} in the course: {$a->coursename}.';
$string['repurchase'] = 'Repurchase';
$string['repurchase_desc'] = 'Settings for repurchase the courses. If enabled, the users can repurchase the lectures again after the enrol date end.';
$string['repurchase_firstdis'] = 'First repurchase discount';
$string['repurchase_firstdis_desc'] = 'If specified, the users will get discount by this percentage value (0 - 100) for the second time they purchase the course.';
$string['repurchase_seconddis'] = 'Second repurchase discount';
$string['repurchase_seconddis_desc'] = 'For the third time the users purchase (second repurchase) the course, they will get discounted by this value. (should be between 0 - 100)';
$string['restrictionenabled'] = 'Enable restriction.';
$string['restrictionenabled_desc'] = 'If disabled, not restrictions will be checked.';
$string['restrictions'] = 'Enrolment restrictions';
$string['restrictions_desc'] = 'Like sections and course modules, now Wallet Enrollments offers an option to add restriction to the enrolment, not all availability plugins tested well, so you can choose from here what works fine and please report any error so we can improve this functionality.';
$string['role'] = 'Default assigned role';


$string['selectuser'] = 'Please select a user.';
$string['sendcoursewelcomemessage'] = 'Send course welcome message';
$string['sendcoursewelcomemessage_help'] = 'When a user enrols in the course, they may be sent a welcome message email. If sent from the course contact (by default the teacher), and more than one user has this role, the email is sent from the first user to be assigned the role.';
$string['sender'] = 'Sender';
$string['sendexpirynotificationstask'] = 'Wallet enrolment send expiry notifications task';
$string['sendpaymentbutton'] = 'direct payment';
$string['share_referral'] = 'Share with friends';
$string['showbalance'] = 'Show balance';
$string['showprice'] = 'Show price on enrol icon';
$string['showprice_desc'] = 'If selected the price of the course will be displayed over the enrollment icon in the course card.';
$string['singlecoupon'] = 'Single coupon';
$string['smallerthan'] = 'Smaller than';
$string['smallerthanorequal'] = 'Smaller than or equal';
$string['sourcemoodle'] = 'Internal moodle wallet';
$string['sourcewordpress'] = 'External Tera-wallet (WooWallet)';
$string['status'] = 'Allow existing enrollments';
$string['status_desc'] = 'Enable wallet enrolment method in new courses.';
$string['status_help'] = 'If enabled together with \'Allow new enrollments\' disabled, only users who enrolled previously can access the course. If disabled, this enrolment method is effectively disabled, since all existing enrollments are suspended and new users cannot enrol.';
$string['submit_coupongenerator'] = 'Create';
$string['syncenrolmentstask'] = 'Wallet enrolment synchronize enrollments task';


$string['tellermen'] = 'Teller men to be displayed';
$string['tellermen_desc'] = 'Users selected here will be public displayed on the topping up options to let users know who to ask to charging their wallets. (Select none will display nothing)';
$string['tellermen_display_guide'] = 'Need help charging your wallet? Ask one of our wallet administrator to charge your wallet manually or to assist you with the procedure.';
$string['tellermen_heading'] = 'Teller men';
$string['tellermen_heading_desc'] = 'All users with capabilities to credit or debit users wallets, this determine whom will be displayed on the topup form to let users know who to as for charging their wallets';
$string['topup'] = 'topup';
$string['topupafterdiscount'] = 'Actual payment';
$string['topupafterdiscount_help'] = 'The amount after discount.';
$string['topupbundle'] = 'Top up your wallet and pay only:';
$string['topupbycoupon'] = 'Using Coupons';
$string['topupbypayment'] = 'Using Payment';
$string['topupbytellerman'] = 'Manually from our side';
$string['topupbyvc'] = 'Using transfer to mobile wallet or instapay';
$string['topupcoupon_desc'] = 'by coupon code {$a}';
$string['topupoffers'] = 'Wallet topping up offers';
$string['topupoffers_desc'] = 'By topping up your wallet by one or greater than the given values you will be discounted by the specified amount and only have to pay less than this amount by this percentage value.';
$string['topuppayment_desc'] = 'Topping up the wallet by payment of {$a} using payment gateway.';
$string['topupvalue'] = 'TopUp value';
$string['topupvalue_help'] = 'Value to topup your wallet by using payment methods';
$string['transaction_perpage'] = 'Transactions per page';
$string['transaction_type'] = 'Type of transaction';
$string['transactions'] = 'Wallet transactions';
$string['transactions_details'] = 'More transaction details';
$string['transfer'] = 'Transfer balance to other user';
$string['transfer_desc'] = 'Enable or disable the ability of users to transfer balance to other users and determine the transfer fee per each operation.';
$string['transfer_enabled'] = 'Transfer to other user';
$string['transfer_enabled_desc'] = 'Enable or disable the ability for users to transfer balance to other users by email.';
$string['transfer_notenabled'] = 'User to user transfer isn\'t enabled in this site.';
$string['transferfee_desc'] = 'Note that there is a {$a->fee}% will be deducted from the {$a->from}.';
$string['transferfee_from'] = 'Deduct fees from:';
$string['transferfee_from_desc'] = 'Select how the fees get deducted.<br>
From sender: means that amount completely transferred and extra credit deducted from the sender.<br>
From receiver: means that the amount transferred to the receiver less than the sent amount by the fees.';
$string['transferop_desc'] = 'Transferring a net amount of {$a->amount} with a transfer fees {$a->fee} to {$a->receiver}';
$string['transferpage'] = 'Transfer balance';
$string['transferpercent'] = 'Transfer fees %';
$string['transferpercent_desc'] = 'In order to transfer some amount to another user a percentage fee will be deducted from the sender by default. Set it to 0 so there is no fee deducted.';
$string['transformation_credit_desc'] = 'Using enrol_credit? If you want, you can transform all users credits to their wallet also migrate all enrollments and instances to enrol_wallet instead. There is {$a->credit} credit enrol instances and {$a->enrol} enrollments to be migrated.';
$string['transformation_credit_done'] = 'Transformation and migration has been queued successfully and will run shortly, please check after a while for credits and enrollments.';
$string['transformation_credit_title'] = 'Transformation of credit to wallet';
$string['turn_not_refundable_task'] = 'Turn balance to non-refundable.';


$string['unenrol'] = 'Un-enrol user';
$string['unenrollimitafter'] = 'Cannot un-enrol self after:';
$string['unenrollimitafter_desc'] = 'Users cannot enrol themselves after this period from enrolment start date. 0 means unlimited.';
$string['unenrollimitbefor'] = 'Cannot un-enrol self before:';
$string['unenrollimitbefor_desc'] = 'Users cannot un-enrol themselves before this period from enrolment end date. 0 means no limit.';
$string['unenrolrefund'] = 'Refund upon un-enrol?';
$string['unenrolrefund_desc'] = 'If enabled, users will be refunded if they unenrolled from the course.';
$string['unenrolrefund_head'] = 'Refund users upon un-enrol.';
$string['unenrolrefund_head_desc'] = 'Return the paid fee of a course after un-enrol from the course.';
$string['unenrolrefundfee'] = 'Refund percentage fee';
$string['unenrolrefundfee_desc'] = 'Choose a percentage amount that will not be refunded after un-enrol as a fee.';
$string['unenrolrefundperiod'] = 'Refund upon un-enrol grace period';
$string['unenrolrefundperiod_desc'] = 'If the user unenrolled within this period from the enrol start date he will be refunded.';
$string['unenrolrefundpolicy'] = 'Un-enrol refunding policy';
$string['unenrolrefundpolicy_default'] = '<p dir="ltr" style="text-align: left;"><strong>Conditions for refunding upon un-enrol:</strong></p>
<p dir="ltr" style="text-align: left;">
If you are unenrolled from the course within {period} days from the start date you will be refunded with the amount you pay after deducting a {fee}% from the paid amount.
This amount will return to your wallet and can use it to enrol in other courses but not be able to be manually refunded.<br>
By pressing purchase means you have agreed to these conditions.
</p>';
$string['unenrolrefundpolicy_help'] = 'If refunding upon un-enrol enabled, this policy will be visible to users before enrol themselves to courses using wallet enrolment.<br>
placing {fee} in the policy will be replaced by the percentage fee.<br>
placing {period} will be replaced by the grace period in days.';
$string['unenrolself_notallowed'] = 'You are not un-enrol yourself from this course.';
$string['unenrolselfconfirm'] = 'Do you really want to un-enrol yourself from course "{$a}"?';
$string['unenrolselfenabled'] = 'Enable self un-enrol';
$string['unenrolselfenabled_desc'] = 'If enable, then users are allowed to un-enrol themselves from the course.';
$string['unenroluser'] = 'Do you really want to un-enrol "{$a->user}" from course "{$a->course}"?';
$string['unenrolusers'] = 'Un-enrol users';
$string['upload_coupons'] = 'Upload coupons';
$string['upload_coupons_help'] = 'Upload coupons in a csv file to bulk add or edit wallet coupons, the csv file should contain two primary columns:<br>
\'code\': The code of the coupon to be added or updated.<br>
\'value\': The value of the coupon and may be left 0 only if the type is (enrol).<br>
and optional columns:<br>
\'type\': the type of the coupon and only four values allowed (fixed, percent, category or enrol).<br>
\'courses\': only effective when the type is (enrol) and if should contain the short names of the required courses separated by / .<br>
\'category\': the id of the category at which the coupon is available for usage.<br>
\'maxusage\': The maximum usage of the coupon code.<br>
\'validfrom\': Time stamp of the date from which the coupon is available for usage.<br>
\'validto\': Timestamp of the date after which the coupon is not available.<br>
\'maxperuser\': Maximum time for a single user to use a coupon.<br>
\'id\': The id of the coupon in case of updating it.';
$string['upload_result'] = 'Result';
$string['uploadcsvfilerequired'] = 'Please upload the csv file.';
$string['upperletters'] = 'UPPER case';
$string['usedfrom'] = 'Used From';
$string['usedto'] = 'Used To';
$string['usernotexist'] = 'User not exist';
$string['usernotfound'] = 'No user found with this email {$a}';


$string['validfrom'] = 'Valid from';
$string['validto'] = 'Valid to';
$string['value'] = 'Amount per transaction';


$string['wallet:bulkedit'] = 'Bulk edit the enrollments in all courses';
$string['wallet:config'] = 'Configure wallet enrol instances';
$string['wallet:createcoupon'] = 'Creating wallet coupons';
$string['wallet:creditdebit'] = 'Credit and debit other users';
$string['wallet:deletecoupon'] = 'Deleting wallet coupon';
$string['wallet:downloadcoupon'] = 'Downloading wallet coupons';
$string['wallet:editcoupon'] = 'Edit coupons';
$string['wallet:enrolself'] = 'Purchase a course through enrol wallet instance';
$string['wallet:manage'] = 'Manage enrolled users';
$string['wallet:transaction'] = 'View the transaction table';
$string['wallet:transfer'] = 'Transfer wallet balance to another user';
$string['wallet:unenrol'] = 'Un-enrol users from course';
$string['wallet:unenrolself'] = 'Un-enrol self from the course';
$string['wallet:viewcoupon'] = 'View wallet coupons table';
$string['wallet:viewotherbalance'] = 'View the wallet balance of others';
$string['walletbulk'] = 'Wallet enrol instances bulk edit';
$string['walletcashback'] = 'Cashback for using wallet';
$string['walletcashback_desc'] = 'Enables the cashback program across the whole site';
$string['walletcredit'] = 'Wallet Credit';
$string['walletsource'] = 'Wallet source';
$string['walletsource_help'] = 'Choose either to connect the wallet with external woocommerce Tera wallet, or just use internal wallet in moodle';
$string['welcometocourse'] = 'Welcome to {$a}';
$string['welcometocoursetext'] = 'Welcome to {$a->coursename}!

If you have not done so already, you should edit your profile page so that we can learn more about you:

{$a->profileurl}';
$string['wordpress_secretkey'] = 'Secret Key';
$string['wordpress_secretkey_help'] = 'Admin must add any value here and the same value in moo-wallet setting in wordpress site.';
$string['wordpressloggins'] = 'Login/logout user from wordpress';
$string['wordpressloggins_desc'] = 'If enabled users are logged in and out from wordpress website when they logged in or out from moodle. (note that is one way only)';
$string['wordpressurl'] = 'Wordpress url';
$string['wordpressurl_desc'] = 'Wordpress url with woo-wallet (tera wallet) plugin on it';
$string['wrongemailformat'] = 'Wrong Email format.';


$string['youhavebalance'] = 'You have balance:';
