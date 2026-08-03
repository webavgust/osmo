<tr uid="{{ $uid }}">
    <td class="client_pad">
        <x-client.client_search_selector :uid="$uid" :clientId="$clientId" :app="$app"></x-client.client_search_selector>
    </td>
    <td>
        <div class="position-relative">
            <div class="del d-flex align-items-center position-absolute" onclick="javascript:row_del(this)">
                <i class="fa-solid fa-delete-left"></i>
            </div>
        </div>

        @foreach($courses as $course)
                    <div class="form-check">
                        <input @if(!empty($data[$course->id])) checked @endif class="form-check-input" type="checkbox" value="1" course_id="{{ $course->id }}" name="data[{{$uid}}][courses][{{ $course->id }}][active]">
                        <label class="form-check-label" for="flexCheckDefault">
                            <div>{{ $course->course->name_duration }}</div>
                            <div>
                                <x-ui.badge.default type="info" class="ms-1">{{ $course->type }}</x-ui.badge.default>
                                <x-ui.badge.default type="info" class="ms-2">
                                    <x-ui.icon.solid icon="fa-user"></x-ui.icon.solid>
                                    {{ $course->count }}
                                </x-ui.badge.default>
                            </div>
                        </label>
                    </div>
            </div>
        @endforeach
    </td>
</tr>
