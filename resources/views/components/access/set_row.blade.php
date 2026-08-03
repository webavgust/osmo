
<x-ui.inputs.radio_group_access :group="$group" :access="$access" checked="{{$checked ?? 0}}"  ></x-ui.inputs.radio_group_access>

<div class="ms-3 content-todo">
    <h5 class="font-weight-medium fs-4 todo-header pt-1" data-todo-header="Meeting with Mr.Jojo Sukla at 5.00PM">
        {{ $access->name }}
    </h5>
    @if($access->description)
        <div class="todo-subtext text-muted fs-3" data-todosubtext-html="<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi pulvinar feugiat consequat. Duis lacus nibh, sagittis id varius vel, aliquet non augue. </p>" data-todosubtexttext="{&quot;ops&quot;:[{&quot;insert&quot;:&quot;Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi pulvinar feugiat consequat. Duis lacus nibh, sagittis id varius vel, aliquet non augue. \n&quot;}]}">
            {{ $access->description }}
        </div>
    @endif
</div>
