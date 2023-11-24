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

$string['agreepolicy_intro'] = 'لتنفيذ أي عملية تعبئة للمحفظة، فهذا يعني أنك <strong>قرأت ووافقت</strong> على سياسة استرداد الأموال اليدوية.<br/>
انقر على الرابط أدناه لقراءة السياسة.<br/>';
$string['agreepolicy_label'] = 'أوافق على سياسة استرداد الأموال اليدوية.';
$string['alreadyenroled'] = 'لقد قمت بالتسجيل بالفعل في هذه المقرر، ربما انتهى وقتك أو تم تعليقك <br> اتصل بالمساعد الفني أو TS لمزيد من المساعدة';
$string['allusers'] = 'المعاملات لجميع المستخدمين المحددين';
$string['allowmultiple'] = 'عدد الحالات المسموح بها';
$string['allowmultiple_help'] = 'حدد عدد المثيلات المسموح بها في المقرر الدراسي الواحد، 0 يعني أنه غير محدود.';
$string['applycoupon'] = 'تطبيق الكوبون';
$string['applycoupon_help'] = 'قم بتطبيق رمز الكوبون للحصول على خصم أو الحصول على قيمة ثابتة لشحن محفظتك.<br>'.
'إذا كانت قيمة الكوبون ثابتة وأكبر من رسوم المقرر، فسيتم تسجيلك.';
$string['amount'] = 'المبلغ';
$string['awards'] = 'برنامج الجوائز';
$string['awards_help'] = 'تمكين أو تعطيل برنامج الجوائز في هذه المقرر';
$string['awardcreteria'] = 'شرط الحصول على الجائزة';
$string['awardcreteria_help'] = 'تعمل الجوائز  عندما يكمل الطالب المقرر. ما هي نسبة العلامة الكاملة التي يحصل عليها الطالب إذا تجاوزها؟';
$string['awardvalue'] = 'قيمة الجائزة';
$string['awardvalue_help'] = 'كم حصل الطالب على كل درجة فوق الشرط؟';
$string['awardingdesc'] = 'يحصل المستخدم على جائزة بقيمة {$a->amount} في المقرر {$a->courseshortname} للحصول على {$a->usergrade} من {$a->maxgrade}';
$string['awardsalter'] = 'تغيير الجوائز';
$string['awardsalter_help'] = 'تغيير حالة برنامج الجوائز';
$string['awardssite'] = 'تمكين الجوائز';
$string['awardssite_help'] = 'تمكين قدرة منشئ المقرر على تعيين الجوائز للمقرر.';
$string['availablebalance'] = 'الرصيد المتاح:';

$string['balance_after'] = 'الرصيد بعد';
$string['balance_before'] = 'الرصيد قبل';
$string['borrow'] = 'رصيد الاقتراض';
$string['borrow_desc'] = 'قم بتمكين وتعيين الشرط لجعل المستخدمين الموثوقين قادرين على التسجيل في المقررات دون أن يكون لديهم رصيد كافٍ، ويصبح رصيدهم سلبيًا ويتعين عليهم إعادة شحن المحفظة لدفع ثمنها لاحقًا.';
$string['borrow_enable'] = 'تمكين الاقتراض';
$string['borrow_enable_help'] = 'في حالة التمكين، سيتمكن الطلاب المستوفون للشروط من التسجيل في المقررات حتى مع وجود رصيد غير كافي.';
$string['borrow_trans'] = 'معاملات الاقتراض';
$string['borrow_trans_help'] = 'عدد المعاملات الائتمانية المطلوبة خلال فترة زمنية معينة، لذا سيكون المستخدم مؤهلاً لاقتراض الرصيد.';
$string['borrow_period'] = 'فترة المعاملات الخاصة بالاقتراض.';
$string['borrow_period_help'] = 'الفترة التي يقوم فيها المستخدم بإجراء العدد السابق من المعاملات ليكون مؤهلاً للاقتراض.';
$string['bulkfolder'] = 'إضافي عن طريق تسجيلات المحفظة';
$string['bulkeditor'] = 'تحرير جماعي للتسجيلات';
$string['bulkeditor_head'] = 'تحرير التسجيل المجمع (لجميع المستخدمين في المقررات الدراسية المحددة)';
$string['bulk_instancestitle'] = 'تحرير مثيلات التسجيل المجمع للمحفظة';
$string['bulk_instanceshead'] = 'تحرير التسجيل المجمع (لجميع المقررات الدراسية)';
$string['bulk_instancesno'] = 'لم يتم إنشاء أو تحديث أي مثيلات';
$string['bulk_instancesyes'] = 'تم تحديث نسخ التسجيل {$a->updated} وتم إنشاء {$a->created}.';

