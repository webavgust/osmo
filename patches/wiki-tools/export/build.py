# Сборка архива WIKI для передачи специалисту, который разворачивает вики: страницы docs/wiki,
# использованные кадры, просмотрщик index.html, а также _sidebar.md и DEPLOY.md из этой папки.
# Служебные _GUIDE.md, _TODO.md и img/shots/ в архив не попадают. Битые ссылки или кадры — архив
# не собирается.
# Запуск из корня проекта: python patches/wiki-tools/export/build.py storage/app/wiki-export/osmo-wiki-ГГГГ-ММ-ДД.zip
import pathlib
import re
import shutil
import sys
import tempfile
import zipfile

sys.stdout.reconfigure(encoding="utf-8")

HERE = pathlib.Path(__file__).resolve().parent
SRC = HERE.parents[2] / "docs" / "wiki"
OUT = pathlib.Path(sys.argv[1]).resolve()
STAGE = pathlib.Path(tempfile.mkdtemp(prefix="osmo-wiki-")) / "osmo-wiki"

pages = ["README.md"] + sorted(p.name for p in SRC.glob("[0-1][0-9]-*.md"))

(STAGE / "img").mkdir(parents=True)
for name in pages:
    shutil.copy2(SRC / name, STAGE / name)
shutil.copy2(SRC / "index.html", STAGE / "index.html")
shutil.copy2(HERE / "_sidebar.md", STAGE / "_sidebar.md")
shutil.copy2(HERE / "DEPLOY.md", STAGE / "DEPLOY.md")

used = set()
for name in pages:
    used |= set(re.findall(r"img/([a-z0-9-]+\.png)", (STAGE / name).read_text(encoding="utf-8")))
for image in sorted(used):
    shutil.copy2(SRC / "img" / image, STAGE / "img" / image)

problems = []
for page in STAGE.glob("*.md"):
    text = page.read_text(encoding="utf-8")
    for match in re.finditer(r"\]\(([^)#\s]+\.md)(?:#[^)]*)?\)|href=\"([^\"#]+\.md)\"", text):
        target = match.group(1) or match.group(2)
        if not (STAGE / target).exists():
            problems.append(f"{page.name}: ссылка на отсутствующий {target}")
    for image in re.findall(r"img/([a-z0-9-]+\.png)", text):
        if not (STAGE / "img" / image).exists():
            problems.append(f"{page.name}: нет кадра img/{image}")
    if page.name != "DEPLOY.md":
        for marker in ("_GUIDE", "_TODO", "shots/", "patches/", "localhost:8123"):
            if marker in text:
                problems.append(f"{page.name}: служебная ссылка «{marker}»")

print(f"страниц: {len(pages)}, кадров: {len(used)}")
if problems:
    print("\n".join(problems))
    shutil.rmtree(STAGE.parent)
    sys.exit(1)

OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.unlink(missing_ok=True)
with zipfile.ZipFile(OUT, "w", zipfile.ZIP_DEFLATED) as archive:
    for file in sorted(STAGE.rglob("*")):
        if file.is_file():
            archive.write(file, pathlib.Path("osmo-wiki") / file.relative_to(STAGE))
shutil.rmtree(STAGE.parent)

with zipfile.ZipFile(OUT) as archive:
    broken = archive.testzip()
    count = len(archive.namelist())
size = OUT.stat().st_size / 1024 / 1024
print(f"архив: {OUT} — {size:.1f} МБ, файлов: {count}, повреждений: {broken or 'нет'}")
