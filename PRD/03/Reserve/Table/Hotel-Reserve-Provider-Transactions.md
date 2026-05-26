# مستندات جدول `hotel_reserve_provider_transactions`

## توضیحات

یک لاگ کامل و فقط-افزودنی (append-only) از تمام فراخوانی‌های API انجام شده به — یا وب‌هوک‌های دریافت شده از — یک تامین‌کننده (provider) برای یک رزرو مشخص.  
هر سطر داده‌های خام `request_payload` و `response_payload` (به فرمت JSON) را ذخیره می‌کند، که این جدول را به منبع اصلی و معتبر برای خطایابی (دیباگ) مشکلات ارتباطی با تامین‌کننده تبدیل می‌کند.

## تصمیمات طراحی

- **فقط-افزودنی (Append-only)** — فقط ستون `created_at` (تنظیم شده از طریق `useCurrent()`) وجود دارد و ستون `updated_at` نداریم. رکوردها پس از ایجاد هرگز تغییر نمی‌کنند.
- فیلد `snapshot_id` تراکنش را به سطر `hotel_reserve_snapshots` که **در نتیجه‌ی** این تراکنش ایجاد شده است، پیوند می‌دهد. اگر تراکنش وضعیت رزرو را تغییر نداده باشد (مثلاً یک درخواست بررسی وضعیت که همان وضعیت قبلی را برگردانده است)، این مقدار می‌تواند `NULL` باشد.
- فیلد `is_success` یک فیلتر سریع از نوع بولین (boolean) برای داشبوردهای مانیتورینگ فراهم می‌کند تا نیازی به پردازش (parsing) `response_payload` نباشد.
- فیلدهای `request_payload` و `response_payload` به صورت `JSON` ذخیره می‌شوند تا امکان کوئری گرفتن در آینده فراهم باشد؛ مقدار `NULL` برای سطرهایی که از طریق وب‌هوک (webhook) ایجاد شده‌اند (جایی که هیچ درخواست خروجی ارسال نشده است) معتبر است.
- استفاده از `onDelete('restrict')` روی هر دو فیلد `reserve_id` و `snapshot_id` از حذف داده‌های رزروهای فعال جلوگیری می‌کند.

## ستون‌ها

| نام ستون                     | نوع داده        |
| :--------------------------- | :-------------- |
| `id`                         | bigint unsigned |
| `reserve_id`                 | bigint unsigned |
| `snapshot_id`                | bigint unsigned |
| `provider_id`                | bigint unsigned |
| `status`                     | varchar(255)    |
| `provider_confirmation_code` | varchar(255)    |
| `is_success`                 | tinyint(1)      |
| `error_message`              | text            |
| `request_payload`            | json            |
| `response_payload`           | json            |
| `created_at`                 | timestamp       |



| رابطه                     | جدول                      | ستون کلید خارجی |
| :------------------------ | :------------------------ | :-------------- |
| متعلق است به (belongs to) | `hotel_reserves`          | `reserve_id`    |
| متعلق است به (belongs to) | `hotel_reserve_snapshots` | `snapshot_id`   |
| متعلق است به (belongs to) | `providers`               | `provider_id`   |