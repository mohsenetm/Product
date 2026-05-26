# سناریو ۰۴ — لغو رزرو توسط مهمان (پس از تایید)

## خلاصه

یک رزرو تایید شده به درخواست مهمان لغو می‌شود. اپراتور کال‌سنتر اقدام را ثبت می‌کند، با تامین‌کننده لغو را انجام می‌دهد و وضعیت رزرو را بروز می‌کند.

**رزرو:** ۱ اتاق · ۳ شب · تایید شده → لغو شده

---

## جریان داده — گام به گام

### گام ۱ — رزرو از قبل تایید شده است

*(رزرو از مراحل سناریو ۰۱ به `status = confirmed` رسیده است)*

```
hotel_reserves.id = 1004
hotel_reserve_snapshots: ... → 5030 (confirmed)
```

---

### گام ۲ — کال‌سنتر درخواست لغو را دریافت می‌کند

```sql
-- 2a. ثبت درخواست ورودی به عنوان کامنت (هنوز تغییر وضعیت نداده‌ایم)
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1004,
    NULL,
    8,              -- شناسه اپراتور
    2,              -- قالب: "Guest requested cancellation"
    'comment',
    'Cancellation request received',
    'Guest called to cancel reservation RES20260526ABCD1004. Reason: change of plans. Checking cancellation policy.'
);
```

---

### گام ۳ — سیستم درخواست لغو را به تامین‌کننده ارسال می‌کند

```sql
-- ثبت درخواست لغو به تامین‌کننده
INSERT INTO hotel_reserve_provider_transactions (reserve_id, snapshot_id, provider_id, status, is_success, request_payload)
VALUES (
    1004, NULL, 2,
    'cancel_requested',
    true,
    '{"action":"cancel","confirmation_code":"GRS-78821"}'
);
-- hotel_reserve_provider_transactions.id = 3030
```

---

### گام ۴ — تامین‌کننده لغو را تایید می‌کند

```sql
-- 4a. ثبت پاسخ تامین‌کننده
INSERT INTO hotel_reserve_provider_transactions (reserve_id, snapshot_id, provider_id, status, provider_confirmation_code, is_success, response_payload)
VALUES (
    1004, NULL, 2,
    'cancelled', 'GRS-78821-CNX',
    true,
    '{"status":"cancelled","cancellation_code":"GRS-78821-CNX","refund_amount":18000000}'
);
-- hotel_reserve_provider_transactions.id = 3031

-- 4b. بروزرسانی رزرو
UPDATE hotel_reserves
SET status = 'cancelled'
WHERE id = 1004;

-- 4c. ایجاد اسنپ‌شات
INSERT INTO hotel_reserve_snapshots (..., status)
VALUES (..., 'cancelled');
-- hotel_reserve_snapshots.id = 5032

-- 4d. لینک کردن اسنپ‌شات به تراکنش
UPDATE hotel_reserve_provider_transactions SET snapshot_id = 5032 WHERE id = 3031;

-- 4e. ثبت اقدام لغو
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1004, 5032, 8, 2,
    'action',
    'Reservation cancelled',
    'Cancelled per guest request. Provider cancellation code: GRS-78821-CNX. Full refund of 18,000,000 Rials initiated.'
);
```

---

## تعداد رکوردهای نهایی

| جدول                                   | ردیف |
|----------------------------------------|------|
| `hotel_reserves`                       | ۱    |
| `hotel_reserve_rooms`                  | ۱    |
| `hotel_reserve_room_availabilities`    | ۳    |
| `hotel_reserve_snapshots`              | ۷    |
| `hotel_reserve_provider_transactions`  | ۴    |
| `hotel_reserve_activities`             | ۲    |

## جدول زمانی وضعیت

| اسنپ‌شات | وضعیت       | رویداد                              |
|----------|-------------|-------------------------------------|
| ...      | `confirmed` | جریان تایید قبلی                    |
| 5032     | `cancelled` | تامین‌کننده لغو را تایید کرد        |