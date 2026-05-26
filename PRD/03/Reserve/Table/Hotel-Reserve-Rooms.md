# مستندات جدول `hotel_reserve_rooms`

## توضیحات

این جدول سطرهای (رکوردهای) مجزای اتاق‌ها را در یک رزرو ذخیره می‌کند. یک سطر `hotel_reserves` می‌تواند **یک یا چند** سطر اتاق داشته باشد که هر کدام نوع اتاق (room type)، برنامه نرخی (rate plan)، تعداد مهمانان و قیمت‌گذاری خاص خود را دارند.

فیلدهای قیمت در این جدول منبع اصلی حقیقت (source of truth) هستند؛ فیلدهای متناظر در `hotel_reserves` باید برابر با مجموع آن‌ها باشد:

$$ \text{hotel\_reserves.price} = \sum(\text{hotel\_reserve\_rooms.price}) $$

## تصمیمات طراحی

*   فیلدهای `room_type_snapshot` و `rate_plan_snapshot` داده‌های JSON هستند که **در لحظه ایجاد رزرو** از جداول `room_types` و `rate_plans` کپی می‌شوند. این کار باعث می‌شود دقیقاً همان پیکربندی که مهمان در زمان رزرو دیده است حفظ شود، حتی اگر داده‌های منبع بعداً تغییر کنند.
*   رفتار `onDelete('cascade')` روی `reserve_id` — با حذف یک رزرو، سطرهای اتاق مربوط به آن نیز به طور خودکار حذف می‌شوند.
*   رفتار `onDelete('restrict')` روی `room_type_id` و `rate_plan_id` — از حذف نوع اتاق یا برنامه نرخی که در رزروهای فعال ارجاع داده شده‌اند جلوگیری می‌کند.
*   تمامی قیمت‌ها در **کوچک‌ترین واحد پولی** به صورت `unsignedBigInteger` ذخیره می‌شوند.

## ستون‌ها

| نام ستون             | نوع داده         |
| :------------------- | :--------------- |
| `id`                 | bigint unsigned  |
| `reserve_id`         | bigint unsigned  |
| `room_type_id`       | bigint unsigned  |
| `rate_plan_id`       | bigint unsigned  |
| `room_type_snapshot` | json             |
| `rate_plan_snapshot` | json             |
| `number_of_adults`   | tinyint unsigned |
| `number_of_children` | tinyint unsigned |
| `price`              | bigint unsigned  |
| `purchase_price`     | bigint unsigned  |
| `sales_price`        | bigint unsigned  |
| `board_price`        | bigint unsigned  |
| `discount`           | bigint unsigned  |
| `created_at`         | timestamp        |
| `updated_at`         | timestamp        |

## روابط

| رابطه                 | جدول                                | ستون کلید خارجی   |
| :-------------------- | :---------------------------------- | :---------------- |
| belongs to (متعلق به) | `hotel_reserves`                    | `reserve_id`      |
| belongs to (متعلق به) | `room_types`                        | `room_type_id`    |
| belongs to (متعلق به) | `rate_plans`                        | `rate_plan_id`    |
| has many (دارای چند)  | `hotel_reserve_room_availabilities` | `reserve_room_id` |