<div class="card card-body mt-2 mb-0">


    <div class="content-todo">
        <h5 class="font-weight-medium fs-4 todo-header">
            {{ $notify->title }}
        </h5>
        <div class="todo-subtext text-muted fs-3 mb-3" >
            {!! $notify->message !!}
        </div>
        <span class="todo-time fs-2 text-muted"><i class="icon-calender me-1"></i>{{ $notify->created_at->format('d.m.Y H:i') }}</span>

    </div>
</div>


