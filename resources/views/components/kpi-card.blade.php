@props([
    'title',       // Título de la tarjeta
    'value',       // Valor a mostrar
    'icon',        // HTML del icono (<i class="bi bi-..."></i>)
    'route' => '#',// Ruta del enlace
    'bgClass' => 'bg-primary' // Clase de fondo de Bootstrap
])
      
      <a hre   f="{{ $route }}" class="text-decoration-none col-12 col-md-3">
     <div    {{ $attributes->merge(['class' => "card kpi-card text-white $bgClass h-100 position-relative"]) }}>
        <div class="card-body text-center">
        <span class="position-absolute" style="top:1rem; right:1rem;">
        {!! $icon !!}
      </span>
      <h5>{{ $title }}</h5>
      <p class="display-5 mb-0">{{ $value }}</p>
    </div>
  </div>
</a>
