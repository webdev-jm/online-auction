{{-- Opens the shared delete modal (layouts/app) for rows with a .btn-delete link. Expects $type, the model name. --}}
@push('js')
    <script>
        $(function() {
            $('body').on('click', '.btn-delete', function(e) {
                e.preventDefault();
                Livewire.dispatch('setDeleteModel', {type: @js($type), model_id: $(this).data('id')});
                $('#modal-delete').modal('show');
            });
        });
    </script>
@endpush
