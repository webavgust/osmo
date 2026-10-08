-- Зеркало Битрикс24 (avgbitrix): новые поля BI-коннектора, 08.10.2026.
-- Синхронизация отказывала: «не хватает полей на локали».
-- contacts — json, как contact_ids: JSON-колонки синхронизация пропускает,
-- поэтому массив из Trino не ломает выгрузку приведением к varchar.
ALTER TABLE crm_company
    ADD COLUMN category_id varchar(200) DEFAULT NULL AFTER company_type,
    ADD COLUMN category_name varchar(200) DEFAULT NULL AFTER category_id,
    ADD COLUMN category varchar(200) DEFAULT NULL AFTER category_name;
ALTER TABLE crm_deal
    ADD COLUMN type_name varchar(200) DEFAULT NULL AFTER type_id,
    ADD COLUMN contacts json DEFAULT NULL AFTER contact_ids;
