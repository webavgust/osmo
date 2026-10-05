import json, sys, re, collections

# Уточнённая сводка: категория + место в документе (без номеров строк и вариантов)
path = sys.argv[1]
since = sys.argv[2] if len(sys.argv) > 2 else '2025-06-01'
only = sys.argv[3] if len(sys.argv) > 3 else None
rows = [json.loads(l) for l in open(path, encoding='utf-8') if l.strip()]


def place(f):
    w = f['where']
    w = re.sub(r'вариант \d+ ?', '', w)
    w = re.sub(r'№\d+', '', w)
    w = re.sub(r'строка \d+: ', '', w)
    w = re.sub(r'(сценарий|работа|platform|neuro|work|soft|доплата) \d+', r'\1', w)
    w = re.sub(r'сводная «(Платформа аналитики данных|ИИ агент)»', 'сводная «модуль»', w)
    w = re.sub(r'сводная «(?!ИТОГО|ПРОГРАММНОЕ|РАБОТЫ|Модули|Конфигурация|модуль)[^»]*»', 'сводная «группа работ»', w)
    w = re.sub(r'«(?!ИТОГО|ПРОГРАММНОЕ|РАБОТЫ|Модули|Конфигурация|модуль|группа работ|ПО:|УСЛУГИ:)[^»]*»', '«…»', w)
    return w.strip()


cat = {}
for r in rows:
    fresh = (r.get('created') or '') >= since
    kp = f"{r.get('number')} ред.{r.get('iteration')}"
    for f in r['findings']:
        if f['area'] == 'КП' and r['template'] != 'default':
            continue
        if only and only not in f['area'] + ' ' + f['code']:
            continue
        k = (f['area'], f['code'], place(f))
        c = cat.setdefault(k, {'n': 0, 'kp': set(), 'fresh': set(), 'ex': []})
        c['n'] += 1
        c['kp'].add(kp)
        if fresh:
            c['fresh'].add(kp)
        if len(c['ex']) < 2:
            c['ex'].append(f"{kp} ({r.get('created', '')[:10]}, {r.get('currency')}): {f['where']} — ожид. {f['expected']}, в документе {f['actual']}" + (f" [{f['note']}]" if f['note'] else ''))

order = {'КП': 0, 'PDF': 1, 'PDF клиент': 2, 'Excel': 3, 'Excel клиент': 4}
for k, c in sorted(cat.items(), key=lambda kv: (order.get(kv[0][0], 9), kv[0][1], -len(kv[1]['kp']))):
    print(f"{k[0]:<12}| {k[1]:<48}| {k[2]:<60}| раз {c['n']:>4} | КП {len(c['kp']):>3} | свежих {len(c['fresh']):>3}")
    if '--ex' in sys.argv:
        for e in c['ex']:
            print('        ', e)
