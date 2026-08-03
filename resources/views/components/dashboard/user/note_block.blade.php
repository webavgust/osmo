<x-ui.button.sidebar_default btn_type="warning" class="w-100" href="{{ route('user-notes.sidebar_add') }}">Добавить заметку</x-ui.button.sidebar_default>

<div id="note-full-container" class="note-full-container">
    @forelse($notes as $note)
        <x-dashboard.user.note_row :note="$note"></x-dashboard.user.note_row>
    @empty
        <div class=" alert customize-alert alert-dismissible alert-light-warning text-warning fade show remove-close-icon mt-2" role="alert">
            У вас пока нет заметок
        </div>
    @endforelse

</div>
<script>
    function note_delete(id) {
        var block_elem = $("body");
        $(block_elem).block({
            message: '<i class="fas fa-spin fa-sync text-white"></i>',
            baseZ: 100000,
            overlayCSS: {
                backgroundColor: "#000",
                opacity: 0.5,
                cursor: "wait",
            },
            css: {
                border: 0,
                padding: 0,
                backgroundColor: "transparent",
            },
        });

        $.ajax({
            url: "{{ route('api.user-notes.delete') }}/" + id + '?_token={{ _token() }}',
            type: "POST",
            dataType: "json",
            success: function (json) {
                $(block_elem).unblock();
                if(json.result == 'success') {
                    $("[type='note'][id='" + id + "']").remove();
                }
            },
            error: function () {
                toastr.error("Не получилось удалить заметку", "Это провал!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                $(block_elem).unblock();
            }
        });
    }

    function note_favorite(id, mode) {
        var block_elem = $("body");
        $(block_elem).block({
            message: '<i class="fas fa-spin fa-sync text-white"></i>',
            baseZ: 100000,
            overlayCSS: {
                backgroundColor: "#000",
                opacity: 0.5,
                cursor: "wait",
            },
            css: {
                border: 0,
                padding: 0,
                backgroundColor: "transparent",
            },
        });

        $.ajax({
            url: "{{ route('api.user-notes.favorite') }}/" + id + '?_token={{ _token() }}',
            type: "POST",
            dataType: "html",
            success: function (html) {
                $(block_elem).unblock();
                $("[type='note'][id='" + id + "']")[0].outerHTML = html;

            },
            error: function () {
                toastr.error("Не получилось удалить заметку", "Это провал!", {
                    progressBar: true,
                    "timeOut": 3000,
                });
                $(block_elem).unblock();
            }
        });
    }
</script>
