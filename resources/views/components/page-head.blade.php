@props([
    'title',
    'eyebrow' => null,
    'description' => null,
    'back' => null,
    'backLabel' => null,
])

<div class="oa-page-head">
    <div>
        @if($back)
            <a href="{{ $back }}" class="small font-weight-bold"><i class="fas fa-arrow-left mr-1"></i> {{ $backLabel ?? 'Back' }}</a>
        @elseif($eyebrow)
            <span class="oa-eyebrow">{{ $eyebrow }}</span>
        @endif
        <h2 @class(['mt-2' => $back])>{{ $title }}</h2>
        @if($description)
            <p>{{ $description }}</p>
        @endif
    </div>
    @if($slot->isNotEmpty())
        <div class="oa-toolbar">
            {{ $slot }}
        </div>
    @endif
</div>
