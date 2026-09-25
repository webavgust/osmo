"""Сводка метрик аудита по всем страницам: ошибки, контраст, значки, кегли."""
import json, sys, collections

f = sys.argv[1] if len(sys.argv) > 1 else 'out/metrics-light.json'
d = json.load(open(f, encoding='utf-8'))

print('== Ошибки по страницам')
for k, v in d.items():
    errs = [e for e in v.get('errors', []) if 'access control checks' not in e]
    if errs or v.get('error'):
        print(' ', k, v.get('error', ''), errs[:4])

print('\n== Выход за экран / битые картинки / пустые значки')
for k, v in d.items():
    if v.get('outside') or v.get('brokenImg') or v.get('emptyIcons'):
        print(' ', k, 'outside', v.get('outside')[:3], 'img', v.get('brokenImg'), 'icons', v.get('emptyIcons')[:4])

lc = collections.Counter(); lc_ex = {}
for k, v in d.items():
    for x in v.get('lowContrast', []):
        key = (x['fg'], x['bg'])
        lc[key] += x['n']
        lc_ex.setdefault(key, (k, x['txt'], x['ratio'], x['size'], x['path']))
print('\n== Низкий контраст: пары цветов (сумма по страницам)')
for key, n in lc.most_common(25):
    k, t, r, s, p = lc_ex[key]
    print(f'  {n:5d}  {key[0]} на {key[1]}  {r}:1  {s}px  [{k}] «{t}»  {p}')

agg = lambda field: sum((collections.Counter(dict(v.get(field, []))) for v in d.values()), collections.Counter())
print('\n== Стили значков', agg('iconStyles').most_common())
print('\n== Кегли (все страницы)', agg('fontSizes').most_common(20))
print('\n== Насыщенность', agg('fontWeights').most_common())
print('\n== Скругления', agg('radii').most_common(15))
print('\n== Тени', agg('shadows').most_common(8))
print('\n== Кнопки', agg('btns').most_common(40))
print('\n== Бейджи', agg('badges').most_common(30))
print('\n== Заголовки страниц')
for k, v in d.items():
    hs = v.get('heads', [])[:4]
    print(' ', k, ' | '.join(f"{h['tag']} {h['size']}/{h['weight']} «{h['txt'][:24]}»" for h in hs))
print('\n== Мелкий текст < 11px')
for k, v in d.items():
    if v.get('tinyCount'):
        print(' ', k, v['tinyCount'], [(t['txt'][:20], t['size']) for t in v['tiny'][:3]])
