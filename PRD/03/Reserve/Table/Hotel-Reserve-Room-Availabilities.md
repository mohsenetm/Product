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