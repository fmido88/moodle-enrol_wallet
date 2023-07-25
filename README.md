# Wallet Enrollment for Moodle #
==========

## Wallet Enrollment ##
by Mo. Farouk

This plugin adds a wallet system to moodle, and users can enrol to courses using their credit.

This enrollment method allow the user to enrol themselves into courses using wallet credit or using payments gateways in addition to using coupons for direct enrollment, charging wallet or get discounts.

Admin can define either to totally use moodle for the wallet system and coupons, or use an existence Tera wallet (in a woocommerce).

So there is two options:
* Using Moodle as source for wallet: this allow all transaction to done internally on moodle site including the balance and coupon data.
* Using wordpress with woocommerce and Treawallet as wallet source: now you use the futures of woocommerce including coupons and wallet balance, also it creates users in wordpress automatically with the same email.

## Features : ##

1. Enrollment using wallet balance.
    * Manager creates a course and decide the cost for it.
    * Users can enrol themselves with their credit and the cost deducted from their wallets.

2. Charging wallet by manager (or users with capability) for other users.
    * By default site managers has the capability to add or deduct from a user's wallet balance.
    * Admin can change these capability "enrol/wallet:creditdebit" or grant it to any role.

3. Topping up wallet by users using coupons or payments gateways.
    * Users can charge their wallets by themselves using payments gateways available.
    * Also can use fixed value coupons to do that.
    * Users can review their balance from profile page and topping up their wallets.

4. Direct enrol using coupons or payment gateways.
    * In addition to enrol using wallet credit, user's can be direct enrol themselves using a coupon code it is a 100% discount or fixed with value greater than the course cost.
    * If the coupon with fixed value greater than the course cost, the remaining value will be added to the user's balance.
    * Also if there is payment gateway enabled they can enrol to the course by direct payment.
    * If the user already have a balance for example 20 USD, and the course cost 100 USD, so he will have to pay only 80 to get enrolled and the 20 will be deducted from his balance.

5. Cashback student when purchase a course (optional).
    * Admin can enable a cashback program, so when a user pay for a course, a percentage amount from what he paid will be return to his wallet.

6. Awarding students in a given course if they completed the course with high mark (optional).
    * Encourage your students by awarding them for completing a course.
    * In each course, the course creator can enable awarding program with a certain condition and amount.
    * For example set the condition for 80, means that only students completed the course with 80% or more of the full mark of the course will get awarded.
    * Setting up the value for 0.2 USD for awarding means that for every raw mark the student get above the condition will add 0.2 to his wallet (student grade is 900 out of max grade for the course 1000, this is a 100 grade above the condition, so 20 USD award added to his wallet).

7. Referral Program.
    * You can enable referral program in the website.
    * Users can send their referral code or url to new user and both gets a referral gift.
    * Referral gift is in hold state until the new user get enrolled in at least one course.
    * Admin can choose what is the enrolment method able to release the hold gift.
    * Times users can use their referral code can be limited by a maximum value.

8. Generate coupons with limiting the usage, and time.
    * If you use moodle as a wallet source, you can add a coupon manually or generate any number you need of coupons.
    * Coupons could be of type fixed of percent.
    * Determine the interval of time at which the coupon could be used or just anytime.
    * Determine the maximum usage for each coupon.
    * Only users with capability "enrol/wallet:createcoupon" could generate coupons and with "enrol/wallet:deletecoupon" can delete coupons.
    * Editing coupons is not an option now but I'll try to add it in the future.
    * You can choose the length of random coupon, the type of characters in the generated random coupon (lowercase, uppercase and digits).

9. Admin can switch to use woocommerce Tera wallet and woocommerce coupons.
    * If you use woocommerce as a wallet source, so you can't use moodle coupons.
    * Instate you use woocommerce coupons so you can generate and create it their.

10. Cohorts restrictions.
    * In each enrol_wallet instance, course creator can decide if only users in a certain course can enrol (using any of previous methods) or not.

11. Another course enrollment restriction.
    * Course creator can decide to restrict using wallet enrollment so only users enrolled in a set of other selected courses can enrol themselves in this course.
    * Also the creator can select a set of 10 courses in example, and set number of of required courses 5 for example, so the user must be enrolled at least in five courses from the 10 selected.

12. Configure self-unenrol option.
    * Enable or disable the ability for users to unenrol themselves.
    * Can enable with period condition.

13. Display the transactions of wallet.
    * Users with capability "enrol/wallet:transactions" can see all wallet transaction can review any transaction in the website.
    * Other user can see only their own transactions.
    * Using Wallet Balance block plugin to allow users to see their balance anywhere and recharge it by payment.

