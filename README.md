# Wallet Enrollment for Moodle #
==========

by Mo. Farouk

This enrollment method allow the user to enrol themselves into courses using wallet credit of using payments gateways in addition to using coupons for direct enrollment, charging wallet or get discounts.

Admin can define either to totally use moodle for the wallet system and coupons, or use an existence Tera wallet (in a woocommerce).

## Features ##

1- Enrollment using wallet balance.

2- Charging wallet by manager (or users with capability) for other users.

3- Charging wallet by users using coupons or payments gateways.

4- Direct enrol using coupons or payment gateways.

5- Cashback student when purchase a course (optional).

6- Awarding students in a given course if they completed the course with high mark (optional).

7- Generate coupons with limiting the usage, and time.

8- Admin can switch to use woocommerce Tera wallet and woocommerce coupons.

9- Cohorts restrictions.

10- Another course enrollment restriction.

11- Display the transactions of wallet.

12- Bulk edit all enrollments in selected courses.

13- Bulk edit all wallet enrolment instances in selected courses.

14- Enable gifts as wallet credits upon creation of a user.

15- Discounts on courses for specific users depend on custom profile field.


## Wallet block ##

Also you can install block_wallet to display the balance for the user.

In addition the block give the ability for users to top-up their wallet using fixed value coupon.

Or using payment gateways.

And for managers it allow them to credit or debit any user.

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/enrol/wallet

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2023 Mohammad Farouk <phun.for.physics@gmail.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.