-- Патч v20: почин статусов у КП, прикреплённых к спецификациям.
--
-- Прикрепление КП к спецификации переводит его в «Выиграно», но у записей,
-- прикреплённых до этого патча, статус мог остаться старым: условие в win()
-- отбирало только in_work / frozen, а в базе встречались значения прежней
-- схемы (sent, negotiation). В скоринге такие КП давали 0 выигранных.
--
-- Проигранные и отменённые не трогаем: сделка могла закрыться и мимо этой
-- спецификации, перебивать явное решение менеджера нельзя.

UPDATE proposals p
SET p.status = 'won',
    p.status_changed_at = NOW()
WHERE p.`group` IN (SELECT proposal_group FROM contract_specification_proposals)
  AND p.status NOT IN ('won', 'lost', 'canceled');

-- Проверка: должно вернуть 0 строк
-- SELECT p.status, COUNT(*) FROM proposals p
-- WHERE p.`group` IN (SELECT proposal_group FROM contract_specification_proposals)
--   AND p.status NOT IN ('won','lost','canceled')
-- GROUP BY p.status;
