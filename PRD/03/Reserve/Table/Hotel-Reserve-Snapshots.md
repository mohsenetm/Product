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

| نام ستون           | نوع داده         | Null |
| :----------------- | :--------------- | :--- |
| `id`               | bigint unsigned  | خیر  |
| `reserve_id`       | bigint unsigned  | خیر  |
| `check_in`         | date             | خیر  |
| `check_out`        | date             | خیر  |
| `number_of_nights` | tinyint unsigned | خیر  |
| `first_name`       | varchar(255)     | خیر  |
| `last_name`        | varchar(255)     | خیر  |
| `phone`            | varchar(255)     | خیر  |
| `email`            | varchar(255)     | بله  |
| `status`           | enum             | خیر  |
| `is_worthless`     | tinyint(1)       | خیر  |
| `price`            | bigint unsigned  | خیر  |
| `purchase_price`   | bigint unsigned  | خیر  |
| `sales_price`      | bigint unsigned  | خیر  |
| `board_price`      | bigint unsigned  | خیر  |
| `discount`         | bigint unsigned  | خیر  |
| `expired_at`       | timestamp        | بله  |
| `created_at`       | timestamp        | خیر  |

## روابط

| رابطه                          | جدول                                  | ستون کلید خارجی |
| :----------------------------- | :------------------------------------ | :-------------- |
| belongs to (متعلق به)          | `hotel_reserves`                      | `reserve_id`    |
| referenced by (ارجاع شده توسط) | `hotel_reserve_provider_transactions` | `snapshot_id`   |
| referenced by (ارجاع شده توسط) | `hotel_reserve_activities`            | `snapshot_id`   |

# مستندات جدول `hotel_reserve_room_availabilities`

## توضیحات

یک اسنپ‌شات (تصویر لحظه‌ای) به ازای هر شب و هر اتاق از شرایط قیمت‌گذاری و ظرفیت (availability) که در لحظه رزرو ثبت شده است.  
برای **هر شب** از **هر اتاق رزرو شده** یک سطر ایجاد می‌شود که ریزترین سطح (lowest-granularity) رکورد قیمت‌گذاری را در سیستم تشکیل می‌دهد.

> **مثال:** یک رزرو ۳ شبه با ۲ اتاق → **۶ سطر** ($2 \text{ اتاق} \times 3 \text{ شب}$)

## تصمیمات طراحی

- **فقط-افزودنی (Append-only)** — فقط دارای `created_at` (تنظیم شده از طریق `useCurrent()`). این سطرها هرگز بروزرسانی نمی‌شوند؛ آن‌ها نشان‌دهنده نرخ‌های روزانه دقیقی هستند که به مهمان پیشنهاد شده است.
- ذخیره نرخ‌های روزانه (per-day rates)، گزارش‌گیری دقیق درآمد، حسابرسی به ازای هر شب و محاسبات بازپرداخت (refund) در آینده را امکان‌پذیر می‌کند.

## ستون‌ها

| نام ستون                | نوع داده        | Null | کلید |
| :---------------------- | :-------------- | :--- | :--- |
| `id`                    | bigint unsigned | خیر  | PRI  |
| `reserve_room_id`       | bigint unsigned | خیر  | MUL  |
| `available_day`         | date            | خیر  |      |
| `inventory`             | int unsigned    | خیر  |      |
| `rack_rate`             | int unsigned    | خیر  |      |
| `daily_rate`            | int unsigned    | خیر  |      |
| `sell_rate`             | int unsigned    | خیر  |      |
| `baby_rack_rate`        | int unsigned    | خیر  |      |
| `baby_daily_rate`       | int unsigned    | خیر  |      |
| `baby_sell_rate`        | int unsigned    | خیر  |      |
| `extend_bed_rack_rate`  | int unsigned    | خیر  |      |
| `extend_bed_daily_rate` | int unsigned    | خیر  |      |
| `extend_bed_sell_rate`  | int unsigned    | خیر  |      |
| `reservation_state`     | enum            | خیر  |      |
| `available_type`        | enum            | خیر  |      |
| `close_to_arrival`      | tinyint(1)      | خیر  |      |
| `close_to_departure`    | tinyint(1)      | خیر  |      |
| `closed`                | tinyint(1)      | خیر  |      |
| `min_stay`              | int unsigned    | بله  |      |
| `max_stay`              | int unsigned    | بله  |      |
| `created_at`            | timestamp       | خیر  |      |
## روابط

| رابطه | جدول | ستون کلید خارجی | نوع |
| :--- | :--- | :--- | :--- |
| متعلق است به (belongs to) | `hotel_reserve_rooms` | `reserve_room_id` | چند به یک (Many → One) |
