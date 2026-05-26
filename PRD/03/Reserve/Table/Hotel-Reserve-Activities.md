# مستندات جدول `hotel_reserve_activities`

## توضیحات

یک ردپای حسابرسی (Audit Trail) **فقط-افزودنی (Append-only)** از هر اقدام (action) یا یادداشتی (comment) که یک اپراتور مرکز تماس روی یک رزرو انجام می‌دهد.  
هر سطر ثبت می‌کند که *چه کسی، چه کاری را در چه زمانی* انجام داده است، و — اگر آن اقدام باعث تغییر در رزرو شده باشد — *کدام اسنپ‌شات (snapshot) آن تغییر را ثبت کرده است*.

## تصمیمات طراحی

- **فقط-افزودنی (Append-only)** — فقط دارای `created_at` (که از طریق `useCurrent()` تنظیم می‌شود) است و ستون `updated_at` ندارد. فعالیت‌ها هرگز ویرایش یا حذف نمی‌شوند.
- مقدار `type = 'comment'` نشان‌دهنده یک یادداشت فقط-خواندنی است که جدول `hotel_reserves` را تغییر **نمی‌دهد**. در این حالت مقدار `snapshot_id` برابر با `NULL` خواهد بود.
- مقدار `type = 'action'` وضعیت رزرو را تغییر می‌دهد (Mutate). پس از بروزرسانی `hotel_reserves` و درج یک سطر جدید در `hotel_reserve_snapshots`، شناسه (`id`) اسنپ‌شات جدید در ستون `snapshot_id` نوشته می‌شود.
- ستون `template_id` می‌تواند خالی (nullable) باشد و از `onDelete('set null')` استفاده می‌کند — با حذف یک قالب (Template)، رکوردهای تاریخچه فعالیت‌ها حفظ می‌شوند.
- ستون `users_id` از `onDelete('restrict')` استفاده می‌کند — کاربری که در سیستم فعالیتی ثبت کرده است را نمی‌توان به طور کامل (Hard-delete) حذف کرد.

## ستون‌ها

| نام ستون      | نوع داده        |
| :------------ | :-------------- |
| `id`          | bigint unsigned |
| `reserve_id`  | bigint unsigned |
| `snapshot_id` | bigint unsigned |
| `users_id`    | bigint unsigned |
| `template_id` | bigint unsigned |
| `type`        | enum            |
| `title`       | varchar(255)    |
| `description` | text            |
| `created_at`  | timestamp       |
## روابط

| رابطه                 | جدول                               | ستون کلید خارجی |
| :-------------------- | :--------------------------------- | :-------------- |
| belongs to (متعلق به) | `hotel_reserves`                   | `reserve_id`    |
| belongs to (متعلق به) | `hotel_reserve_snapshots`          | `snapshot_id`   |
| belongs to (متعلق به) | `users`                            | `users_id`      |
| belongs to (متعلق به) | `hotel_reserve_activity_templates` | `template_id`   |