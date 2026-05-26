# سناریو ۰۳ — اوربوکینگ پس از تایید

## خلاصه

تامین‌کننده ابتدا رزرو را تایید می‌کند. چند ساعت یا چند روز بعد، تامین‌کننده از طریق Webhook به سیستم اطلاع می‌دهد که اوربوکینگ رخ داده — هتل نمی‌تواند مهمان را پذیرا باشد. کال‌سنتر باید موضوع را مدیریت کند.

**رزرو:** ۱ اتاق · ۲ شب · تایید شده، سپس اوربوکینگ

---

## جریان داده — گام به گام

### گام ۱ — رزرو به وضعیت تایید شده می‌رسد

*(همان مراحل سناریو ۰۱ — رزرو به `status = confirmed` می‌رسد)*

```
hotel_reserves.id = 1003
hotel_reserve_snapshots: 5020 (created) → 5021 (waiting_booking) → 5022 (booking) → 5023 (paid) → 5024 (confirmed)
```

---

### گام ۲ — تامین‌کننده Webhook اوربوکینگ ارسال می‌کند

```sql
-- 2a. ثبت Webhook دریافتی
INSERT INTO hotel_reserve_provider_transactions (reserve_id, snapshot_id, provider_id, status, is_success, response_payload)
VALUES (
    1003,
    NULL,           -- snapshot_id پس از بروزرسانی زیر تعیین می‌شود
    2,
    'overbooking',
    false,
    '{"code":"GRS-78821","status":"overbooking","message":"Hotel cannot accommodate. Overbooking declared."}'
);
-- hotel_reserve_provider_transactions.id = 3020

-- 2b. بروزرسانی رزرو
UPDATE hotel_reserves
SET status = 'overbooking_confirmation'
WHERE id = 1003;

-- 2c. اسنپ‌شات جدید
INSERT INTO hotel_reserve_snapshots (..., status)
VALUES (..., 'overbooking_confirmation');
-- hotel_reserve_snapshots.id = 5025

-- 2d. لینک کردن اسنپ‌شات به تراکنش
UPDATE hotel_reserve_provider_transactions
SET snapshot_id = 5025
WHERE id = 3020;
```

---

### گام ۳ — کال‌سنتر اولین کامنت را ثبت می‌کند

```sql
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1003,
    NULL,           -- فقط کامنت، بدون تغییر وضعیت
    7,
    3,              -- قالب: "Provider overbooking notice"
    'comment',
    'Overbooking notice received from provider',
    'Webhook received from GRS. Hotel cannot accommodate guest. Searching for alternative.'
);
```

---

### گام ۴ — کال‌سنتر با مهمان تماس می‌گیرد و هتل جایگزین پیدا می‌کند

اپراتور با مهمان تماس می‌گیرد، موضوع را توضیح می‌دهد و یک هتل جایگزین پیشنهاد می‌کند.

```sql
-- 4a. ثبت تلاش برای تماس
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1003, NULL, 7, NULL,
    'comment',
    'Guest contacted - overbooking explained',
    'Spoke with Ali Ahmadi. Explained overbooking. Guest agreed to be moved to Hotel Laleh.'
);

-- 4b. اپراتور رزرو را لغو شده علامت‌گذاری می‌کند
UPDATE hotel_reserves
SET status = 'cancelled'
WHERE id = 1003;

INSERT INTO hotel_reserve_snapshots (..., status)
VALUES (..., 'cancelled');
-- hotel_reserve_snapshots.id = 5026

INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1003, 5026, 7, 5,
    'action',
    'Reservation cancelled - overbooking',
    'Original reservation cancelled. Guest relocated to Hotel Laleh (new reservation RES20260526WXYZ). Refund initiated.'
);
```

---

## تعداد رکوردهای نهایی

| جدول                                   | ردیف |
|----------------------------------------|------|
| `hotel_reserves`                       | ۱    |
| `hotel_reserve_rooms`                  | ۱    |
| `hotel_reserve_room_availabilities`    | ۲    |
| `hotel_reserve_snapshots`              | ۷    |
| `hotel_reserve_provider_transactions`  | ۴    |
| `hotel_reserve_activities`             | ۳    |

## جدول زمانی وضعیت

| اسنپ‌شات | وضعیت                       | رویداد                                        |
|----------|-----------------------------|-----------------------------------------------|
| 5020     | `created`                   | مهمان رزرو ثبت می‌کند                         |
| 5021     | `waiting_booking`           | سیستم به تامین‌کننده ارسال می‌کند             |
| 5022     | `booking`                   | تامین‌کننده رزرو را می‌پذیرد                  |
| 5023     | `paid`                      | پرداخت انجام شد                               |
| 5024     | `confirmed`                 | تایید نهایی تامین‌کننده                        |
| 5025     | `overbooking_confirmation`  | Webhook تامین‌کننده — اوربوکینگ               |
| 5026     | `cancelled`                 | اقدام کال‌سنتر — مهمان منتقل شد              |