$string['cashbackdesc'] = 'تمت الإضافة عن طريق الاسترداد النقدي بسبب التسجيل في {$a}';
$string['cashbackenable'] = 'تمكين الاسترداد النقدي';
$string['cashbackenable_desc'] = 'عندما يتم تمكين هذا، سيحصل الطالب على نسبة مئوية من مبلغ الاسترداد النقدي في كل مرة يستخدم فيها المحفظة لشراء مقرر تدريبية.';
$string['cashbackpercent'] = 'النسبة المئوية لمبلغ الاسترداد النقدي';
$string['cashbackpercent_help'] = 'النسبة المئوية للمبلغ المسترد للمحفظة من المبلغ المدفوع بواسطة رصيد المحفظة.';
$string['canntenrol'] = 'التسجيل معطل أو غير نشط';
$string['canntenrolearly'] = 'لا يمكنك التسجيل بعد؛ يبدأ التسجيل في {$a}.';
$string['canntenrollate'] = 'لا يمكنك التسجيل بعد الآن، منذ انتهاء التسجيل في {$a}.';
$string['cannotdeductbalance'] = 'لا يمكن خصم الرصيد بسبب حدوث خطأ. الرجاء المحاولة مرة أخرى وإذا كانت المشكلة لا تزال موجودة، فاتصل بدعم الموقع.';
$string['categorycoupon'] = 'كوبون الفئة';
$string['category_options'] = 'الفئة';
$string['category_options_help'] = 'مثل الكوبونات الثابتة إلا أنها محظورة للاستخدام إلا في الفئة المختارة';
$string['checkout'] = 'سيتم خصم {$a->credit_cost} {$a->currency} من رصيدك البالغ {$a->user_balance} {$a->currency}.';
$string['checkout_borrow'] = '{$a->credit_cost} {$a->currency} مطلوب للتسجيل، سيتم خصم رصيدك {$a->user_balance} {$a->currency} واقتراض {$ أ->استعارة}.';
$string['checkout_discounted'] = '<del>{$a->credit_cost} {$a->currency</del> {$a->after_discount} {$a->currency} سيتم خصمها من رصيدك من {$a->user_balance} {$a->currency}.';
$string['checkout_borrow_discounted'] = '<del>{$a->credit_cost} {$a->currency</del> {$a->after_discount} {$a->currency} المطلوبة للتسجيل، رصيدك سيتم خصم {$a->user_balance} {$a->currency} واقتراض {$a->borrow}.';
$string['characters'] = 'الأحرف في الكود.';
$string['characters_help'] = 'اختر نوع الأحرف في الرموز التي تم إنشاؤها.';
$string['charger_novalue'] = 'لم يتم إدخال قيمة صالحة.';
$string['charger_nouser'] = 'لم يتم تحديد مستخدم';
$string['charger_credit_desc'] = 'الشحن يدوياً بمقدار {$a}';
$string['charger_debit_desc'] = '(الخصم يدويًا بمقدار {$a})';
$string['charger_debit_err'] = 'القيمة ({$a->value}) أكبر من رصيد المستخدم ({$a->before}) ';
$string['charger_invalid_operation'] = 'عملية غير صالحة.';
$string['chargingoptions'] = 'شحن محفظة المستخدم ';
$string['chargingoperation'] = 'العملية';
$string['chargingvalue'] = 'القيمة';
$string['charging_value'] = 'قيمة الشحن: ';
$string['clear_filter'] = 'مسح عوامل التصفية';
$string['cohortnonmemberinfo'] = 'فقط أعضاء المجموعة \' {$a} \' يمكنهم التسجيل.';
$string['cohortonly'] = 'أعضاء المجموعة النموذجية فقط';
$string['cohortonly_help'] = 'قد يقتصر التسجيل على أعضاء مجموعة محددة فقط. لاحظ أن تغيير هذا الإعداد ليس له أي تأثير على التسجيلات الحالية.';
$string['courses_options'] = 'المقررات';
$string['courses_options_help'] = 'اختر المقررات لتسجيل المستخدم مباشرة باستخدام هذه الكوبونات.';
$string['conditionaldiscount'] = 'الخصم المشروط';
$string['conditionaldiscount_link_desc'] = 'إضافة أو تعديل أو حذف قواعد الخصم المشروط';
$string['conditionaldiscount_apply'] = 'الخصومات المشروطة';
$string['conditionaldiscount_apply_help'] = 'تمكين الخصم المشروط للموقع بأكمله';
$string['condition'] = 'شرط';
$string['conditionaldiscount_condition'] = 'شروط تطبيق الخصم';
$string[' conditionaldiscount_condition_help'] = ' لن يتم تطبيق الخصومات إلا إذا تم تحصيل رسوم من محفظة المستخدم بأكثر من أو تساوي القيمة المدخلة هنا.';
$string['conditionaldiscount_desc'] = 'شحن المحفظة بسبب الخصومات المشروطة بمقدار {$a->rest} لشحن المحفظة لأكثر من {$a->condition}';
$string['conditionaldiscount_percent'] = 'النسبة المئوية لمبلغ الخصم';
$string['conditionaldiscount_percent_help'] = 'يتم إضافة هذه النسبة إلى المستخدمين. (يطبق فقط لشحن المحفظة)<br>
ملاحظة مهمة: إذا اختار المستخدم تعبئة المحفظة بمقدار 400 وتم ضبط نسبة الخصم على 15%، فسيدفع المستخدم 340 فقط ثم سيتم إضافة 60 تلقائيًا.';
$string['conditionaldiscount_percentage'] = 'النسبة المئوية';
$string['conditionaldiscount_timeto'] = 'متاح حتى';
$string['conditionaldiscount_timeto_help'] = 'متاح حتى التاريخ المحدد، وبعد ذلك التاريخ لم يعد الشرط قابلاً للتطبيق.';
$string['conditionaldiscount_timefrom'] = 'متاح بعد';
$string['conditionaldiscount_timefrom_help'] = 'متاح بعد التاريخ المحدد، وقبله لا يكون الشرط قابلاً للتطبيق .';
$string['confirmbulkdeleteenrolment'] = 'هل أنت متأكد من رغبتك في حذف تسجيلات المستخدم هذه؟';
$string['confirmedit'] = 'تأكيد التحرير';
$string['confirmdeletecoupon'] = 'هل أنت متأكد من رغبتك في حذف الكوبونات ذات المعرف {$a}. هذه العملية لا رجعة فيها.';
$string['confirm Payment'] = 'تأكيد الدفع بقيمة {$a->value} {$a->currency}. لاحظ أن: الضغط على نعم يعني موافقتك على سياسة استرداد الأموال.<br> {$a->policy}';
$string['confirm Payment_discounted'] = 'تأكيد دفع <del>{$a->قبل} {$a->currency</del> {$a->value} {$a->currency}. لاحظ أن: الضغط على نعم يعني موافقتك على سياسة استرداد الأموال.<br> {$a->policy}';
$string['coupons'] = 'الكوبونات';
$string['coupon_applydiscount'] = 'لقد حصلت الآن على خصم بنسبة {$a}%';
$string['coupon_applyerror'] = 'خطأ في رمز الكوبون غير صالح: <br> {$a}';
$string['coupon_applyfilter'] = 'تطبيق الفلتر';
$string['coupon_applyfixed'] = 'تم تطبيق رمز الكوبون بنجاح بقيمة {$a->value} {$a->currency}.';
$string['coupon_applynocourse'] = 'حدث خطأ أثناء تطبيق الكوبون، لم يتم العثور على المقرر.';
$string['coupon_applynohere'] = 'لا يمكن تطبيق كوبون الخصم هنا.';
$string['coupon_cat_notكافي'] = 'قيمة هذه الكوبون غير كافية لاستخدامها في هذه المقرر.';
$string['coupon_categoryapplied'] = 'تم تطبيق الكوبون.';
$string['coupon_categoryfail'] = 'عذرًا، يمكن تطبيق هذه الكوبون فقط في هذه الفئة: {$a}';
$string['coupons_category_error'] = 'يجب تحديد الفئة';
$string['coupon_code'] = 'رمز الكوبون';
$string['coupon_code_applied'] = 'الكوبون {$a} مطبق.';
$string['coupon_code_error'] = 'الرجاء إدخال الرمز أو تحديد طريقة عشوائية';
$string['coupon_code_help'] = 'أدخل رمز الكوبون الذي تريده.';
$string['coupons_courseserror'] = 'يجب تحديد مقرر واحدة على الأقل.';
$string['coupons_discount_error'] = 'لا يمكن أن تتجاوز قيمة الخصم 100%';
$string['coupon_edit_title'] = 'تحرير الكوبون';
$string['coupon_edit_heading'] = 'تحرير الكوبون';
$string['coupons_valueerror'] = 'القيمة المطلوبة';
$string['coupon_enrolapplied'] = 'تم تطبيق الكوبون';
$string['coupon_enrolerror'] = 'عذرًا، يمكن تطبيق هذه الكوبون فقط في هذه المقررات:<br>{$a}';
$string['coupon_exceedusage'] = 'هذه الكوبون تتجاوز الحد الأقصى للاستخدام';
$string['coupon_expired'] = 'انتهت صلاحية هذه الكوبون';
$string['coupon_generation'] = 'إنشاء كوبونات';
$string['coupon_generation_title'] = 'إنشاء كوبونات';
$string['coupon_generation_heading'] = 'أضف كوبونات جديدة';
$string['coupon_generation_method'] = 'طريقة الإنشاء';
$string['coupon_generation_method_help'] = 'اختر ما إذا كنت تريد إنشاء كوبون واحدة برمز من اختيارك أو إنشاء عدد من الكوبونات العشوائية';
$string['coupons_generation_success'] = 'تم إنشاء أكواد الكوبون {$a} بنجاح.';
$string['coupon_generator_nonumber'] = 'لم يتم تحديد عدد الكوبونات.';
$string['coupon_generator_error'] = 'حدث خطأ أثناء محاولة إنشاء الكوبونات.';
$string['coupon_generator_peruser_gt_max'] = 'أقصى استخدام مسموح به لكل مستخدم يجب ألا يتجاوز الحد الأقصى لاستخدام الكوبون.';
$string['coupon_invalidtype'] = 'نوع الكوبون غير صالح، فقط ثابت، النسبة المئوية، التسجيل والفئة مسموح بها.';
$string['coupon_invalidid'] = 'سجل الكوبون بهذا المعرف غير موجود أو أنه لا يطابق الرمز.';
$string['coupons_length'] = 'الطول';
$string['coupons_length_help'] = 'كم عدد الأحرف في الكوبون الواحدة';
$string['coupon_novalue'] = 'تعود الكوبون بدون قيمة، من المحتمل أن رمز الكوبون غير موجود';
$string['coupon_notexist'] = 'هذه الكوبون غير موجودة';
$string['coupon_notvalidyet'] = 'هذه الكوبون غير صالحة حتى {$a}';
$string['coupon_nocode'] = 'لا يوجد كود.';
$string['coupons_number'] = 'عدد الكوبونات';
$string['coupons_number_help'] = 'الرجاء عدم تعيين عدد كبير حتى لا يتم تحميل قاعدة البيانات بشكل زائد.';
$string['coupons_maxusage'] = 'الحد الأقصى للاستخدام';
$string['coupons_maxusage_help'] = 'كم مرة يمكن استخدام الكوبون. (0 يعني غير محدود)';
$string['coupons_maxperuser'] = 'الحد الأقصى للاستخدام / المستخدم';
$string['coupons_maxperuser_help'] = 'كم مرة يمكن لمستخدم واحد استخدام هذه الكوبون. (0 يعني الحد الأقصى للاستخدام المسموح به)';
$string['coupon_perpage'] = 'كوبونات لكل صفحة';
$string['coupon_resetusetime'] = 'إعادة الضبط المستخدمة';
$string['coupon_resetusetime_help'] = 'أعد ضبط استخدام الكوبون على الصفر.';
$string['coupon_t_code'] = 'الرمز';
$string['coupon_t_value'] = 'القيمة';
$string['coupon_t_type'] = 'النوع';
$string['coupon_t_usage'] = 'الاستخدام';
$string['coupon_t_lastuse'] = 'آخر استخدام';
$string['coupon_t_timecreated'] = 'وقت الإنشاء';
$string['coupon_table'] = 'عرض كوبونات المحفظة';
$string['coupon_type'] = 'نوع الكوبونات';
$string['coupon_type_help'] = 'اختر نوع الكوبونات المراد إنشاؤها.<br>
كوبونات ذات قيمة ثابتة: تستخدم في أي مكان وتقوم بتعبئة محفظة المستخدم بقيمتها ، وفي حالة استخدامها في صفحة التسجيل، سيتم تسجيل المستخدم في المقرر إذا كانت كافية.<br>
كوبونات خصم النسبة المئوية: تستخدم للحصول على نسبة خصم على تكلفة المقرر.
كوبونات الفئة: نفس الكوبونات الثابتة باستثناء أنه لا يمكن استخدامها في أي مكان، فقط لتسجيل المستخدم في الفئة المحددة.
كوبونات المقررات: هذه الكوبونات ليس لها أي قيمة، فهي تستخدم لتسجيل المستخدمين في إحدى المقررات المختارة.';
$string['coupon_value'] = 'قيمة الكوبون';
$string['coupon_value_help'] = 'قيمة الكوبون، قيمة ثابتة أو نسبة مخصومة.';
$string['coupon_usetimes'] = 'أوقات الاستخدام';
$string['coupon_usage'] = 'سجل استخدام الكوبونات';
$string['coupon_update_failed'] = 'فشل تحديث الكوبون.';
$string['coupon_update_success'] = 'تم تحديث الكوبون بنجاح.';
$string['coupons_uploadtotal'] = '{$a} من إجمالي الكوبونات في الملف.';
$string['coupons_uploadcreated'] = 'تم إنشاء كوبونات {$a} بنجاح.';
$string['coupons_uploadupdated'] = 'تم تحديث الكوبون {$a} بنجاح.';
$string['coupons_uploaderrors'] = 'تقوم الكوبونات {$a} بحساب الأخطاء ولم يتم تحديثها أو إنشاؤها.';
$string['couponsall'] = 'السماح لجميع الأنواع';
$string['couponsdeleted'] = 'تم حذف الكوبونات {$a} بنجاح';
$string['couponsdiscount'] = 'كوبونات الخصم فقط';
$string['couponsfixed'] = 'كوبونات ذات مبالغ ثابتة فقط';
$string['couponstype'] = 'السماح بالكوبونات';
$string['couponstype_help'] = 'اختر إما تعطيل الكوبونات أو السماح بنوع معين أو السماح للجميع.';
$string['coursesrestriction'] = 'قيد آخر للمقرر';
$string['coursesrestriction_help'] = 'فقط المستخدمين المسجلين في أكثر من أو يساوي العدد المطلوب من المقررات المحددة يمكنهم شراء هذه المقرر.';
$string['coursesrestriction_num'] = 'عدد المقررات المطلوبة';
$string['coursesrestriction_num_help'] = 'اختر الحد الأدنى من المقررات المطلوبة التي يجب على المستخدم تسجيلها لشراء هذه المقرر باستخدام هذا المثال.';
$string['createdfrom'] = 'تم الإنشاء بعد';
$string['createdto'] = 'تم الإنشاء من قبل';
$string['credit_cost'] = 'التكلفة';
$string['credit_cost_help'] = 'الرسوم التي سيتم خصمها عند التسجيل.';
$string['csvfile'] = 'ملف CSV';
$string['csvfile_help'] = 'يتم قبول الملفات ذات الامتداد *.csv فقط';
$string['currency'] = 'العملة';
$string['currency_help'] = 'اختر عملة الدفع للمقرر.';
$string['customcurrency'] = 'العملة المخصصة';
$string['customcurrency_desc'] = 'إضافة اسم عملة مخصصة لرصيد المحفظة.<br>لاحظ أن هذا غير صالح مع استخدام بوابة الدفع الفعلية.<br>إذا تركت فارغة، فستتم إضافة عملات المحفظة إلى قائمة العملات. ';
$string['customcurrencycode'] = 'رمز العملة المخصص';
$string['customcurrencycode_desc'] = 'إضافة رمز للعملة المخصصة، يشبه الدولار الأمريكي ولكن تأكد من أن هذا الرمز غير موجود بالفعل كرمز عملة متاح في بوابات الدفع المتاحة لأنه لن يتم تجاوزه، ولكن يمكنك تجاوزه عملة محفظة Moodle (MWC).';
$string['customwelcomemessage'] = 'رسالة ترحيب مخصصة';
$string['customwelcomemessage_help'] = 'يمكن إضافة رسالة ترحيب مخصصة كنص عادي أو بتنسيق Moodle-auto، بما في ذلك علامات HTML وعلامات متعددة اللغات.

