
<div class="order_comments profiletimeline position-relative">
    @forelse($comments as $comment)
        <x-order.detail.comment :comment="$comment"
                                :loop="$loop" mode="{{ $mode ?? '' }}"></x-order.detail.comment>
    @empty

    @endforelse
</div>
