# سناریو ۰۶ — تغییر وضعیت رزرو توسط کال‌سنتر (اقدام)

## خلاصه

یک اپراتور کال‌سنتر مستقیماً وضعیت یک رزرو را تغییر می‌دهد — برای مثال، لغو آن یا علامت‌گذاری به عنوان بی‌ارزش. این یک فعالیت از نوع `action` است که `hotel_reserves` را **تغییر می‌دهد** و **یک اسنپ‌شات جدید ایجاد می‌کند**.

**رزرو:** `confirmed` → `cancelled` (توسط اپراتور، نه تامین‌کننده)

---

## تفاوت با لغو توسط تامین‌کننده

| جنبه                       | سناریو ۰۴ (لغو تامین‌کننده)     | این سناریو (اقدام اپراتور)         |
|----------------------------|---------------------------------|------------------------------------|
| چه کسی لغو را آغاز می‌کند؟ | درخواست مهمان → API تامین‌کننده | اپراتور کال‌سنتر                   |
| API تامین‌کننده فراخوانی می‌شود؟ | بله                        | نه لزوماً                          |
| `provider_transactions`    | ردیف جدید ثبت می‌شود           | ردیف جدیدی نیست (مگر API فراخوانی شود) |
| `activities`               | ردیف جدید                      | ردیف جدید                          |

---

## جریان داده — گام به گام

### گام ۱ — اپراتور تصمیم می‌گیرد رزرو را لغو کند

رابط کاربری منوی کشویی با قالب‌های نوع `action` نمایش می‌دهد. اپراتور *"لغو توسط کال‌سنتر"* (template id = 5) را انتخاب می‌کند.

---

### گام ۲ — بروزرسانی رزرو

```sql
UPDATE hotel_reserves
SET status = 'cancelled'
WHERE id = 1005;
```

---

### گام ۳ — ایجاد اسنپ‌شات

```sql
INSERT INTO hotel_reserve_snapshots (
    reserve_id, check_in, check_out, number_of_nights,
    first_name, last_name, phone, email,
    status, is_worthless,
    price, purchase_price, sales_price, board_price, discount, expired_at
)
SELECT
    id, check_in, check_out, number_of_nights,
    first_name, last_name, phone, email,
    status, is_worthless,
    price, purchase_price, sales_price, board_price, discount, expired_at
FROM hotel_reserves
WHERE id = 1005;
-- hotel_reserve_snapshots.id = 5040
```

---

### گام ۴ — ثبت فعالیت با لینک اسنپ‌شات

```sql
INSERT INTO hotel_reserve_activities (
    reserve_id, snapshot_id, users_id, template_id,
    type, title, description
) VALUES (
    1005,
    5040,           -- لینک به اسنپ‌شات ایجاد شده در گام ۳
    8,
    5,              -- قالب: "Cancel by call center"
    'action',
    'Reservation cancelled by call center',
    'Guest confirmed cancellation verbally. Refund of 12,000,000 Rials processed via payment gateway ref #PAY-9912.'
);
```

---

### حالت جایگزین: علامت‌گذاری رزرو به عنوان بی‌ارزش

```sql
-- بروزرسانی
UPDATE hotel_reserves SET is_worthless = true WHERE id = 1006;

-- اسنپ‌شات
INSERT INTO hotel_reserve_snapshots (..., is_worthless) VALUES (..., true);
-- hotel_reserve_snapshots.id = 5041

-- فعالیت
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (
    1006, 5041, 8, NULL,
    'action',
    'Marked as worthless',
    'Reservation created during load testing. Marked worthless to exclude from reports.'
);
```