قد يتم تضمين العناصر النائبة التالية في الرسالة:

* اسم المقرر {$a->coursename}
* رابط إلى صفحة الملف الشخصي للمستخدم {$a->profileurl}
* البريد الإلكتروني للمستخدم {$a->email}
* الاسم الكامل للمستخدم {$a->fullname}';

$string['datefrom'] = 'من';
$string['dateto'] = 'إلى';
$string['debitdesc_user'] = 'يتم تحصيل رسوم من المستخدم بمبلغ {$a->amount} بواسطة مستخدم بالمعرف {$a->charger}';
$string['debitdesc_course'] = 'يتم تحصيل مبلغ {$a->amount} من المستخدم مقابل التسجيل في المقرر {$a->coursename}';
$string['defaultrole'] = 'تعيين الدور الافتراضي';
$string['defaultrole_desc'] = 'اختر الدور الذي يجب تعيينه للمستخدمين أثناء التسجيل';
$string['deleteselectedusers'] = 'حذف تسجيلات المستخدم المحدد';
$string['digits'] = 'أرقام (أرقام)';
$string['discountscopouns'] = 'الخصومات والكوبونات';
$string['discountscopouns_desc'] = 'اختر ما إذا كنت تريد تطبيق نسبة الخصومات على المستخدمين باستخدام حقل ملف تعريف مخصص. <br>
وأيضًا، تطبيق كوبونات لهذا البرنامج المساعد.';
$string['discountcoupondisabled'] = 'كوبونات الخصم معطلة في هذا الموقع.';
$string['editselectedusers'] = 'تحرير تسجيلات المستخدم المحدد';

