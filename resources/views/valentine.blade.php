<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D карточка с изображением и наклоном</title>
    <style>
        @media (min-width: 600px) {
            .screen-lock {
                opacity: 0!important;
            }

            /* Скрываем скроллбар при заблокированном экране */
            body {
                overflow: hidden;
            }
        }
        @media (min-width: 500px) {
            .scene {
                margin-top: -10%!important;
            }
        }
        @media (max-height: 500px) {
            .scene {
                margin-top: -5%!important;
            }
        }
        @media (max-width: 500px) {
            .scene {
                margin-top: 100px;
            }
            #small-screen-message {
                margin-top: 30px;
                display: block!important;
            }
        }

        .screen-lock {
            opacity: 1;
            transition: opacity 8s;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: black;
            z-index: 9999;
            color: white;
            font-size: 24px;
            text-align: center;
            padding: 20px;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            font-weight: 500;
        }



        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #FFF; /* Синий фон */
            font-family: Arial, Helvetica, sans-serif;
            overflow: hidden;
            background-image: url('/images/valentine/bg_pattern.jpg'); /* Путь к картинке */
            background-size: 50%;
        }

        .blur {
            /*opacity: .8;*/
            /*filter: blur(1px);*/
        }

        /* Сцена с перспективой, размер подстраивается под карточку через JS */
        .scene {
            margin-top: -30px;
            width: var(--card-size);
            height: var(--card-size);
            perspective: 1200px;
            touch-action: none; /* важно для тачей */
        }

        /* Карточка — контейнер для граней */
        .card {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.1s ease-out;
            will-change: transform;
        }

        /* Общие стили для всех граней */
        .face {
            position: absolute;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            backface-visibility: visible;
            background-size: cover;
            background-position: center;
        }

        /* Передняя грань с изображением */
        .front {
            width: var(--card-size);
            height: var(--card-size);
            background-image: url('{{ $img }}'); /* Путь к картинке */
            background-size: cover;
            background-position: center;
            /*transform: translateZ(calc(var(--thickness) / 2));*/
            border-radius: var(--radius);
            transform: translateZ(16px);
        }

        /* Задняя грань — полупрозрачная, размытая */
        .back {
            width: var(--card-size);
            height: var(--card-size);
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            transform: translateZ(calc(-1 * var(--thickness) / 2)) rotateY(180deg);
            border-radius: var(--radius);
        }

        /* Левая грань */
        .left {
            width: var(--thickness);
            height: var(--card-size);
            background-image: url('/images/valentine/h.png'); /* Путь к картинке */
            backdrop-filter: blur(6px);
            background-position-y: -1px;
            transform: rotateY(90deg)  translateZ(calc(-1 * var(--thickness) / 2))
        }

        .right {
            width: var(--thickness);
            height: var(--card-size);
            background-color: rgba(255, 255, 255, 0.15);
            background-image: url('/images/valentine/h.png'); /* Путь к картинке */
            backdrop-filter: blur(6px);
            background-position-y: -1px;
            transform: rotateY(90deg)  translateZ(calc(-1 * var(--thickness) / 2 + var(--card-size)))

        }

        .top {
            width: var(--card-size);
            height: var(--thickness);
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            transform: rotateX(90deg)  translateZ(calc(var(--thickness) / 2));
            background-image: url('/images/valentine/v.png'); /* Путь к картинке */
        }

        .bottom {
            width: var(--card-size);
            height: var(--thickness);
            background-color: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(6px);
            transform: rotateX(90deg)  translateZ(calc(var(--thickness) / 2 - var(--card-size)));
            background-image: url('/images/valentine/v.png'); /* Путь к картинке */
        }

        /* Лёгкая обводка для визуального выделения граней (опционально) */
        .face {
            border: 1px solid rgba(255,255,255,0.1);
        }
        #small-screen-message,
        #big-screen-message {
            display: none;
        }
    </style>
</head>
<body>
<div class="screen-lock" id="screenLock">
    <div id="small-screen-message">Поверни или раскрой телефон!</div>
