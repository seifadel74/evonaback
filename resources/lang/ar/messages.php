<?php

return [
  // Auth
  'account_created' => 'تم إنشاء الحساب بنجاح.',
  'credentials_incorrect' => 'بيانات الدخول غير صحيحة.',
  'account_deactivated' => 'تم تعطيل هذا الحساب.',
  'logged_in' => 'تم تسجيل الدخول بنجاح.',
  'reset_sent' => 'إذا كان هناك حساب بهذا البريد، تم إرسال رابط إعادة تعيين كلمة المرور.',
  'invalid_token' => 'الرمز غير صالح.',
  'token_expired' => 'انتهت صلاحية الرمز.',
  'password_reset' => 'تم إعادة تعيين كلمة المرور بنجاح.',
  'logged_out' => 'تم تسجيل الخروج بنجاح.',
  'profile_updated' => 'تم تحديث الملف الشخصي بنجاح.',
  'avatar_updated' => 'تم تحديث الصورة الشخصية.',

  // Products
  'product_created' => 'تم إنشاء المنتج بنجاح.',
  'product_updated' => 'تم تحديث المنتج بنجاح.',
  'product_deleted' => 'تم حذف المنتج بنجاح.',
  'product_restored' => 'تم استعادة المنتج بنجاح.',
  'product_duplicated' => 'تم نسخ المنتج بنجاح.',
  'products_updated' => 'تم تحديث المنتجات بنجاح.',

  // Reviews
  'review_exists' => 'لقد قمت بتقييم هذا المنتج مسبقاً.',
  'review_created' => 'تم إرسال التقييم بنجاح.',
  'review_updated' => 'تم تحديث التقييم بنجاح.',
  'review_deleted' => 'تم حذف التقييم بنجاح.',
  'review_approved' => 'تمت الموافقة على التقييم.',
  'review_deleted_admin' => 'تم حذف التقييم.',

  // Cart
  'product_unavailable' => 'المنتج غير متاح.',
  'variant_unavailable' => 'البديل غير متاح.',
  'insufficient_stock' => 'المخزون غير كافٍ.',
  'product_added_to_cart' => 'تمت إضافة المنتج إلى السلة.',

  // Checkout
  'order_created_cod' => 'تم إنشاء الطلب بنجاح. الدفع عند الاستلام.',
  'payment_failed' => 'فشل بدء عملية الدفع. حاول مرة أخرى.',
  'order_created_redirect' => 'تم إنشاء الطلب. جارٍ التوجيه للدفع.',

  // Orders
  'order_cannot_cancel' => 'لا يمكن إلغاء الطلب في حالته الحالية.',
  'order_cancelled' => 'تم إلغاء الطلب بنجاح.',
  'order_status_updated' => 'تم تحديث حالة الطلب بنجاح.',
  'payment_status_updated' => 'تم تحديث حالة الدفع.',
  'tracking_event_added' => 'تمت إضافة حدث التتبع.',

  // Categories
  'category_created' => 'تم إنشاء التصنيف بنجاح.',
  'category_updated' => 'تم تحديث التصنيف بنجاح.',
  'category_delete_has_products' => 'لا يمكن حذف تصنيف مرتبط بمنتجات.',
  'category_deleted' => 'تم حذف التصنيف بنجاح.',

  // Coupons
  'coupon_invalid' => 'رمز الكوبون غير صالح.',
  'coupon_created' => 'تم إنشاء الكوبون بنجاح.',
  'coupon_updated' => 'تم تحديث الكوبون بنجاح.',
  'coupon_deleted' => 'تم حذف الكوبون بنجاح.',

  // Customers
  'customer_deleted' => 'تم حذف العميل بنجاح.',

  // Addresses
  'address_created' => 'تمت إضافة العنوان بنجاح.',
  'address_updated' => 'تم تحديث العنوان بنجاح.',
  'address_deleted' => 'تم حذف العنوان بنجاح.',

  // Images
  'image_uploaded' => 'تم رفع الصورة بنجاح.',
  'image_added_from_url' => 'تمت إضافة الصورة من الرابط بنجاح.',
  'image_updated' => 'تم تحديث الصورة بنجاح.',
  'image_deleted' => 'تم حذف الصورة بنجاح.',

  // Newsletter
  'subscribed' => 'تم الاشتراك بنجاح.',
  'unsubscribed' => 'تم إلغاء الاشتراك بنجاح.',

  // Inventory
  'inventory_updated' => 'تم تحديث المخزون بنجاح.',

  // Stripe
  'invalid_signature' => 'توقيع غير صالح.',
  'unhandled_event' => 'نوع الحدث غير معالج.',
  'order_not_found_stripe' => 'الطلب غير موجود.',
  'order_already_processed' => 'تمت معالجة الطلب مسبقاً.',
  'payment_not_completed' => 'لم يتم إتمام الدفع.',
  'session_mismatch' => 'عدم تطابق الجلسة.',
  'amount_mismatch' => 'عدم تطابق المبلغ.',
  'payment_confirmed' => 'تم تأكيد الدفع.',
  'session_expired' => 'تمت معالجة انتهاء الجلسة.',

  // Middleware
  'unauthorized_admin' => 'غير مصرح. يجب أن تكون مشرفاً.',
  'unauthorized' => 'غير مصرح.',
];