$string['enablerefund'] = 'تمكين استرداد الأموال';
$string['enablerefund_desc'] = 'إذا لم يتم تحديده، فإن كافة الأرصدة من الآن فصاعداً ستكون غير قابلة للاسترداد، لا تنس أن توضح ذلك للمستخدمين في سياسة الاسترداد';
$string['endpoint_error'] = 'خطأ في إرجاع نقطة النهاية';
$string['endpoint_incorrect'] = 'استجابة غير صحيحة';
$string['enrol_wallet'] = 'التسجيل باستخدام رصيد المحفظة';
$string['enrol_type'] = 'نوع التسجيل';
$string['enrolenddate'] = 'تاريخ الانتهاء';
$string['enrolenddate_help'] = 'إذا تم تمكينه، فيمكن للمستخدمين تسجيل أنفسهم حتى هذا التاريخ فقط.';
$string['enrolenddaterror'] = 'لا يمكن أن يكون تاريخ انتهاء التسجيل أقدم من تاريخ البدء';
$string['enrolcupon'] = 'تسجيل الكوبون';
$string['enrollmentupdated'] = 'تم تحديث التسجيل (التسجيلات)';
$string['enrolme'] = 'سجلني';
$string['enrolperiod'] = 'مدة التسجيل';
$string['enrolperiod_desc'] = 'المدة الافتراضية التي يكون فيها التسجيل صالحاً. إذا تم التعيين على صفر، فستكون مدة التسجيل غير محدودة افتراضيًا.';
$string['enrolperiod_help'] = 'المدة الزمنية التي يكون فيها التسجيل صالحاً، بدءاً من لحظة قيام المستخدم بالتسجيل بنفسه. في حالة التعطيل، ستكون مدة التسجيل غير محدودة.';
$string['enrolstartdate'] = 'تاريخ البدء';
$string['enrolstartdate_help'] = 'إذا تم تمكينه، فيمكن للمستخدمين تسجيل أنفسهم اعتبارًا من هذا التاريخ فصاعدًا فقط.';
$string['entervalue'] = 'الرجاء إدخال قيمة.';
$string['equalsto'] = 'يساوي';
$string['event_transactions'] = 'حدث معاملة المحفظة';
$string['event_transaction_debit_description'] = 'تم خصم رصيد المحفظة الخاص بالمستخدم بالمعرف {$a->availableuserid} بمقدار {$a->amount} بواسطة المستخدم بالمعرف {$a->userid} <br> more معلومات: {$a->reason}';
$string['event_transaction_credit_description'] = 'رصيد المحفظة الخاص بالمستخدم بالمعرف {$a->dependentuserid} الذي تم تحصيله بواسطة {$a->amount} {$a->refundable} بواسطة المستخدم بالمعرف {$a->userid } <br> مزيد من المعلومات: {$a->reason}';
$string['event_award'] = 'تم استلام جائزة المحفظة';
$string['event_award_desc'] = 'المستخدم ذو المعرف {$a->userid} يحصل على جائزة بقيمة {$a->amount} بسبب حصوله على الدرجة {$a->grade}% خلال المعرف {$a- >كورسايد}';
$string['event_cashback'] = 'استرداد النقود في المحفظة';
$string['event_cashback_desc'] = 'يحصل المستخدم بالمعرف {$a->userid} على استرداد نقدي في محفظته بمبلغ({$a->amount}) بسبب دفع {$a->original} للتسجيل في المقرر بالمعرف {$a->courseid}';
$string['event_coupon'] = 'كوبون المحفظة المستخدمة';
$string['event_coupon_desc'] = 'تم استخدام الكوبون ( {$a->code} ) من قبل المستخدم ذي المعرف {$a->userid}';
$string['event_newuser_gifted'] = 'إهداء مستخدم جديد';
$string['event_newuser_gifted_desc'] = 'مستخدم جديد بالمعرف {$a->userid} مُهدى بمبلغ {$a->amount} كرصيد محفظة.';
$string['expiredaction'] = 'إجراء انتهاء صلاحية التسجيل';
$string['expiredaction_help'] = 'اختر الإجراء الذي سيتم تنفيذه عند انتهاء صلاحية تسجيل المستخدم. يرجى ملاحظة أنه تتم إزالة بعض بيانات وإعدادات المستخدم من المقرر أثناء إلغاء التسجيل في المقرر.';
$string['expirymessageenrollersubject'] = 'إشعار انتهاء التسجيل';
$string['expirymessageenrollerbody'] = 'ستنتهي صلاحية التسجيل في المقرر \' {$a->course} \' خلال {$a->threshold} التالية للمستخدمين التاليين:
<br>
{$a->المستخدمون}
لتمديد فترة التسجيل، اذهب إلى {$a->extendurl}';
$string['expirymessageenrolledsubject'] = 'إشعار انتهاء التسجيل';
$string['expirymessageenrolledbody'] = 'عزيزي {$a->user}،
<br>
هذا إشعار بأن تسجيلك في المقرر \' {$a->course} \' سينتهي في {$a->timeend}.
<br>
إذا كنت بحاجة إلى مساعدة، يرجى الاتصال بـ {$a->enroller}.';
$string['filter_coupons'] = 'كوبونات التصفية';
$string['filter_transaction'] = 'معاملات التصفية';
$string['fixedvaluecoupon'] = 'كوبون ذات قيمة ثابتة';
$string['fixedcoupondisabled'] = 'الكوبونات ذات القيمة الثابتة معطلة في هذا الموقع.';

