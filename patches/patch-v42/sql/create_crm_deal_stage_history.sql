-- patch v42: зеркало истории стадий сделок Битрикс24 (BI-коннектор, таблица crm_deal_stage_history).
-- База avgbitrix. Колонки повторяют Битрикс один в один: страница синхронизации строит SQL
-- для SQL Lab по локальным колонкам, лишняя колонка уронит запрос в Trino.
-- Наполняется так же, как crm_deal: SQL со страницы «Настройка CRM» → SQL Lab → вставка результата.
CREATE TABLE IF NOT EXISTS crm_deal_stage_history (
    id bigint NOT NULL,
    type_id int DEFAULT NULL,
    deal_id bigint DEFAULT NULL,
    date_create timestamp NULL DEFAULT NULL,
    start_date date DEFAULT NULL,
    end_date date DEFAULT NULL,
    assigned_by_id bigint DEFAULT NULL,
    assigned_by_name varchar(200) DEFAULT NULL,
    assigned_by varchar(200) DEFAULT NULL,
    assigned_by_department varchar(200) DEFAULT NULL,
    category_id bigint DEFAULT NULL,
    category_name varchar(200) DEFAULT NULL,
    category varchar(200) DEFAULT NULL,
    stage_semantic_id varchar(200) DEFAULT NULL,
    stage_semantic varchar(200) DEFAULT NULL,
    stage_id varchar(200) DEFAULT NULL,
    stage_name varchar(200) DEFAULT NULL,
    stage varchar(200) DEFAULT NULL,
    PRIMARY KEY (id),
    KEY deal_date (deal_id, date_create),
    KEY date_create (date_create)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
