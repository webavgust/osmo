<form id="bind">
    <div class="table-wrapper">
        <table class="table mb-0 v-middle measure-table">
            <thead>
            <tr>
                <td>
                    <div class="form-group">
                        <input type="text" class="form-control" id="search" aria-describedby="name" placeholder="Поиск по названию">
                    </div>
                </td>
                @php $rows = 0 @endphp
                @foreach($object->children as $object_sub)
                    <th @class(["object_name main", "odd" => $loop->odd])><a href="javascript:void(0);" onclick="javascript:cb_fill({{$object_sub->id}})">{{ $object_sub->name }}</a></th>
                    @foreach($object_sub->children as $object_once)
                        @php $rows++ @endphp
                        <th @class(["object_name", "odd" => $rows % 2 == 0])>
                            <a href="javascript:void(0);" onclick="javascript:cb_fill({{$object_once->id}})">{{ $object_once->name }}</a>
                        </th>
                    @endforeach
                @endforeach
            </tr>
            </thead>
            @foreach($measure->children as $sub_measure)
                <tr class="searchable" id="{{$sub_measure->id}}">
                    <th class="border-bottom border-top caption"
                        colspan="{{ $rows + $object->children->count() + 1 }}">{{ $sub_measure->name }}</th>
                </tr>
                @foreach($sub_measure->children as $measure_once)
                    <tr class="searchable" parent="{{ $sub_measure->id }}">
                        <td>{{ $measure_once->name }}</td>
                        @foreach($object->children as $object_sub)
                            <td class="value">
                                <input class="form-check-input" object_id="{{$object_sub->id}}" type="checkbox" name="set[{{$object_sub->id}}][{{$measure_once->id}}]" id="cb_{{$object_sub->id}}_{{$measure_once->id}}" value="1" @if(!empty($saved[$object_sub->id][$measure_once->id])) checked @endif>
                            </td>
                            @foreach($object_sub->children as $object_once)
                                <td class="value">
                                    <input class="form-check-input" object_id="{{$object_once->id}}"  type="checkbox" name="set[{{$object_once->id}}][{{$measure_once->id}}]" id="cb_{{$object_once->id}}_{{$measure_once->id}}" value="1" @if(!empty($saved[$object_once->id][$measure_once->id])) checked @endif>
                                </td>
                            @endforeach
                        @endforeach
                    </tr>
                @endforeach
            @endforeach
        </table>
    </div>
</form>
