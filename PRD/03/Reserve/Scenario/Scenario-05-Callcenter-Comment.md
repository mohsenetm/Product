# سناریو ۰۵ — ثبت کامنت توسط کال‌سنتر (بدون تغییر وضعیت)

## خلاصه

یک اپراتور روی یک رزرو موجود کامنت ثبت می‌کند — برای مثال، ثبت تلاش ناموفق برای تماس. وضعیت رزرو **تغییر نمی‌کند**. هیچ اسنپ‌شاتی ایجاد نمی‌شود.

**رزرو:** هر وضعیتی · کال‌سنتر یادداشت اطلاعاتی اضافه می‌کند

---

## چه زمانی از این الگو استفاده کنیم

از یک فعالیت از نوع `comment` (با `snapshot_id = NULL`) در موارد زیر استفاده کنید:

- یک اپراتور تلاش می‌کند با مهمان تماس بگیرد اما کسی جواب نمی‌دهد.
- یادداشتی درباره نیازهای خاص مهمان اضافه می‌شود.
- یک ارتباط داخلی تیمی روی رزرو ثبت می‌شود.
- تاخیر تامین‌کننده بدون نیاز به تغییر وضعیت یادداشت می‌شود.

---

## جریان داده — گام به گام

### گام ۱ — اپراتور یک قالب از منوی کشویی انتخاب می‌کند

رابط کاربری `hotel_reserve_activity_templates` را با `is_active = true` و مرتب‌سازی بر اساس `order ASC` نمایش می‌دهد. اپراتور *"جوابی از مهمان نیامد"* (template id = 1) را انتخاب می‌کند.

---

### گام ۲ — فقط ردیف فعالیت درج می‌شود

```sql
-- هیچ تغییری در hotel_reserves نیست
-- هیچ ردیف جدیدی در hotel_reserve_snapshots نیست
-- فقط یک فعالیت درج می‌شود

INSERT INTO hotel_reserve_activities (
    reserve_id, snapshot_id, users_id, template_id,
    type, title, description
) VALUES (
    1001,
    NULL,           -- NULL چون وضعیت رزرو تغییر نکرده
    7,              -- اپراتوری که یادداشت را ثبت کرده
    1,              -- قالب: "No answer from guest"
    'comment',
    'No answer from guest',
    'Called 09121234567 at 14:32. No answer. Will retry in 30 minutes.'
);
```

---

### چه اتفاقی نمی‌افتد

| جدول                                     | تغییر؟ |
|------------------------------------------|--------|
| `hotel_reserves`                         | ❌ دست نخورده |
| `hotel_reserve_snapshots`                | ❌ ردیف جدیدی نیست |
| `hotel_reserve_provider_transactions`    | ❌ دست نخورده |
| `hotel_reserve_rooms`                    | ❌ دست نخورده |
| `hotel_reserve_activities`               | ✅ ۱ ردیف درج شد |

---

## چندین تلاش کامنت (همان رزرو)

اگر اپراتور دوباره زنگ بزند و باز هم کسی جواب ندهد، هر تلاش به عنوان یک ردیف فعالیت جداگانه ثبت می‌شود:

```sql
-- تلاش دوم
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (1001, NULL, 7, 1, 'comment', 'No answer from guest', 'Called again at 15:05. Still no answer.');

-- تلاش سوم — اپراتور متفاوتی رزرو را پیگیری می‌کند
INSERT INTO hotel_reserve_activities (reserve_id, snapshot_id, users_id, template_id, type, title, description)
VALUES (1001, NULL, 9, 1, 'comment', 'No answer from guest', 'Third attempt at 16:20. Guest answered briefly then call dropped.');
```

---

## نکات

- `type = 'comment'` یک یادداشت فقط‌خواندنی است — **هیچ اختیاری برای تغییر وضعیت رزرو ندارد**.
- `snapshot_id = NULL` نشانه‌ای است که این فعالیت وضعیت رزرو را تغییر نداده.
- استفاده از `template_id` اختیاری است؛ اگر اپراتور یادداشت آزاد بنویسد، `template_id` برابر NULL است.