$string['giftdesc'] = 'مستخدم جديد بالمعرف {$a->userid} في {$a->time} حصل على هدية بقيمة {$a->amount} في محفظته.';
$string['giftvalue'] = 'قيمة هدية المستخدمين الجدد';
$string['giftvalue_help'] = 'القيمة التي ستتم إضافتها إلى محفظة المستخدمين الجدد.';
$string['greaterthan'] = 'أكبر من';
$string['greaterthanorequal'] = 'أكبر من أو يساوي';

$string['inefficiency_balance'] = 'ليس لديك رصيد كافٍ في المحفظة للتسجيل. مطلوب {$a->cost_before} جنيه مصري، ورصيدك هو {$a->user_balance} جنيه مصري.';
$string['inefficiency_balance_discount'] = 'ليس لديك رصيد كافٍ في المحفظة للتسجيل. <del>{$a->cost_before}EGP</del> مطلوب {$a->cost_after} جنيه مصري، ورصيدك هو {$a->user_balance} جنيه مصري.';
$string['inoughbalance'] = 'عذراً، ليس لديك رصيد كافي لهذه العملية. أنت بحاجة إلى {$a->amount} بينما لديك فقط {$a->balance}';
$string['inyourwallet'] = 'في محفظتك.';
$string['invalidpercentcoupon'] = 'القيمة غير صالحة لنسبة الكوبون، لا يمكن أن تتجاوز 100.';
$string['invalidcoupon_operation'] = 'عملية كوبون غير صالحة، قد يتم تعطيل نوع الكوبون هذا في هذا الموقع أو في حالة التكوين غير الصالح.';
$string['invalidvalue'] = 'قيمة غير صالحة، الرجاء إدخال قيمة صالحة.';

$string['longtimenosee'] = 'إلغاء التسجيل غير النشط بعد';
$string['longtimenosee_help'] = 'إذا لم يتمكن المستخدمون من الوصول إلى المقرر لفترة طويلة، فسيتم إلغاء تسجيلهم تلقائيًا. تحدد هذه المعلمة هذا الحد الزمني.';
$string['lowbalancenotification'] = 'رصيد المحفظة منخفض<br>رصيدك هو {$a}.';
$string['lowbalancenotify'] = 'إشعار بانخفاض الرصيد.';
$string['lowbalancenotify_desc'] = 'إذا تم تمكينه وكان رصيد المستخدم أقل من أو يساوي الشرط، فستظهر إشعارات تحذيرية في كل صفحة في الموقع.';
$string['lowbalancenotice'] = 'تمكين إشعار الرصيد المنخفض';
$string['lowerletters'] = 'أحرف صغيرة';

$string['mainbalance'] = 'الرصيد الرئيسي: ';
$string['maxenrolled'] = 'الحد الأقصى للمستخدمين المسجلين';
$string['maxenrolled_help'] = 'يحدد الحد الأقصى لعدد المستخدمين الذين يمكنهم التسجيل. 0 يعني عدم وجود حد.';
$string['maxenrolledreached'] = 'تم الوصول بالفعل إلى الحد الأقصى لعدد المستخدمين المسموح لهم بالتسجيل.';
$string['messagesubject'] = 'معاملات المحفظة ({$a})';
$string['messagebody_credit'] = 'لقد تم خصم مبلغ {$a->amount} من محفظتك}
<br>
رصيدك قبل ذلك كان {$a->before}
<br>
رصيدك الآن هو: {$a->balance}
<br>
مزيد من المعلومات: {$a->desc}. في: {$a->time}';
$string['messagebody_debit'] = 'يُخصم مبلغ {$a->amount} من محفظتك
<br>
رصيدك قبل ذلك كان {$a->before}
<br>
رصيدك الآن هو: {$a->balance}
<br>
مزيد من المعلومات: {$a->desc}. في: {$a->time}';
$string['messageprovider:expiry_notification'] = 'إشعارات انتهاء صلاحية التسجيل في المحفظة';
$string['messageprovider:wallet_transaction'] = 'إشعارات معاملات المحفظة';
$string['mustselectchar'] = 'يجب تحديد نوع حرف واحد على الأقل.';
$string['mintransfer'] = 'الحد الأدنى لمبلغ التحويل هو {$a}';
$string['mintransfer_config'] = 'الحد الأدنى المسموح به للنقل';
$string['mintransfer_config_desc'] = 'الحد الأدنى المسموح به لمبلغ التحويل، لا يمكن للمستخدمين تحويل رصيد لبعضهم البعض أقل من هذا المبلغ.';
$string['MWC'] = 'عملات المحفظة';
$string['mywallet'] = 'محفظتي';

$string['newenrols'] = 'السماح بالتسجيلات الجديدة';
$string['newenrols_desc'] = 'السماح للمستخدمين بالتسجيل في المقررات الجديدة بشكل افتراضي.';
$string['newenrols_help'] = 'يحدد هذا الإعداد ما إذا كان يمكن للمستخدم التسجيل في هذه المقرر أم لا.';
$string['newusergift'] = 'هدايا للمستخدمين الجدد';
$string['newusergift_desc'] = 'تطبيق هدية المحفظة للمستخدم الجديد في موقع مودل';
$string['newusergift_enable'] = 'تمكين هدايا المستخدم الجديد';
$string['newusergift_enable_help'] = 'في حالة التمكين، سيحصل المستخدمون الجدد على الهدية التي قررتها في محفظتهم.';
$string['noaccount'] = 'لا يوجد حساب';
$string['nodiscountstoshow'] = 'لا توجد خصومات للعرض.';
$string['not_set'] = 'غير محدد';
$string['notrefund'] = ' غير قابل للاسترداد (إضافي): ';
$string['nonrefundable'] = 'غير قابل للاسترداد';
$string['nonrefundable_transform_desc'] = "تحويل المعاملة إلى غير قابلة للاسترداد بسبب انتهاء فترة الاسترداد. \n ";
$string['nochange'] = 'لا تغيير';
$string['nocost'] = 'تكلفة هذا المقرر الدراسي غير صالحة';
$string['nocoupons'] = 'تعطيل الكوبونات';
$string['noreferraldata'] = 'لا توجد إحالات سابقة.';
$string['notequal'] = 'لا يساوي';
$string['noticecondition'] = 'الحد الأدنى لرصيد الإخطار';
$string['noticecondition_desc'] = 'إذا كان الرصيد أصغر من أو يساوي هذا الشرط، فسيظهر إشعار للمستخدم.';

$string['othercourserestriction'] = 'غير قادر على تسجيل نفسك في هذه المقرر إلا إذا كنت مسجلاً في هذه المقررات {$a}';

