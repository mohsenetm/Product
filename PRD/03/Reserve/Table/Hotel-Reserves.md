# مستندات جدول `hotel_reserves`

## توضیحات

این جدول، رکورد مرکزی رزرواسیون است. هر سطر نشان‌دهنده یک رزرو مهمان در یک هتل است که شامل جزئیات مهمان، تاریخ‌ها، مجموع قیمت‌ها و وضعیت فعلی چرخه حیات رزرو می‌شود.
فیلدهای قیمت در این جدول همیشه **مجموع** فیلدهای قیمت متناظر در جدول `hotel_reserve_rooms` هستند.

## تصمیمات طراحی

- ستون `unique_id` (۳۲ کاراکتری) **شناسه UNIQUE**  رزرو است که در جلوی رزرو تکراری را از کاربر می گیرد؛ `confirmation_code` به مهمانان نشان داده می‌شود؛ `provider_confirmation_code` شناسه مرجع تامین‌کننده (ارائه‌دهنده) خارجی است.
- ستون `status` ماشین حالت (state machine) جریان کاری رزرو را هدایت می‌کند (نمودار زیر را ببینید). این وضعیت همیشه باید رو به جلو (یا به سمت وضعیت‌های خاص خطا/لغو) پیش برود.
- ستون `is_worthless` رزروهای  بی‌ارزش (junk) را مشخص می‌کند رزروهایی که از سمت کالسنتر پیگیری شده و ارزش پیگیری کردن دیگر ندارند..
- ستون `expired_at` شامل یک مهلت زمانی برای کاربر است که در این زمان کاربر زمان برای پرداخت دارد.
- قانون `onDelete('restrict')` روی `hotel_id` و `provider_id` از حذف تصادفی یک هتل یا ارائه‌دهنده‌ای که دارای رزروهای فعال است، جلوگیری می‌کند.

## ماشین حالت وضعیت (Status State Machine)

![[tables/mermaid-diagram-2026-05-26-120552.png]]
## ستون‌ها

| نام ستون                     | نوع (Type)       |
| ---------------------------- | ---------------- |
| `id`                         | bigint unsigned  |
| `member_id`                  | bigint unsigned  |
| `hotel_id`                   | bigint unsigned  |
| `provider_id`                | bigint unsigned  |
| `unique_id`                  | varchar(32)      |
| `confirmation_code`          | varchar(255)     |
| `provider_confirmation_code` | varchar(255)     |
| `user_ip`                    | varchar(45)      |
| `check_in`                   | date             |
| `check_out`                  | date             |
| `number_of_nights`           | tinyint unsigned |
| `first_name`                 | varchar(255)     |
| `last_name`                  | varchar(255)     |
| `phone`                      | varchar(255)     |
| `email`                      | varchar(255)     |
| `status`                     | enum             |
| `is_worthless`               | tinyint(1)       |
| `price`                      | bigint unsigned  |
| `purchase_price`             | bigint unsigned  |
| `sales_price`                | bigint unsigned  |
| `board_price`                | bigint unsigned  |
| `discount`                   | bigint unsigned  |
| `expired_at`                 | timestamp        |
| `created_at`                 | timestamp        |
| `updated_at`                 | timestamp        |

## روابط

| رابطه                     | جدول                                  | ستون کلید خارجی |
| ------------------------- | ------------------------------------- | --------------- |
| دارای چند (has many)      | `hotel_reserve_snapshots`             | `reserve_id`    |
| دارای چند (has many)      | `hotel_reserve_provider_transactions` | `reserve_id`    |
| دارای چند (has many)      | `hotel_reserve_activities`            | `reserve_id`    |
| دارای چند (has many)      | `hotel_reserve_rooms`                 | `reserve_id`    |
| متعلق است به (belongs to) | `hotels`                              | `hotel_id`      |
| متعلق است به (belongs to) | `providers`                           | `provider_id`   |