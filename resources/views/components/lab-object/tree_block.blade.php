@if($object->children->isNotEmpty()) <div class="group"> @endif

    <div @class(["form-check ", "mt-4" => $object->depth == 1])
        {{  $object->id }}
         style="margin-left: {{ 50 * ($object->depth - 1) }}px"
         id="{{ $object->id }}"
         chain="{{ implode(",", $object->chain_id) }}"
         children="{{ implode(",", \App\Modules\Pub\LabObject\Repository\LabObjectRepository::getInnerChildrens($object)->pluck('id')->toArray()) }}"
    >

        <input class="form-check-input info" type="checkbox" id="switch{{ $object->id }}"
                @if($object->children->isEmpty()) name="set[]"  @endif
               id="cb_{{$object->id}}" value="{{ $object->id }}"
               @if($user->lab_objects->contains($object->id)) checked @endif
        >
        <label for="switch{{ $object->id }}">
            {{ $object->name }}
        </label>
    </div>

    @if($object->children->isNotEmpty())
        @foreach($object->children as $children)
            <div>
                <x-lab-object.tree_block :object="$children" :user="$user"/>
            </div>
        @endforeach
    @endif
@if($object->children->isNotEmpty()) </div> @endif
