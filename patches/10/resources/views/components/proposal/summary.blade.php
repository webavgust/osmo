@props(['proposal'])

{{--
    Кнопка перехода в сквозную карточку сделки.

    <x-proposal.summary :proposal="$proposal" />
--}}

<a href="{{ route('deal_card.index', $proposal->group ?? $proposal) }}"
   class="btn btn-sm btn-light-primary"
   title="КП, сделки Битрикса, договоры, спецификации, платежи и лицензии на одном экране">
    <i class="fa-light fa-diagram-project fs-5 me-2"></i>Сводная информация
</a>
