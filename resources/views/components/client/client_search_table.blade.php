<div class="table-responsive">
    @if(count($clients) > 0)
        <table class="table">
            <thead class="bg-light-secondary text-secondary">
            <tr>
                <th class="py-1" style="width: 60px"></th>
                <th class="py-1">Ф.И.О</th>
                <th class="py-1">Телефон</th>
                <th class="py-1">Почта</th>
            </tr>
            </thead>
            <tbody>
            @foreach($clients as $client)
                <x-client.client_search_tr :client="$client" row_id="{{ $row_id ?? null }}"></x-client.client_search_tr>
            @endforeach
            </tbody>
        </table>
    @else
        <div class=" alert customize-alert alert-dismissible alert-light-danger text-danger fade show remove-close-icon" role="alert">
            <div class=" d-flex align-items-center font-weight-medium me-3 me-md-0">
                <x-ui.icon.regular icon="fa-users-slash" class="me-2"></x-ui.icon.regular>
                Ничего не найдено
            </div>
        </div>
    @endif
</div>
