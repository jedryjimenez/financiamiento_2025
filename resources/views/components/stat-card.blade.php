@props(['title', 'value', 'icon'])

<div {{ $attributes->merge(['class' => 'card p-3 mb-3']) }}>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h6 class="text-uppercase small mb-1">{{ $title }}</h6>
            <h4 class="mb-0">{{ $value }}</h4>
        </div>
        <div class="fs-2">
            {!! $icon !!}
        </div>
    </div>
</div>