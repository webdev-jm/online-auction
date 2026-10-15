@extends('adminlte::page')

{{-- Extend and customize the browser title --}}

@section('title')
    {{ config('adminlte.title') }}
    @hasSection('subtitle') | @yield('subtitle') @endif
@stop

{{-- Extend and customize the page content header --}}

@section('content_header')
    @hasSection('content_header_title')
        <h1>
            @yield('content_header_title')

            @hasSection('content_header_subtitle')
                <small>
                    <i class="fas fa-xs fa-angle-right"></i>
                    @yield('content_header_subtitle')
                </small>
            @endif
        </h1>
    @endif
@stop

{{-- Rename section content to content_body --}}

@section('content')
    @yield('content_body')

    {{-- Flash messages are shown as toasts by resources/js/app.js --}}
    @if(session()->has('message_success') || session()->has('message_error'))
        <div id="oa-flash" hidden
             data-success="{{ session('message_success') }}"
             data-error="{{ session('message_error') }}"></div>
    @endif

    <!-- DELETE MODAL -->
    <div class="modal fade" id="modal-delete">
        <div class="modal-dialog">
            <livewire:delete-model/>
        </div>
    </div>
@stop

{{-- Create a common footer --}}

@section('footer')
    <div class="float-right">
        Version: {{ config('app.version', '1.0.0') }}
    </div>

    <strong>
        <a href="{{ config('app.company_url', '#') }}">
            {{ config('app.company_name', 'My company') }}
        </a>
    </strong>
@stop

{{-- Setup Custom Preloader Content --}}

@section('preloader')
    <i class="fas fa-4x fa-spin fa-spinner text-secondary"></i>
    <h4 class="mt-4 text-dark">Loading</h4>
@stop

{{-- Add common Javascript/Jquery code --}}

@push('js')
<script>

    $(document).on('click', '[data-toggle="lightbox"]', function(event) {
        event.preventDefault();
        $(this).ekkoLightbox({
            alwaysShowClose: true
        });
    });

</script>
@endpush

{{-- Design layer, toasts and countdowns (Vite) --}}

@prepend('css')
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
@endprepend
