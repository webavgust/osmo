@extends('components.box.box-static-extralarge')

@section('body')
                <table class="table table-bordered m-0">
                    @if($configurations->count() > 0)
                        <tr>
                            <th width="100">Номер</th>
                            <th width="200">Тип</th>
                            <th width="120" class="text-center">Срок (мес)</th>
                            <th width="120" class="text-center">Потоков</th>
                            <th colspan="2">Комментарий</th>
                        </tr>
                        @foreach($configurations as $configuration)
                            <tr>
                                <td class="text-end fs-5"><code>{{ $configuration->number }}</code></td>
                                <td class="pt-3">{{ \App\Modules\Pub\ProjectConfiguration\Models\ProjectConfigurationPlatform::from($configuration->platform)->data()['label'] }}</td>
                                <td class="pt-3 text-center">
                                    @if($configuration->duration > 0)
                                        {{ $configuration->duration }} мес.
                                    @else
                                        Бессрочно
                                    @endif
                                </td>
                                <td class="pt-3 text-center">{{tools()->cost_normalize($configuration->streams) }}</td>
                                <td class="pt-3">
                                    @if(!empty($configuration->comment))
                                        {{ $configuration->comment }}
                                    @else
                                        <span style="color: #DDD">Без комментария</span>
                                    @endif
                                </td>
                                <td width="1">
                                    <x-ui.button.default btn_type="info" onclick="choose({{ $configuration->id }})">Прикрепить</x-ui.button.default>
                                </td>
                            </tr>
                        @endforeach
                    @endif

                    <tr>
                        <th colspan="7">Прикрепленные конфигурации</th>
                    </tr>
                    @foreach($spec->project_configurations as $configuration)
                        <tr>
                            <td class="text-end fs-5"><code>{{ $configuration->number }}</code></td>
                            <td class="pt-3">{{ \App\Modules\Pub\ProjectConfiguration\Models\ProjectConfigurationPlatform::from($configuration->platform)->data()['label'] }}</td>
                            <td class="pt-3 text-center">
                                @if($configuration->duration > 0)
                                    {{ $configuration->duration }} мес.
                                @else
                                    Бессрочно
                                @endif
                            </td>
                            <td class="pt-3 text-center">{{tools()->cost_normalize($configuration->streams) }}</td>
                            <td class="pt-3">
                                @if(!empty($configuration->comment))
                                    {{ $configuration->comment }}
                                @else
                                    <span style="color: #DDD">Без комментария</span>
                                @endif
                            </td>
                            <td width="1">
                                <x-ui.button.default btn_type="danger" onclick="choose({{ $configuration->id }}, 1)">Открепить</x-ui.button.default>
                            </td>
                        </tr>
                    @endforeach
                </table>
    </form>

    <script>
        function choose(num, unbind) {
            $("body").block(block_default);
            $.ajax({
                url: "{{ route('api.contract_spec.set_project_configuration', [$spec, '_token' => _token() ]) }}",
                type: "POST",
                dataType: "json",
                data: { num, unbind },
                success: function (response) {
                    if (response.result == 'success') {
                        location.reload();
                    } else {
                        toastr.error("Не получилось сохранить данные", "Это провал!", {
                            progressBar: true,
                            "timeOut": 3000,
                        });
                        $("body").unblock();
                    }
                },
                error: function () {
                    toastr.error("Не получилось сохранить данные", "Это провал!", {
                        progressBar: true,
                        "timeOut": 3000,
                    });
                    $("body").unblock();
                }
            });
        }
    </script>
@endsection

@section('footer')
    <div class="d-flex justify-content-between align-items-center w-100">
        @if(!empty($spec->project_configuration))
            <x-ui.button.default btn_type="danger" onclick="choose(0);">
                <x-ui.icon.solid icon="fa-trash" class="me-1"></x-ui.icon.solid>
                <span>Убрать привязку</span>
            </x-ui.button.default>
        @else
            <div/>
        @endif
    </div>
@endsection