$string['payaccount'] = 'حساب الدفع';
$string['payaccount_help'] = 'اختر حساب الدفع الذي ستقبل فيه الدفعات';
$string['Paymentrequired'] = 'يمكنك الدفع لهذه المقرر مباشرة باستخدام طرق الدفع المتاحة';
$string['Paymentstopup_desc'] = 'الدفع لتعبئة المحفظة';
$string['percentdiscountcoupon'] = 'كوبون الخصم المئوية';
$string['pluginname'] = 'التسجيل في المحفظة';
$string['pluginname_desc'] = '';
$string['purchase'] = 'شراء';
$string['purchasedescription'] = 'التسجيل في المقرر {$a}';
$string['profile_field_map'] = 'تعيين حقل الملف الشخصي';
$string['profile_field_map_help'] = 'اختر حقل الملف الشخصي الذي يقوم بتخزين المعلومات حول الخصومات في ملفات تعريف المستخدمين.';
$string['privacy:metadata'] = 'لا يقوم البرنامج الإضافي للتسجيل في المحفظة بتخزين أي بيانات شخصية.';

$string['randomcoupons'] = 'كوبونات عشوائية';
$string['referral_code'] = 'رمز الإحالة';
$string['referral_code_signup'] = '';
$string['referral_code_help'] = 'باستخدام عنوان URL للإحالة، يمكنك إرسال رمز الإحالة هذا بدلاً من ذلك ويقوم المستخدم الجديد بإدخاله في صفحة التسجيل.';
$string['referral_code_signup_help'] = 'إذا كان هذا فارغاً، أدخل رمز الإحالة لتلقي هدية الإحالة.';
$string['referral_amount'] = 'مبلغ الإحالة.';
$string['referral_amount_help'] = 'مبلغ الهدية الذي ستحصل عليه أنت والمستخدم الجديد في المحفظة.';
$string['referral_amount_desc'] = 'مبلغ الهدية الذي سيحصل عليه كل من المستخدمين المُحالين والمحالين في محفظتهم.';
$string['referral_max'] = 'الحد الأقصى للإحالات';
$string['referral_max_desc'] = 'الحد الأقصى للمرات التي يمكن للمستخدم أن يتلقى فيها هدايا الإحالة (0 يعني غير محدود).';
$string['referral_user'] = 'الإحالات';
$string['referral_program'] = 'برنامج الإحالات';
$string['referral_program_desc'] = 'يمكن للمستخدمين الحاليين إحالة مستخدم جديد للانضمام إلى هذا الموقع وسيحصل كلاهما على هدية إحالة.';
$string['referral_plugins'] = 'تسجيل المكونات الإضافية';
$string['referral_plugins_desc'] = 'بما أن المستخدمين لا يحصلون على هدية الإحالة حتى يتم تسجيل المستخدم المُحال في مقرر تدريبية للتأكد من أنه مستخدم نشط.<br/>اختر طرق التسجيل المسموح بها لجعل المستخدمين يتلقون هذه الهدية ';
$string['referral_enabled'] = 'تمكين برنامج الإحالة';
$string['referral_hold'] = 'هدية قيد الحجز';
$string['referral_done'] = 'تم منح الهدية';
$string['referral_timecreated'] = 'وقت التسجيل';
$string['referral_timereleased'] = 'المُهدى عند:';
$string['referral_exceeded'] = 'رمز الإحالة: {$a} يتجاوز الحد الأقصى للاستخدام .';
$string['referral_notexist'] = 'الكود: \' {$a} \' غير موجود في قاعدة البيانات.';
$string['referral_topup'] = 'بسبب إحالة المستخدم: {$a}.';
$string['referral_gift'] = 'بسبب رمز الإحالة من المستخدم: {$a}';
$string['referral_holdgift'] = 'لديك هدية ({$a->amount}) بسبب استخدام رمز الإحالة من {$a->name}، قم بشراء مقرر تدريبية للحصول على هديتك.';
$string['referral_url'] = 'عنوان URL للإحالة';
$string['referral_url_help'] = 'أرسل عنوان url هذا إلى صديقك للتسجيل في هذا الموقع والحصول على هدية إحالة بالمبلغ التالي في محفظتك.';
$string['referral_remain'] = 'الإحالات المتبقية.';
$string['referral_remain_help'] = 'الأوقات المتبقية المتاحة لتلقي هدية الإحالة.';
$string['referral_past'] = 'الإحالات السابقة';
$string['referral_data'] = 'بيانات الإحالة';
$string['refundpolicy'] = 'سياسة الاسترداد اليدوية';
$string['refundpolicy_help'] = 'حدد سياسة استرداد مخصصة للمستخدمين ليكونوا على دراية بحالة كيفية استرداد أموالهم أو عدم استردادها قبل تعبئة محفظتهم. سيتم عرض هذه السياسة للمستخدمين بأي شكل من الأشكال لإعادة شحن محفظتهم، أو عرض رصيدهم. ';
$string['refundpolicy_default'] = '<h5>سياسة استرداد الأموال</h5>
يرجى ملاحظة ما يلي:<br>
لا يمكن استرداد المبلغ المدفوع لتعبئة محفظتك في الحالات التالية:<br>
1- إذا كان هذا المبلغ بسبب هدية أو مكافأة أو استرداد نقدي للمستخدم الجديد.<br>
2- إذا انتهت فترة سماح الاسترداد (14 يومًا).<br>
3- أي مبلغ تم استخدامه بالفعل في التسجيل غير قابل للاسترداد.<br>
عند شحن محفظتك بأي طريقة يعني موافقتك على هذه السياسة.';
$string['refundperiod'] = 'فترة السماح لاسترداد الأموال';
$string['refundperiod_desc'] = 'الوقت الذي لا يستطيع المستخدمون بعده استرداد ما دفعوه لتعبئة محفظتهم. 0 يعني استرداد الأموال في أي وقت.';
$string['refunduponunenrol_desc'] = 'تم استرداد المبلغ بمبلغ {$a->credit} بعد خصم رسوم إلغاء التسجيل البالغة {$a->fee} في المقرر: {$a->coursename}.';
$string['receiver'] = 'Receiver';
$string['role'] = 'الدور المعين الافتراضي';

