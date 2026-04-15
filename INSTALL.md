# دليل التثبيت - نظام ERP لشركة الأخشاب

## المتطلبات
- PHP 8.2+
- MySQL 8.0+
- Composer 2.x
- استضافة cPanel

## خطوات التثبيت على cPanel

### 1. رفع الملفات
- استخدم Git أو FTP لرفع جميع الملفات إلى مجلد الموقع
- أو استخدم GitHub Actions (راجع `.github/workflows/deploy.yml`)

### 2. إعداد قاعدة البيانات
- أنشئ قاعدة بيانات MySQL في cPanel
- سجّل بيانات الاتصال

### 3. إعداد ملف .env
```bash
cp .env.example .env
```
عدّل الملف بالبيانات الصحيحة:
```
APP_URL=https://yourdomain.com
DB_HOST=localhost
DB_DATABASE=wood_erp
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

### 4. التثبيت عبر Terminal في cPanel
```bash
# تثبيت المكتبات
composer install --no-dev --optimize-autoloader

# توليد مفتاح التطبيق
php artisan key:generate

# تشغيل الـ migrations
php artisan migrate --force

# تشغيل البيانات الأولية
php artisan db:seed --force

# ربط مجلد التخزين
php artisan storage:link

# تحسين الأداء
php artisan optimize
php artisan filament:assets
```

### 5. بيانات الدخول الأولية
| الدور | البريد الإلكتروني | كلمة المرور |
|-------|------------------|-------------|
| مدير النظام | admin@wood-erp.com | password |
| محاسب | accountant@wood-erp.com | password |
| أمين مخزن | warehouse@wood-erp.com | password |
| بائع | sales@wood-erp.com | password |

> ⚠️ **مهم**: غيّر كلمات المرور فور الدخول!

## الأدوار والصلاحيات

### مدير النظام (super_admin)
- كامل الصلاحيات بدون قيود

### المحاسب (accountant)
- الحسابات والفواتير والتقارير
- بدون: إدارة المستخدمين، بدون حذف

### أمين المخزن (warehouse)
- المخزون وفواتير التوريد
- بدون: الحسابات البنكية، بدون التكاليف

### البائع (salesperson)
- فواتير البيع وعروض الأسعار والعملاء
- بدون: أسعار التكلفة أو الحسابات البنكية

## الميزات الرئيسية
- ✅ FIFO لحساب التكلفة تلقائياً
- ✅ فواتير بيع وشراء مع PDF
- ✅ دعم الدفع بالدولار مع تسجيل سعر الصرف
- ✅ تحويل مخزون بين الفروع
- ✅ عروض أسعار تتحول لفواتير
- ✅ إشعارات المخزون المنخفض
- ✅ لوحة تحكم بالإحصائيات والرسوم البيانية
- ✅ RTL عربي كامل

## GitHub Actions للنشر
أضف هذه الأسرار في إعدادات المستودع:
- `FTP_HOST`: عنوان الـ FTP
- `FTP_USER`: اسم المستخدم
- `FTP_PASSWORD`: كلمة المرور
- `FTP_SERVER_DIR`: المسار على الخادم (مثلاً: `/home/username/public_html/`)
