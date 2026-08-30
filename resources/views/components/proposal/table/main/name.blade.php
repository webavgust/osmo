<div class="cell">
    <div class="fw-bolder fs-7 align-center d-flex justify-content-start">
        <a href="{{ route('proposal.detail', [$row, $row->iteration]) }}">
            {{ $row->name }}
        </a>

        @if($row->iteration > 1)
            <x-ui.badge.light_rounded type="primary" class="text-white ms-2 mb-1">
                {{ $row->iteration }}
            </x-ui.badge.light_rounded>
        @endif
    </div>
</div>