14. Optional ability to transfer credit between users.
    * Admin can enable the option for users to transfer credit to each other by email.
    * The capability enrol/wallet:transfer is set to all users by default, can be altered by admin.
    * A transfer fee can be set.
    * Admin can configure if the fee deducted from the sender balance or from the receiver balance.

15. Bulk edit all enrollments in selected courses.
    * Their is an option for admins to edit all users enrollments in selected courses in bulk from a central place.

16. Bulk edit all wallet enrolment instances in selected courses.
    * Admins can edit all wallet enrolment instances in all or selected courses from a central place.

17. Enable gifts as wallet credits upon creation of a user.
    * From settings, admins can enable new user gift program.
    * This gives new users a balance in their wallet as a gift for joining the website.

18. Discounts on courses for specific users depend on custom profile field.
    * Want to give certain student a discount 50%? Or another student want to give him courses for free?
    * If yes, create a custom profile field and make it locked, also invisible if needed.
    * In wallet enrolment setting, select this field as a discount field.
    * Users with 20 in this field will get 20% discount in all courses.
    * Users with 100 or 'free' in this field will get courses for free.

19. Conditional discounts.
    * In the latest version a conditional discount rules added.
    * Admin can enable or disable conditional discounts.
    * Conditional discount applied for charging the wallet only.
    * Add a rule which is an amount for charging wallet, if the user charge their wallet with a value exceed the rule, the discount applied.
    * Decide the percentage amount for this discount.
    * Discount appear on confirmation when user top-up their wallet using payment along with the refund policy (if admin left it blank nothing appears).
    * Also discount appear to users with capability 'creditdebit' as a final calculated value when they try to recharge other user wallet.

20. Manual refunding and Policy.
    * Admins can customize a refunding policy to display it to users.
    * Users can see how much of their wallet balance is refundable.
    * All gifts, cashback, credit from discount and awards are not refundable.
    * Admin can set a grace time period for refunding, after this time is over the balance turn to be nonrefundable (14 days by default).
    * Setting grace period to 0 means that is no grace period and no transformations for the balance.
    * If Admin unchecked 'enable refund' so all balance now on will be nonrefundable.

21. Auto refund upon unenrol.
    * Enable or disable refunding users by unenrol.
    * Conditions like grace period could be configured.
    * Unenrol deduction fee can be configured.

22. Notifications for every transaction.
    * Users gets a notifications for every debit or credit type of transaction in their wallet.
    * Admins can change the way users get notify from messages setting.

23. Low balance notice.
    * Display a warning notice to the user in case of low balance.
    * This is optional and admin can decide the minimum balance to call it a low balance.

24. Events.
    * Almost any action in this plugin triggers its own event.
    * Transactions events: with every credit or debit action to users wallet.
    * Using coupon: if a coupon used it triggers its own event.
    * Cashback: if a user receive a cashback.
    * Award: If a user get a high mark in a course and receive award for it.
    * Gift: If a new user get gifted.
These events helps administrators or managers to track the wallet workflow.

25. Enhanced security.
    * In the latest version, connection to wordpress is secure using encrypted data.
    * Also using shared secret key which the admin must match those in moodle and wordpress in order for secure connection.

26. Login and logout to wordpress.
    * When a user login or logout from moodle website, automatically logged in or out from wordpress website.
    * Admin can disable this option of course.

27. Auto create wordpress user.
    * only active if the wallet source is wordpress.
    * Creating new user in wordpress website with same username and password.
    * Update the password automatically when updated in moodle.
    * Updating another user data not available yet in current version.

# Wallet block #

Also you can install block_wallet to display the balance for the user.

In addition the block give the ability for users to top-up their wallet using fixed value coupon or using payment gateways.
And a link to see their wallet transaction history.

And for managers it allow them to credit or debit any user.

# Moo-Wallet #

If you welling to use existence Tera-wallet in woocommerce, you can download a wordpress plugin (moo-wallet) from here:
https://github.com/fmido88/moo-wallet/archive/refs/heads/main.zip

This wordpress lite plugin connects enrol_wallet plugin in moodle with wordpress site.
Install it in wordpress and enable it.
Make sure that you have Tera wallet plugin in wordpress.
Now you can use woocommerce coupons and tera wallet, users can charge their wallets from woocommerce or from moodle.

# Installing via uploaded ZIP file #

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

# Installing manually #

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/enrol/wallet

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

# License #

2023 Mohammad Farouk <phun.for.physics@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>;.
