@props(['proposal'])

{{--
    Кнопка перехода в сводную карточку сделки.

    <x-proposal.summary :proposal="$proposal" />

    Иконка в синтаксисе fas — он работает и в старой теме (FA5),
    и в Metronic с Font Awesome Pro.
--}}

<a href="{{ route('deal_card.index', $proposal->group ?? $proposal) }}"
   class="btn btn-sm btn-primary text-nowrap"
   title="КП, сделки Битрикса, договоры, спецификации, платежи и лицензии на одном экране">
    <i class="fas fa-sitemap me-2"></i>Сводная информация
</a>
