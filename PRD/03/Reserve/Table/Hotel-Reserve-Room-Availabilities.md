# مستندات جدول `hotel_reserve_room_availabilities`

## توضیحات

یک اسنپ‌شات (تصویر لحظه‌ای) به ازای هر شب و هر اتاق از شرایط قیمت‌گذاری و ظرفیت (availability) که در لحظه رزرو ثبت شده است.  
برای **هر شب** از **هر اتاق رزرو شده** یک سطر ایجاد می‌شود که ریزترین سطح (lowest-granularity) رکورد قیمت‌گذاری را در سیستم تشکیل می‌دهد.

> **مثال:** یک رزرو ۳ شبه با ۲ اتاق → **۶ سطر** ($2 \text{ اتاق} \times 3 \text{ شب}$)

## تصمیمات طراحی

- **فقط-افزودنی (Append-only)** — فقط دارای `created_at` (تنظیم شده از طریق `useCurrent()`). این سطرها هرگز بروزرسانی نمی‌شوند؛ آن‌ها نشان‌دهنده نرخ‌های روزانه دقیقی هستند که به مهمان پیشنهاد شده است.
- ذخیره نرخ‌های روزانه (per-day rates)، گزارش‌گیری دقیق درآمد، حسابرسی به ازای هر شب و محاسبات بازپرداخت (refund) در آینده را امکان‌پذیر می‌کند.

## ستون‌ها

| نام ستون                | نوع داده        |
| :---------------------- | :-------------- |
| `id`                    | bigint unsigned |
| `reserve_room_id`       | bigint unsigned |
| `available_day`         | date            |
| `inventory`             | int unsigned    |
| `rack_rate`             | int unsigned    |
| `daily_rate`            | int unsigned    |
| `sell_rate`             | int unsigned    |
| `baby_rack_rate`        | int unsigned    |
| `baby_daily_rate`       | int unsigned    |
| `baby_sell_rate`        | int unsigned    |
| `extend_bed_rack_rate`  | int unsigned    |
| `extend_bed_daily_rate` | int unsigned    |
| `extend_bed_sell_rate`  | int unsigned    |
| `reservation_state`     | enum            |
| `available_type`        | enum            |
| `close_to_arrival`      | tinyint(1)      |
| `close_to_departure`    | tinyint(1)      |
| `closed`                | tinyint(1)      |
| `min_stay`              | int unsigned    |
| `max_stay`              | int unsigned    |
| `created_at`            | timestamp       |
## روابط

| رابطه                     | جدول                  | ستون کلید خارجی   |
| :------------------------ | :-------------------- | :---------------- |
| متعلق است به (belongs to) | `hotel_reserve_rooms` | `reserve_room_id` |
