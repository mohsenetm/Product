# سناریو ۰۱ — رزرو آنلاین موفق (مسیر کامل)

## خلاصه

مهمان یک اتاق هتل را  رزرو می‌کند. تامین‌کننده رزرو را می‌پذیرد و تایید می‌کند.

**رزرو:** ۲ اتاق · ۳ شب · ۱ تامین‌کننده
  
---  

## جریان داده — گام به گام

### گام ۱ — مهمان درخواست رزرو ثبت می‌کند

سیستم رزرو اصلی و تمام رکوردهای مرتبط با اتاق و موجودی را ایجاد می‌کند.

```sql  
-- 1a. ایجاد رزرو اصلی  
INSERT INTO hotel_reserves (  
    member_id, hotel_id, provider_id,    unique_id, confirmation_code, provider_confirmation_code,    user_ip, check_in, check_out, number_of_nights,    first_name, last_name, phone, email,    status, is_worthless,    price, purchase_price, sales_price, board_price, discount,    expired_at) VALUES (  
    101, 5, 2,    'RES20260526ABCD1234', 'CONF-8821', '',    '192.168.1.10', '2026-06-10', '2026-06-13', 3,    'Ali', 'Ahmadi', '09121234567', 'ali@example.com',    'created', false,    18000000, 15000000, 18000000, 3000000, 0,    '2026-05-26 20:00:00');  
-- hotel_reserves.id = 1001  
  
-- 1b. ایجاد ردیف‌های اتاق (۲ اتاق)  
INSERT INTO hotel_reserve_rooms (reserve_id, room_type_id, rate_plan_id, room_type_snapshot, rate_plan_snapshot, number_of_adults, number_of_children, price, purchase_price, sales_price, board_price, discount)  
VALUES  
    (1001, 12, 3, '{"id":12,"name":"Deluxe Double","bed":"double"}', '{"id":3,"name":"BB Plan"}', 2, 0, 9000000, 7500000, 9000000, 1500000, 0),    (1001, 12, 3, '{"id":12,"name":"Deluxe Double","bed":"double"}', '{"id":3,"name":"BB Plan"}', 2, 0, 9000000, 7500000, 9000000, 1500000, 0);-- hotel_reserve_rooms.id = 201, 202  
  
-- 1c. ایجاد اسنپ‌شات موجودی شبانه (۲ اتاق × ۳ شب = ۶ ردیف)  
INSERT INTO hotel_reserve_room_availabilities (reserve_room_id, available_day, inventory, rack_rate, daily_rate, sell_rate, reservation_state, available_type)  
VALUES  
    (201, '2026-06-10', 5, 3500000, 2500000, 3000000, 'online', 'normal'),    (201, '2026-06-11', 5, 3500000, 2500000, 3000000, 'online', 'normal'),    (201, '2026-06-12', 5, 3500000, 2500000, 3000000, 'online', 'normal'),    (202, '2026-06-10', 5, 3500000, 2500000, 3000000, 'online', 'normal'),    (202, '2026-06-11', 5, 3500000, 2500000, 3000000, 'online', 'normal'),    (202, '2026-06-12', 5, 3500000, 2500000, 3000000, 'online', 'normal');  
-- 1d. ایجاد اسنپ‌شات اول (وضعیت: created)  
INSERT INTO hotel_reserve_snapshots (reserve_id, check_in, check_out, number_of_nights, first_name, last_name, phone, email, status, is_worthless, price, purchase_price, sales_price, board_price, discount, expired_at)  
VALUES (1001, '2026-06-10', '2026-06-13', 3, 'Ali', 'Ahmadi', '09121234567', 'ali@example.com', 'created', false, 18000000, 15000000, 18000000, 3000000, 0, '2026-05-26 20:00:00');  
-- hotel_reserve_snapshots.id = 5001  
```  
  
---  

### گام ۲ — سیستم درخواست رزرو را به تامین‌کننده ارسال می‌کند

```sql  
-- 2a. بروزرسانی وضعیت رزرو  
UPDATE hotel_reserves  
SET status = 'request_booking'  
WHERE id = 1001;  
  
-- 2b. اسنپ‌شات جدید  
INSERT INTO hotel_reserve_snapshots (..., status)  
VALUES (..., 'request_booking');  
-- hotel_reserve_snapshots.id = 5002  
```  
  
---  

### گام ۳ — تامین‌کننده پاسخ می‌دهد: رزرو پذیرفته شد

```sql  
-- 3a. بروزرسانی رزرو  
UPDATE hotel_reserves  
SET status = 'booking', provider_confirmation_code = 'GRS-78821', expired_at = "2026-10-01"  
WHERE id = 1001;  
  
-- 3b. اسنپ‌شات جدید  
INSERT INTO hotel_reserve_snapshots (..., status)  
VALUES (..., 'booking');  
-- hotel_reserve_snapshots.id = 5003  
  
-- 3c. ثبت پاسخ تامین‌کننده  
INSERT INTO hotel_reserve_provider_transactions (reserve_id, snapshot_id, provider_id, status, provider_confirmation_code, is_success, response_payload)  
VALUES (  
    1001, 5003, 2,    'booked', 'GRS-78821',    true,    '{"code":"GRS-78821","status":"booked","message":"Booking successful"}');  
```  
  
---  

### گام ۴ — پرداخت انجام شد

```sql  
-- 4a. بروزرسانی رزرو  
UPDATE hotel_reserves  
SET status = 'paid'  
WHERE id = 1001;  
  
-- 4b. اسنپ‌شات جدید  
INSERT INTO hotel_reserve_snapshots (..., status)  
VALUES (..., 'paid');  
-- hotel_reserve_snapshots.id = 5004  
```  
  
---  

### گام ۵ — تامین‌کننده تایید نهایی می‌کند (Webhook یا Polling)

```sql  
-- 5a. بروزرسانی رزرو  
UPDATE hotel_reserves  
SET status = 'confirmed'  
WHERE id = 1001;  
  
-- 5b. اسنپ‌شات جدید  
INSERT INTO hotel_reserve_snapshots (..., status)  
VALUES (..., 'confirmed');  
-- hotel_reserve_snapshots.id = 5005  
  
-- 5c. ثبت تراکنش تایید  
INSERT INTO hotel_reserve_provider_transactions (reserve_id, snapshot_id, provider_id, status, provider_confirmation_code, is_success, response_payload)  
VALUES (  
    1001, 5005, 2,    'confirmed', 'GRS-78821',    true,    '{"code":"GRS-78821","status":"confirmed"}');  
```  
  
---  

## تعداد رکوردهای نهایی

| جدول                                   | ردیف |  
|----------------------------------------|------|  
| `hotel_reserves`                       | ۱    |  
| `hotel_reserve_rooms`                  | ۲    |  
| `hotel_reserve_room_availabilities`    | ۶    |  
| `hotel_reserve_snapshots`              | ۵    |  
| `hotel_reserve_provider_transactions`  | ۲    |  
| `hotel_reserve_activities`             | ۰    |  

## جدول زمانی وضعیت

| اسنپ‌شات | وضعیت             |     |
| -------- | ----------------- | --- |
| 5001     | `created`         |     |
| 5002     | `request_booking` |     |
| 5003     | `booking`         |     |
| 5004     | `paid`            |     |
| 5005     | `confirmed`       |     |
