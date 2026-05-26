# سناریو ۰۲ — رد شدن رزرو توسط تامین‌کننده

## خلاصه

سیستم درخواست رزرو را به تامین‌کننده ارسال می‌کند. تامین‌کننده آن را رد می‌کند. سیستم رزرو را به وضعیت `rejected_booking` تغییر می‌دهد و یک اپراتور کال‌سنتر آن را بررسی می‌کند.

**رزرو:** ۱ اتاق · ۲ شب · تامین‌کننده شماره ۲ (اصلی)

---

## جریان داده — گام به گام

### گام ۱ — مهمان درخواست رزرو ثبت می‌کند

*(مشابه سناریو ۰۱ گام ۱ — رزرو با وضعیت `created` ایجاد می‌شود)*

```sql
INSERT INTO hotel_reserves (..., status) VALUES (..., 'created');
-- hotel_reserves.id = 1002

INSERT INTO hotel_reserve_rooms (...) VALUES (...);
-- hotel_reserve_rooms.id = 203

INSERT INTO hotel_reserve_room_availabilities (...) VALUES (...), (...); -- 2 شب

INSERT INTO hotel_reserve_snapshots (..., status) VALUES (..., 'created');
-- hotel_reserve_snapshots.id = 5010
```

---

### گام ۲ — سیستم درخواست رزرو ارسال می‌کند

```sql
UPDATE hotel_reserves
SET status = 'request_booking'
WHERE id = 1002;

INSERT INTO hotel_reserve_snapshots (..., status)
VALUES (..., 'request_booking');
-- hotel_reserve_snapshots.id = 5011
```

---

### گام ۳ — تامین‌کننده درخواست را رد می‌کند

```sql
-- 3a. بروزرسانی رزرو به وضعیت رد شده
UPDATE hotel_reserves
SET status = 'rejected_booking'
WHERE id = 1002;

-- 3b. اسنپ‌شات جدید
INSERT INTO hotel_reserve_snapshots (..., status)
VALUES (..., 'rejected_booking');
-- hotel_reserve_snapshots.id = 5012

-- 3c. ثبت پاسخ رد درخواست
INSERT INTO hotel_reserve_provider_transactions (reserve_id, snapshot_id, provider_id, status, is_success, error_message, response_payload)
VALUES (
    1002, 5012, 2,
    'rejected',
    false,
    'No availability for room type DBL on 2026-06-10',
    '{"error":"ROOM_NOT_AVAILABLE","message":"No rooms of requested type available for selected dates"}'
);
```

---

### گام ۴ — کال‌سنتر بررسی می‌کند و کامنت ثبت می‌کند

```sql
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1002,
    NULL,           -- فقط کامنت، بدون تغییر وضعیت
    7,              -- شناسه اپراتور کال‌سنتر
    1,              -- قالب: "Provider rejection received"
    'comment',
    'Booking rejected by provider',
    'Provider GRS returned ROOM_NOT_AVAILABLE for dates 2026-06-10 to 2026-06-12. Guest will be contacted.'
);
```

## تعداد رکوردهای نهایی

| جدول                                  | ردیف |
| ------------------------------------- | ---- |
| `hotel_reserves`                      | ۱    |
| `hotel_reserve_rooms`                 | ۱    |
| `hotel_reserve_room_availabilities`   | ۲    |
| `hotel_reserve_snapshots`             | ۳    |
| `hotel_reserve_provider_transactions` | ۲    |
| `hotel_reserve_activities`            | ۱    |

## جدول زمانی وضعیت

| اسنپ‌شات | وضعیت              | توضیح                      |
| -------- | ------------------ | -------------------------- |
| 5010     | `created`          |                            |
| 5011     | `waiting_booking`  | ارسال به تامین‌کننده شماره |
| 5012     | `rejected_booking` | رد شده توسط تامین‌کننده    |
