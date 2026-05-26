# مستندات جدول `hotel_reserve_snapshots`

## توضیحات

یک لاگ حسابرسی (Audit Log) **فقط-افزودنی (Append-only)** که هر بار وضعیت رزرو تغییر می‌کند، یک کپی از سطر `hotel_reserves` در همان لحظه (point-in-time) را ثبت می‌کند.  
اسنپ‌شات‌ها هرگز بروزرسانی یا حذف نمی‌شوند. آن‌ها به این سوال پاسخ می‌دهند: *"جزئیات این رزرو در وضعیت X چگونه بود؟"*

## تصمیمات طراحی

- **فقط-افزودنی (Append-only)** — ستون `updated_at` وجود ندارد و فقط دارای `created_at` است (که از طریق `useCurrent()` تنظیم می‌شود). عملیات بروزرسانی (Update) و حذف (Delete) هرگز نباید روی این جدول انجام شود.
- ساختار جدول (Schema) عمداً مشابه `hotel_reserves` است (همان مقادیر enum برای وضعیت و همان فیلدهای قیمت) تا هر ابزار مقایسه‌ای (Diff Tool) بتواند دو اسنپ‌شات متوالی را با هم مقایسه کند.
- جلوگیری از ارجاع حلقوی (Circular-reference): این جدول **هیچ کلید خارجی به** `hotel_reserve_activities` یا `hotel_reserve_provider_transactions` ندارد؛ در عوض، آن جداول به `snapshot_id` ارجاع می‌دهند.
- رفتار `onDelete('restrict')` روی `reserve_id` — یک رزرو تا زمانی که اسنپ‌شاتی از آن وجود دارد، نمی‌تواند حذف شود.

## زمان ایجاد یک اسنپ‌شات

**هر بار** که جدول `hotel_reserves` بروزرسانی می‌شود، باید یک سطر اسنپ‌شات جدید درج شود. شناسه (`id`) اسنپ‌شاتِ جدید سپس در ستون `snapshot_id` از جدول فعالیت‌ها (activities) یا تراکنش‌های تامین‌کننده (provider transactions) که باعث این تغییر شده‌اند، نوشته می‌شود.

## ستون‌ها

| نام ستون           | نوع داده         |
| :----------------- | :--------------- |
| `id`               | bigint unsigned  |
| `reserve_id`       | bigint unsigned  |
| `check_in`         | date             |
| `check_out`        | date             |
| `number_of_nights` | tinyint unsigned |
| `first_name`       | varchar(255)     |
| `last_name`        | varchar(255)     |
| `phone`            | varchar(255)     |
| `email`            | varchar(255)     |
| `status`           | enum             |
| `is_worthless`     | tinyint(1)       |
| `price`            | bigint unsigned  |
| `purchase_price`   | bigint unsigned  |
| `sales_price`      | bigint unsigned  |
| `board_price`      | bigint unsigned  |
| `discount`         | bigint unsigned  |
| `expired_at`       | timestamp        |
| `created_at`       | timestamp        |

## روابط

| رابطه                          | جدول                                  | ستون کلید خارجی |
| :----------------------------- | :------------------------------------ | :-------------- |
| belongs to (متعلق به)          | `hotel_reserves`                      | `reserve_id`    |
| referenced by (ارجاع شده توسط) | `hotel_reserve_provider_transactions` | `snapshot_id`   |
| referenced by (ارجاع شده توسط) | `hotel_reserve_activities`            | `snapshot_id`   |
