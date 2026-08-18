-- Патч v16: пункт меню «Реестр лицензий» в разделе аналитики.
--
-- Таблицы меню и прав в проекте называются menu и access_rules — если у вас
-- иначе, поправьте имена. Пункты /analytics/discounts и /analytics/partners
-- добавлялись в патче v15, здесь только третий.

INSERT INTO menu (parent_id, name, url, icon, sort, active)
SELECT id, 'Реестр лицензий', '/analytics/licenses', 'fa-key', 300, 1
FROM menu WHERE url = '/analytics/discounts' LIMIT 1;

INSERT INTO access_rules (code, name, sort)
VALUES ('analytics.licenses', 'Аналитика: реестр лицензий', 300);
