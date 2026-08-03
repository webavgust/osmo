<div class="card agreement_block mb-1">
    <div class="card-body d-flex justify-content-between align-items-center">
        <h4 class="card-title mb-0">Согласование скидки</h4>
    </div>
    <div class="card-body">
        <div class="card-table">
            @foreach($task->agreement->users as $user)
                <x-education_task.detail.agreement_row :task="$task" :loop="$loop" :user="$user"></x-education_task.detail.agreement_row>
            @endforeach
        </div>
    </div>
</div>