$string['sendcoursewelcomemessage'] = 'أرسل رسالة ترحيب للمقرر';
$string['sendcoursewelcomemessage_help'] = 'عندما يقوم المستخدم بالتسجيل في المقرر، قد يتم إرسال رسالة ترحيب عبر البريد الإلكتروني إليه. إذا تم إرساله من جهة اتصال المقرر الدراسي (المدرس بشكل افتراضي)، وكان لدى أكثر من مستخدم هذا الدور، فسيتم إرسال البريد الإلكتروني من المستخدم الأول الذي تم تعيينه للدور.';
$string['sender'] = 'Sender';
$string['sendexpirynotificationstask'] = "مهمة التسجيل في المحفظة ترسل إشعارات انتهاء الصلاحية";
$string['sendpaybutton'] = 'الدفع المباشر';
$string['showprice'] = 'أظهر السعر على أيقونة التسجيل';
$string['showprice_desc'] = 'في حالة التحديد، سيتم عرض سعر المقرر فوق أيقونة التسجيل في بطاقة المقرر.';
$string['singlecoupon'] = 'كوبون واحدة';
$string['status'] = 'السماح بالتسجيلات الموجودة';
$string['status_desc'] = 'تمكين طريقة التسجيل في المحفظة في المقررات الدراسية الجديدة.';
$string['status_help'] = 'إذا تم تمكينه مع تعطيل \' السماح بالتسجيلات الجديدة \' ، فلن يتمكن سوى المستخدمين الذين قاموا بالتسجيل مسبقًا من الوصول إلى المقرر. إذا تم تعطيلها، فسيتم تعطيل طريقة التسجيل هذه بشكل فعال، حيث يتم تعليق جميع التسجيلات الحالية ولا يمكن للمستخدمين الجدد التسجيل.';
$string['smallerthan'] = 'أصغر من';
$string['smallerthanorequal'] = 'أصغر من أو يساوي';
$string['sourcemoodle'] = 'محفظة موودل الداخلية';
$string['sourcewordpress'] = 'محفظة تيرا خارجية (WooWallet)';
$string['submit_coupongenerator'] = 'إنشاء';
$string['syncenrolmentstask'] = 'مهمة مزامنة التسجيل في المحفظة';

$string['topup'] = 'اشحن رصيدك';
$string['topupafterdiscount'] = 'الدفع الفعلي';
$string['topupafterdiscount_help'] = 'المبلغ بعد الخصم.';
$string['topupvalue'] = 'قيمة الشحن';
$string['topupvalue_help'] = 'قيمة تعبئة محفظتك باستخدام طرق الدفع';
$string['topupcoupon_desc'] = 'بواسطة رمز الكوبون {$a}';
$string['topuppayment_desc'] = 'تعبئة المحفظة عن طريق دفع {$a} باستخدام بوابة الدفع.';
$string['transactions'] = 'معاملات المحفظة';
$string['transaction_type'] = 'نوع المعاملة';
$string['transaction_perpage'] = 'المعاملات لكل صفحة';
$string['transfer'] = 'تحويل الرصيد إلى مستخدم آخر';
$string['transfer_desc'] = 'تمكين أو تعطيل قدرة المستخدمين على تحويل الرصيد إلى مستخدمين آخرين وتحديد رسوم التحويل لكل عملية.';
$string['transfer_enabled'] = 'النقل إلى مستخدم آخر';
$string['transfer_enabled_desc'] = 'تمكين أو تعطيل قدرة المستخدمين على تحويل الرصيد إلى مستخدمين آخرين عبر البريد الإلكتروني.';
$string['transfer_notenabled'] = 'النقل من مستخدم إلى مستخدم \' غير ممكن في هذا الموقع.';
$string['transferfee_desc'] = 'لاحظ أنه سيتم خصم {$a->fee}% من {$a->from}.';
$string['transferfee_from'] = 'خصم الرسوم من:';
$string['transferfee_from_desc'] = 'اختر كيفية خصم الرسوم.<br>
من المرسل: يعني تحويل المبلغ بالكامل وخصم رصيد إضافي من المرسل.<br>
من المرسل إليه: يعني أن المبلغ المحول للمستلم أقل من المبلغ المرسل بالرسوم.';
$string['transferop_desc'] = 'تحويل مبلغ صافي قدره {$a->amount} مع رسوم تحويل {$a->fee} إلى {$a->receiver}';
$string['transfercent'] = 'رسوم التحويل %';
$string['transfercent_desc'] = 'لتحويل مبلغ ما إلى مستخدم آخر، سيتم خصم نسبة مئوية من المرسل افتراضيًا. اضبطه على 0 حتى لا يتم خصم أي رسوم.';
$string['transferpage'] = 'تحويل الرصيد';
$string['turn_not_refundable_task'] = 'تحويل الرصيد إلى غير قابل للاسترداد.';

$string['unenrol'] = 'إلغاء تسجيل المستخدم';
$string['unenrollimitafter'] = 'لا يمكن إلغاء التسجيل الذاتي بعد:';
$string['unenrollimitafter_desc'] = 'لا يمكن للمستخدمين تسجيل أنفسهم بعد هذه الفترة من تاريخ بدء التسجيل. 0 يعني غير محدود.';
$string['unenrollimitbefor'] = 'لا يمكن إلغاء التسجيل الذاتي قبل:';
$string['unenrollimitbefor_desc'] = 'لا يمكن للمستخدمين إلغاء التسجيل بأنفسهم قبل هذه الفترة من تاريخ انتهاء التسجيل. 0 يعني عدم وجود حد.';
$string['unenrolrefund'] = 'استرداد المبلغ عند إلغاء التسجيل؟';
$string['unenrolrefund_desc'] = 'في حالة التمكين، سيتم استرداد أموال المستخدمين إذا قاموا بإلغاء تسجيلهم في المقرر.';
$string['unenrolrefundperiod'] = 'استرداد الأموال عند فترة سماح إلغاء التسجيل';
$string['unenrolrefundperiod_desc'] = 'إذا قام المستخدم بإلغاء تسجيله خلال هذه الفترة من تاريخ بدء التسجيل، فسيتم استرداد أمواله.';
$string['unenrolrefundfee'] = 'رسوم النسبة المئوية للاسترداد';
$string['unenrolrefundfee_desc'] = 'اختر النسبة المئوية للمبلغ الذي لن يتم استرداده بعد إلغاء التسجيل كرسوم.';
$string['unenrolrefundpolicy'] = 'سياسة استرداد الإلغاء';
$string['unenrolrefundpolicy_help'] = 'في حالة تمكين استرداد الأموال عند إلغاء التسجيل، ستكون هذه السياسة مرئية للمستخدمين قبل تسجيل أنفسهم في المقررات باستخدام تسجيل المحفظة.<br>
سيتم استبدال {fee} في السياسة بنسبة الرسوم.<br>
سيتم استبدال {period} بفترة السماح بالأيام.';
$string['unenrolrefundpolicy_default'] = '<p dir="ltr" style="text-align: left;"><strong>شروط استرداد الأموال عند إلغاء التسجيل:</strong></p>
<p dir="ltr" style="text-align: left;">
إذا قمت بإلغاء تسجيلك في المقرر خلال {period} يوم من تاريخ البدء، فسيتم رد المبلغ الذي دفعته إليك بعد خصم {fee}% من المبلغ المدفوع.
سيعود هذا المبلغ إلى محفظتك ويمكنك استخدامه للتسجيل في مقررات أخرى ولكن لا يمكن استرداده يدويًا.<br>
بالضغط على الشراء يعني أنك وافقت على هذه الشروط.
</p>';
$string['unenrolrefund_head'] = 'استرداد أموال المستخدمين عند إلغاء التسجيل.';
$string['unenrolrefund_head_desc'] = 'أعد الرسوم المدفوعة للمقرر بعد إلغاء التسجيل فيها.';
$string['unenrolselfconfirm'] = 'هل تريد فعلاً إلغاء تسجيلك من المقرر "{$a}"؟';
$string['unenrolselfenabled'] = 'تمكين إلغاء التسجيل الذاتي';
$string['unenrolselfenabled_desc'] = 'في حالة التمكين، يُسمح للمستخدمين بإلغاء تسجيل أنفسهم من المقرر الدراسي.';
$string['unenrolself_notallowed'] = 'لم تتمكن من إلغاء تسجيلك في هذه المقرر.';
$string['unenroluser'] = 'هل تريد حقاً إلغاء تسجيل "{$a->user}" من المقرر الدراسي "{$a->course}؟';
$string['unenrolusers'] = 'إلغاء تسجيل المستخدمين';
$string['upperletters'] = 'الحالة العلوية';
$string['upload_coupons'] = 'كوبونات التحميل';
$string['upload_coupons_help'] = 'قم بتحميل الكوبونات في ملف CSV لإضافة أو تحرير كوبونات المحفظة بشكل مجمّع، يجب أن يحتوي ملف CSV على عمودين أساسيين:<br>
\' code \' : رمز الكوبون المراد إضافتها أو تحديثها.<br>
\' value \' : قيمة الكوبون ولا يجوز تركها 0 إلا إذا كان نوعها (تسجيل).<br>
والأعمدة الاختيارية:<br>
\' type \' : نوع الكوبون وأربع قيم مسموحة فقط (ثابتة أو نسبة مئوية أو فئة أو تسجيل).<br>
\' courses \' : يسري فقط عندما يكون النوع (تسجيل) ويجب أن يحتوي على الأسماء المختصرة للمقررات المطلوبة مفصولة بـ / .<br>
\' category \' : معرف الفئة التي تتوفر فيها الكوبون للاستخدام.<br>
\' maxusage \' : الحد الأقصى لاستخدام رمز الكوبون.<br>
\' validfrom \' : الطابع الزمني للتاريخ الذي تصبح فيه الكوبون متاحة للاستخدام.<br>
\' validto \' : الطابع الزمني للتاريخ الذي لن تصبح الكوبون متاحة بعده.<br>
\' maxperuser \' : الحد الأقصى للوقت الذي يمكن لمستخدم واحد أن يستخدم فيه كوبون.<br>
\' id \' : معرف الكوبون في حالة تحديثها.';
$string['upload_result'] = 'النتيجة';
$string['usernotfound'] = 'لم يتم العثور على مستخدم لديه هذا البريد الإلكتروني {$a}';
$string['usedfrom'] = 'مستخدم من';
$string['usedto'] = 'معتاد على';

