-- ---------------------------------------------------------------------------
-- patch v23 — разовое сопоставление партнёров портала с компаниями Битрикс24
--
-- Кладём пары, у которых названия совпадают точно (без учёта регистра и
-- обрамляющих пробелов). Остальные партнёры сопоставляются руками в форме
-- редактирования: /partners/edit/{id}, поле «Компании в Битрикс24».
--
-- Запускать, ПОДКЛЮЧИВШИСЬ К БАЗЕ ПОРТАЛА (avgmom локально), — таблицы
-- партнёров не квалифицированы именем базы намеренно, а зеркало Битрикса
-- всегда зовётся avgbitrix (config/database.php, соединение bitrix).
--
-- COLLATE utf8mb4_unicode_ci обязателен: у avgmom коллация utf8mb4_unicode_ci,
-- у avgbitrix — utf8mb4_0900_ai_ci, без приведения MySQL ругается
-- «Illegal mix of collations».
--
-- Запрос идемпотентный: NOT EXISTS пропускает уже сопоставленные компании,
-- MIN(p.id) + GROUP BY не дают двум одноимённым партнёрам подраться за одну
-- компанию (UNIQUE по crm_company_id всё равно бы это не пустил).
-- Повторный запуск ничего не добавляет и ничего не ломает.
-- ---------------------------------------------------------------------------

INSERT INTO partner_crm_companies (partner_id, crm_company_id, created_at)
SELECT MIN(p.id), c.id, NOW()
FROM partners p
         JOIN avgbitrix.crm_company c
              ON LOWER(TRIM(c.title)) COLLATE utf8mb4_unicode_ci
                     = LOWER(TRIM(p.name)) COLLATE utf8mb4_unicode_ci
WHERE NOT EXISTS (SELECT 1
                  FROM partner_crm_companies l
                  WHERE l.crm_company_id = c.id)
GROUP BY c.id;

-- Проверка: сколько партнёров сопоставлено и сколько осталось
-- SELECT COUNT(DISTINCT partner_id) AS partners_matched, COUNT(*) AS links FROM partner_crm_companies;
-- SELECT p.id, p.name FROM partners p
--   LEFT JOIN partner_crm_companies l ON l.partner_id = p.id
--   WHERE l.id IS NULL ORDER BY p.name;
