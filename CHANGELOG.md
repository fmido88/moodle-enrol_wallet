# Wallet Enrollment for Moodle #
==========
## V 4.0.5 ##
- Add missing capability check for generating coupons page.
- Use curl class instate of curl_init() when requesting data from wordpress.
- Some lang string fixes.

## V 4.0.0 ##
- Many fixes.
- Improve the validation of forms.
- Add new types of coupons.
- Add coupons usage page.
- Add repurchase option.
- Improve filtrations of transaction table and coupons tables.
- After creating coupons, redirect to coupons table with only the newly created coupons displayed.

## V 3.1.0 ##
- Add Referral program.
- Fix some bugs.
- Fix typo.

## V 2.5.0 ##
- Display a warning in case of low balance.
- Add multiple rules for conditional discount.
- Fix bugs.

## V 2.0.0 ##
- Add option to display a notice for low balance.
- Enhance user creation in wordpress.
- Fix minor bugs.

## V 1.8.2 ##
- Fix the the table name in get_record().
- Fix the functionality of the function get_unenrolself_link().
- Add tests for get_unenrolself_link() and unenrol_user() to make sure refunding is working well.

## V 1.8.1 ##
- Some fixes.
- Apply  coupons now compatible with availability_wallet.

## V 1.8.0 ##
- Add more useful links to homepage navigation.
- Adding the ability to transfer credit to other users.
- Adding option to enable or disable self unenrol with conditions.
- Adding option to refund users upon unenrol.

## V 1.7.0 ##
- Add ability for editing coupons.
- Hide cheaper instances or non available enrol wallet instance in the same course.
- Fix and test logging in and out to wordpress.
- Bug fixes and enhancement.

## V 1.6.7 ##
- Fix and enhance coupons and transaction pages.

## V 1.6.6 ##
- Rebuilt the restrictions according to other course enrolment so that to check minimum number from multiple selected courses to check.
- Hide instance in case of existence of a cheaper one in the same course.

## V 1.6.5 ##
- Fix the functionality of logging in and logging out from wordpress website.
- Fix bug in bulkedit page

## V 1.6.4 ##
- Adding some translatable language strings.
- some code improvement.

## V 1.6.3 ##
- Some code improvement
- Enable plugin upon installation

## V 1.6.2 ##
- Add automate PHPUnit test for external service.
- Add cost to external services info.
- Wallet balance now displayed in users profiles.
- Fix task to automatically convert balance to nonrefundable after grace period is over.

## V 1.6.1 ##
- Enhance the calculation of conditional discount.
- Slight improvements.

## V 1.6 ##
- Enhance the security during connection with wordpress website.
- Add conditional discounts rules
- Add refunding policy and expiry date
- Add the ability to login or out from wordpress when the user logged in or out from moodle
- Add events for transaction, awards, gifts, using coupons and cashback.

## V 1.5.1 ##
- Implant payment privacy API.
- Prevent generation or use of percent discount coupons with value grater that 100.
- Fix course discounted cost with negative value.

## V 1.5 ##
- Adding PHPUnit tests for the major functions.
- Fixing a payment service provider bug.

## V 1.4 ##
- Migrate function to different classes.
- Fix observer class.
- Make moodle source a default setting.
- Adding filtration for to transactions page.
- Fix issues with applying coupons.

## V 1.3 ##
- Adding discount coupons.
- Adding page for bulk editing users enrollments.
- Adding page for bulk editing wallet enrolment instances.
- Adding page to charge user with another user with capability.