</div>



<div class="div" style="width: 100%; height: 100%; display: flex; justify-content: center; align-items:center">
    <div  style="position: absolute; top: 25%; right: 20px; width: 64px; z-index: 9998">
        <a href="/images/valentine/rp.jpg">
            <img src="/images/valentine/btn.svg" style="width: 100%">
        </a>
    </div>


    <div class="scene" id="scene">

        <div class="card" id="card">
            <div class="face front"></div>
            <div class="face back blur"></div>
            <div class="face left blur"></div>
            <div class="face right blur"></div>
            <div class="face top blur"></div>
            <div class="face bottom blur"></div>
        </div>
    </div>
</div>

<script>
    // ==================== КОНФИГ ====================
    const CONFIG = {
        sizePercent: 70,       // Размер карточки в % от меньшей стороны окна
        thicknessPx: 30,       // Толщина карточки в пикселях
        borderRadiusPx: 0,     // Радиус скругления углов в пикселях
        flyHeightPx: 50,       // Подъём карточки над фоном (px)

        // Параметры анимации:
        tiltPercent: 80,       // Угол наклона в процентах (0–100) от максимума
        rotationPeriodSec: 8   // Время полного круга в секундах (>0)
    };

    // Максимальный угол, от которого считается процент (можете изменить при желании)
    const MAX_TILT_DEG = 30;
    // =================================================

    const card = document.getElementById('card');

    // Устанавливаем постоянные CSS-переменные
    document.documentElement.style.setProperty('--thickness', CONFIG.thicknessPx + 'px');
    document.documentElement.style.setProperty('--radius', CONFIG.borderRadiusPx + 'px');

    // Обновление размера карточки от окна
    function updateCardSize() {
        const minSide = Math.min(window.innerWidth, window.innerHeight);
        const cardSize = (CONFIG.sizePercent / 100) * minSide;
        document.documentElement.style.setProperty('--card-size', cardSize + 'px');
    }

    // Клэмп
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

    // Анимация «как будто курсор двигается по кругу вокруг центра карточки»
    let rafId = null;
    function startCircularFollow() {
        const tiltDeg = clamp(CONFIG.tiltPercent, 0, 100) / 100 * MAX_TILT_DEG;
        const periodMs = Math.max(0.1, Math.abs(Number(CONFIG.rotationPeriodSec)) || 0) * 1000;

        let start = performance.now();

        function tick(now) {
            const elapsed = now - start;
            const phase = (elapsed % periodMs) / periodMs; // 0..1
            const theta = phase * Math.PI * 2;             // 0..2π

            // Виртуальный «курсор» движется по окружности радиуса 1 (нормализовано),
            // а угол наклона определяется его проекциями по осям
            const normX = Math.cos(theta);  // -1..1
            const normY = Math.sin(theta);  // -1..1

            // Маппинг как в логике «следования за курсором»:
            const angleY = normX * tiltDeg;  // поворот вокруг Y по X-смещению
            const angleX = -normY * tiltDeg; // поворот вокруг X по Y-смещению (знак минус — к «курсу»)

            card.style.transform = `translateZ(${CONFIG.flyHeightPx}px) rotateX(${angleX}deg) rotateY(${angleY}deg)`;
            rafId = requestAnimationFrame(tick);
        }

        if (rafId) cancelAnimationFrame(rafId);
        rafId = requestAnimationFrame(tick);
    }

    // Инициализация
    function init() {
        updateCardSize();
        // Начальная поза — точка на круге при theta = 0
        const tiltDeg = clamp(CONFIG.tiltPercent, 0, 100) / 100 * MAX_TILT_DEG;
        card.style.transform = `translateZ(${CONFIG.flyHeightPx}px) rotateX(${-0 * tiltDeg}deg) rotateY(${1 * tiltDeg}deg)`;
        startCircularFollow();
    }

    window.addEventListener('resize', updateCardSize);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
</script>



<script>
</script>


</body>
</html>
