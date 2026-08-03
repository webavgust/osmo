<div class="lesson plan noHover border-0 flex-column py-2 @if($doc->deleted_at) deleted muted @endif">
    <div class="font-14 d-flex justify-content-between align-items-start">
        <span>{{ $doc->purchase->document->name }}</span>

        <x-ui.badge.light type="primary">
            {{ tools()->cost_normalize($doc->purchase->cost) }} ₽
        </x-ui.badge.light>
    </div>
</div>