$string['value'] = 'المبلغ لكل معاملة';

$string['wallet:bulkedit'] = 'تحرير التسجيلات بشكل مجمّع في كافة المقررات الدراسية';
$string['wallet:config'] = 'تكوين حالات التسجيل في المحفظة';
$string['wallet:creditdebit'] = 'الائتمان والخصم للمستخدمين الآخرين';
$string['wallet:createcoupon'] = 'إنشاء كوبونات المحفظة';
$string['wallet:deletecoupon'] = 'حذف كوبون المحفظة';
$string['wallet:downloadcoupon'] = 'تنزيل كوبونات المحفظة';
$string['wallet:editcoupon'] = 'تحرير الكوبونات';
$string['wallet:enrolself'] = 'قم بشراء مقرر تدريبية من خلال مثيل محفظة التسجيل' ;
$string['wallet:manage'] = 'إدارة المستخدمين المسجلين';
$string['wallet:unenrol'] = 'إلغاء تسجيل المستخدمين من المقرر الدراسي';
$string['wallet:unenrolself'] = 'إلغاء التسجيل الذاتي من المقرر الدراسي';
$string['wallet:transaction'] = 'عرض جدول المعاملات';
$string['wallet:transfer'] = 'نقل رصيد المحفظة إلى مستخدم آخر';
$string['wallet:viewcoupon'] = 'عرض جدول كوبونات المحفظة';
$string['wallet:viewotherbalance'] = 'عرض رصيد المحفظة للآخرين';
$string['walletcashback'] = 'استرداد النقود مقابل استخدام المحفظة';
$string['walletcashback_desc'] = 'تمكين برنامج الاسترداد النقدي عبر الموقع بأكمله';
$string['walletcredit'] = 'رصيد المحفظة';
$string['walletbulk'] = 'تحرير مجمع لمثيلات تسجيل المحفظة';
$string['walletsource'] = 'مصدر المحفظة';
$string['walletsource_help'] = 'اختر إما ربط المحفظة بمحفظة Tera الخارجية للتجارة الإلكترونية، أو مجرد استخدام المحفظة الداخلية في مودل';
$string['welcometocourse'] = 'مرحبًا بك في {$a}';
$string['welcometocoursetext'] = 'مرحبًا بك في {$a->coursename}!

إذا لم تكن قد قمت بذلك بالفعل، فيجب عليك تعديل صفحة ملفك الشخصي حتى نتمكن من معرفة المزيد عنك:

{$a->profileurl}';
$string['wordpressurl'] = 'عنوان URL الخاص بـWordpress';
$string['wordpressurl_desc'] = 'عنوان url الخاص بـ WordPress مع إضافة woo-wallet (محفظة tera) عليه';
$string['wordpressloggins'] = 'تسجيل دخول/خروج المستخدم من ووردبريس';
$string['wordpressloggins_desc'] = 'إذا كان المستخدمون الممكّنون قد قاموا بتسجيل الدخول والخروج من موقع ووردبريس عندما قاموا بتسجيل الدخول أو الخروج من مودل. (لاحظ أن هذه طريقة واحدة فقط)';
$string['wordpress_secretkey'] = 'المفتاح السري';
$string['wordpress_secretkey_help'] = 'يجب على المشرف إضافة أي قيمة هنا ونفس القيمة في إعداد moo-wallet في موقع Wordpress.';
$string['wrongemailformat'] = 'تنسيق بريد إلكتروني خاطئ.';
$string['validfrom'] = 'صالح من';
$string['validto'] = 'صالح لـ';

$string['youhavebalance'] = 'لديك رصيد:';

$string['repurchase'] = 'إعادة شراء';
$string['repurchase_desc'] = 'إعدادات لإعادة شراء المقررات. إن تم تفعيلها يمكن للمستخدمين إعادة شراء المقررات بعد إنتهاء وقت الدخول.';
$string['repurchase_firstdis'] = 'تخفيض إعادة الشراء لأول مرة';
$string['repurchase_firstdis_desc'] = 'إن تم تحديدها سيتم تطبيق تخفيض لإعادة شراء المحاضرة لأول مرةز يجم أن تكون القيمة من 0 إلى 100.';
$string['repurchase_seconddis'] = 'تخفيض إعادة الشراء لثاني مرة';
$string['repurchase_seconddis_desc'] = 'تطبيق تخفيض للمستخدم لإعادة الشراء لثاني مرة مما يعني انها ثالث مرة للمستخدم شراء المقرر ويجب أن تكون من 0 إلى 100 وأن تكون أزيد من تخفيض أول مرة';
$string['selectuser'] = 'برجاء إختيار مستخدم.';
