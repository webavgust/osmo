<div block="{{ $block }}">
    @forelse($subUsers as $sub_user)
        <x-user.detail_sub_user_tr :subUser="$sub_user"></x-user.detail_sub_user_tr>
    @empty
        <div class="p-3">
            Нет назначенных людей
        </div>
    @endforelse
</div>
