<div>
    <a href="{{ route('proposal.detail', [$row, $row->iteration]) }}">
        {{ $row->name }}
        <sup>{{ $row->iteration }}</sup>
    </a>
</div>
