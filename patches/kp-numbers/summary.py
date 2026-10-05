import json, sys, collections

# Сводка по report.jsonl: категория → сколько раз, сколько КП, свежие/старые, примеры
path = sys.argv[1]
since = sys.argv[2] if len(sys.argv) > 2 else '2025-06-01'
rows = [json.loads(l) for l in open(path, encoding='utf-8') if l.strip()]

errors = [r for r in rows if r.get('error')]
print(f'прогонов: {len(rows)}, с ошибкой выполнения: {len(errors)}')
for r in errors[:15]:
    print('  ОШИБКА', r['pid'], r['template'], r.get('number'), r['error'][:300])

cat = collections.OrderedDict()
for r in rows:
    fresh = (r.get('created') or '') >= since
    key_kp = f"{r.get('number')} ред.{r.get('iteration')}"
    for f in r['findings']:
        # находки «КП» одинаковы в обоих шаблонах — считаем один раз
        if f['area'] == 'КП' and r['template'] != 'default':
            continue
        k = (f['area'], f['code'])
        c = cat.setdefault(k, {'n': 0, 'kp': set(), 'kp_fresh': set(), 'ex': []})
        c['n'] += 1
        c['kp'].add(key_kp)
        if fresh:
            c['kp_fresh'].add(key_kp)
        if len(c['ex']) < 3:
            c['ex'].append(f"{key_kp}: {f['where']} — ожид. {f['expected']}, в документе {f['actual']}" + (f" ({f['note']})" if f['note'] else ''))

order = {'КП': 0, 'PDF': 1, 'PDF клиент': 2, 'Excel': 3, 'Excel клиент': 4}
print()
print(f'{"где":<13}| {"что":<55}| {"раз":>6} | {"КП":>4} | {"с " + since:>13}')
for (area, code), c in sorted(cat.items(), key=lambda kv: (order.get(kv[0][0], 9), -len(kv[1]['kp']))):
    print(f'{area:<13}| {code:<55}| {c["n"]:>6} | {len(c["kp"]):>4} | {len(c["kp_fresh"]):>13}')
if '--ex' in sys.argv:
    print()
    for (area, code), c in sorted(cat.items(), key=lambda kv: (order.get(kv[0][0], 9), -len(kv[1]['kp']))):
        print(f'[{area}] {code}')
        for e in c['ex']:
            print('     ', e)
