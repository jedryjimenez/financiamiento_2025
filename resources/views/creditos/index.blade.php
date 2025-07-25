{{-- resources/views/creditos/index.blade.php --}}
@extends('layouts.app')

@section('content')
@php
    use Carbon\Carbon;
    use App\Support\CreditoHelper;

    /**
     * Días a considerar para mostrar en la tabla:
     * - Si está pagado y tiene liquidado_at, corta ahí.
     * - Si no, hasta hoy.
     */
    $diasDe = function($credito){
        $inicio = Carbon::parse($credito->fecha_entrega)->startOfDay();
        $fin    = ($credito->estado === 'pagado' && $credito->liquidado_at)
                    ? Carbon::parse($credito->liquidado_at)->startOfDay()
                    : now()->startOfDay();
        return $inicio->diffInDays($fin);
    };
@endphp

<div class="container">
    {{-- Nuevo Crédito --}}
    <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#crearModal">
        Nuevo Crédito
    </button>

    {{-- Saldo Total Pendiente --}}
    <div class="mb-3">
        <p><strong>Saldo Total Pendiente:</strong> C$ {{ number_format($saldoPendiente, 2) }}</p>
    </div>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr>
                <th rowspan="2">Fecha</th>
                <th rowspan="2">Productor</th>
                <th rowspan="2" class="text-end">Saldo</th>
                <th colspan="6" class="text-center">Detalles</th>
                <th rowspan="2" class="text-center">Acciones</th>
            </tr>
            <tr>
                <th>Producto</th>
                <th>Cant.</th>
                <th>P. Venta</th>
                <th>Días</th>
                <th>Interés Acum.</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
        @foreach($creditos as $c)
            @php
                // Saldo pendiente exacto usando helper (corta intereses si está pagado)
                $saldoPend = CreditoHelper::saldoCreditoPorDias($c);
                $dias      = $diasDe($c);
                $rowspan   = $c->detalles->count();

                // Flag para no recalcular intereses si ya está pagado
                $estaPagado = $c->estado === 'pagado';
            @endphp

            @foreach($c->detalles as $i => $d)
                @php
                    if($estaPagado){
                        $intA    = 0.00;
                        $totalLn = $d->subtotal;
                    }else{
                        // Cálculo simple por línea (aprox). Si quieres exacto por abono,
                        // tendrás que simular por detalle con el mismo algoritmo del helper.
                        $tasaDia = ($d->interes / 30) / 100;
                        $intA    = round($d->subtotal * $tasaDia * $dias, 2);
                        $totalLn = round($d->subtotal + $intA, 2);
                    }
                @endphp
                <tr>
                    @if($i === 0)
                        <td rowspan="{{ $rowspan }}">{{ Carbon::parse($c->fecha_entrega)->format('d/m/Y') }}</td>
                        <td rowspan="{{ $rowspan }}">{{ $c->productor->nombre }}</td>
                        <td rowspan="{{ $rowspan }}" class="text-end">
                            C$ {{ number_format($saldoPend, 2) }}
                        </td>
                    @endif

                    <td>{{ $d->insumo->nombre }}</td>
                    <td>{{ number_format($d->cantidad, 2) }}</td>
                    <td>C$ {{ number_format($d->subtotal, 2) }}</td>
                    <td>{{ $dias }}</td>
                    <td>C$ {{ number_format($intA, 2) }}</td>
                    <td>C$ {{ number_format($totalLn, 2) }}</td>

                    @if($i === 0)
                        <td rowspan="{{ $rowspan }}" class="text-center">

                            {{-- Eliminar --}}
                            <form action="{{ route('creditos.destroy', $c) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar?');" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger mb-1">Eliminar</button>
                            </form>

                            {{-- Ver --}}
                            <button type="button" class="btn btn-sm btn-info mb-1"
                                    data-bs-toggle="modal"
                                    data-bs-target="#verCreditoModal-{{ $c->id }}">
                                Ver
                            </button>

                            {{-- Abonar / Pagada --}}
                            @if($saldoPend > 0)
                                <button class="btn btn-sm btn-success"
                                        data-bs-toggle="modal"
                                        data-bs-target="#abonoModal-{{ $c->id }}">
                                    Abonar
                                </button>
                            @else
                                <span class="badge bg-success">Pagada</span>
                            @endif

                        </td>
                    @endif
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table>
</div>

{{-- ===================  MODAL CREAR CRÉDITO  =================== --}}
<div class="modal fade" id="crearModal" tabindex="-1" aria-labelledby="crearLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('creditos.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="crearLabel">Registrar Nuevo Crédito</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="moneda" value="C$">

                    <div class="mb-3">
                        <label>Productor *</label>
                        <select name="productor_id" id="select-productor" class="form-select select2"
                                data-placeholder="Escribe nombre o cédula" required>
                            <option></option>
                            @foreach($productores as $p)
                                <option value="{{ $p->id }}" data-saldo="{{ $p->saldo }}">
                                    {{ $p->nombre }} – {{ $p->cedula }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Saldo pendiente: C$
                            <span id="productor-saldo">0.00</span>
                        </small>
                    </div>

                    <div class="mb-3">
                        <label>Fecha de entrega *</label>
                        <input type="date" name="fecha_entrega" class="form-control"
                               value="{{ date('Y-m-d') }}" required>
                    </div>

                    <hr>
                    <h5>Insumos</h5>
                    <div id="insumos-repeater">
                        <div class="insumo-item row g-2 mb-2 align-items-end">
                            <div class="col-md-4">
                                <label>Insumo</label>
                                <select name="insumos[0][insumo_id]" class="form-select insumo-select" required
                                        data-precio="" data-stock="" data-interes="">
                                    <option value="">Seleccione...</option>
                                    @foreach($insumos as $i)
                                        <option value="{{ $i->id }}"
                                                data-precio="{{ $i->precio_venta }}"
                                                data-stock="{{ $i->stock }}"
                                                data-interes="{{ in_array(strtolower($i->nombre), ['frijoles', 'maiz']) ? 0 : 3 }}">
                                            {{ $i->nombre }} (Stock: {{ $i->stock }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label>Cantidad</label>
                                <input type="number" name="insumos[0][cantidad]" class="form-control cantidad-input"
                                       step="0.01" min="0.01" required>
                            </div>
                            <input type="hidden" name="insumos[0][interes]" class="interes-input">
                            <div class="col-md-3">
                                <label>Precio unitario</label>
                                <input type="text" class="form-control precio-label" readonly>
                            </div>
                            <div class="col-md-2">
                                <label>Subtotal</label>
                                <input type="text" class="form-control subtotal-label" readonly>
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-danger btn-remove-insumo">×</button>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="btn-add-insumo" class="btn btn-sm btn-secondary mb-3">
                        + Agregar otro insumo
                    </button>

                    <hr>
                    <div class="row g-3">
                        <div class="col-md-4 offset-md-4 text-end">
                            <p><strong>Subtotal:</strong> C$ <span id="total-subtotal">0.00</span></p>
                            <p><strong>Total:</strong> C$ <span id="total-general">0.00</span></p>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ===================  MODAL ABONAR  =================== --}}
@foreach($creditos as $c)
    @php
        $saldoPendModal = CreditoHelper::saldoCreditoPorDias($c);
    @endphp
    <div class="modal fade" id="abonoModal-{{ $c->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('creditos.abonar', $c) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Abonar Crédito #{{ $c->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Monto a abonar</label>
                        <input type="number"
                               name="monto"
                               step="0.01"
                               min="0.01"
                               max="{{ $saldoPendModal }}"
                               class="form-control"
                               required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Comentario (opcional)</label>
                        <input type="text" name="comentario" class="form-control">
                    </div>
                    <hr>
                    <h5>Historial de Abonos</h5>
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Comentario</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($c->abonos as $a)
                            <tr>
                                <td>{{ Carbon::parse($a->fecha)->format('d/m/Y') }}</td>
                                <td>C$ {{ number_format($a->monto, 2) }}</td>
                                <td>{{ $a->comentario }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Registrar abono</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
@endforeach

{{-- ===================  MODAL VER DETALLES  =================== --}}
@foreach($creditos as $c)
    @php
        $dias = $diasDe($c);
        $estaPagado = $c->estado === 'pagado';
    @endphp
    <div class="modal fade" id="verCreditoModal-{{ $c->id }}" tabindex="-1"
         aria-labelledby="verCreditoLabel-{{ $c->id }}" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verCreditoLabel-{{ $c->id }}">Detalles del Crédito #{{ $c->id }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Productor:</strong> {{ $c->productor->nombre }}</p>
                    <p><strong>Fecha de Entrega:</strong> {{ Carbon::parse($c->fecha_entrega)->format('d/m/Y') }}</p>
                    <p><strong>Días transcurridos:</strong> {{ $dias }}</p>
                    <hr>
                    <h5>Insumos entregados</h5>
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unitario</th>
                            <th>Subtotal</th>
                            <th>Interés (%)</th>
                            <th>Interés Acum.</th>
                            <th>Total</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($c->detalles as $d)
                            @php
                                if($estaPagado){
                                    $intA    = 0;
                                    $totalLn = $d->subtotal;
                                }else{
                                    $tasaDia = ($d->interes / 30) / 100;
                                    $intA    = round($d->subtotal * $tasaDia * $dias, 2);
                                    $totalLn = round($d->subtotal + $intA, 2);
                                }
                            @endphp
                            <tr>
                                <td>{{ $d->insumo->nombre }}</td>
                                <td>{{ number_format($d->cantidad, 2) }}</td>
                                <td>C$ {{ number_format($d->precio_unitario, 2) }}</td>
                                <td>C$ {{ number_format($d->subtotal, 2) }}</td>
                                <td>{{ $d->interes }}</td>
                                <td>C$ {{ number_format($intA, 2) }}</td>
                                <td>C$ {{ number_format($totalLn, 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <p><strong>Total Crédito (principal guardado):</strong> C$ {{ number_format($c->total, 2) }}</p>
                    <p><strong>Total Abonado:</strong> C$ {{ number_format($c->abonado, 2) }}</p>
                    <p><strong>Saldo Pendiente:</strong> C$ {{ number_format(CreditoHelper::saldoCreditoPorDias($c), 2) }}</p>

                    @if($estaPagado && $c->liquidado_at)
                        <p class="text-success">
                            <strong>Liquidado el:</strong> {{ Carbon::parse($c->liquidado_at)->format('d/m/Y') }}
                        </p>
                    @endif

                    <hr>
                    <h5>Abonos realizados</h5>
                    <table class="table table-sm">
                        <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Monto</th>
                            <th>Comentario</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($c->abonos as $a)
                            <tr>
                                <td>{{ Carbon::parse($a->fecha)->format('d/m/Y') }}</td>
                                <td>C$ {{ number_format($a->monto, 2) }}</td>
                                <td>{{ $a->comentario }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <a href="{{ route('creditos.pdf', $c) }}" target="_blank" class="btn btn-dark">
                        Descargar PDF
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
@endpush

@push('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(function () {
    // Select2 productor
    $('#select-productor').select2({
        theme: 'bootstrap-5',
        placeholder: $('#select-productor').data('placeholder'),
        allowClear: true,
        dropdownParent: $('#crearModal')
    }).on('select2:select', e => {
        $('#productor-saldo').text(parseFloat($(e.params.data.element).data('saldo')).toFixed(2));
    }).on('select2:clear', () => $('#productor-saldo').text('0.00'));

    const rpt   = $('#insumos-repeater'),
          btnAdd= $('#btn-add-insumo'),
          tSub  = $('#total-subtotal'),
          tTot  = $('#total-general');
    let idx = 0;

    function calc() {
        let sub = 0;
        rpt.find('.insumo-item').each(function () {
            const opt     = $(this).find('.insumo-select option:selected'),
                  qty     = parseFloat($(this).find('.cantidad-input').val()) || 0,
                  price   = parseFloat(opt.data('precio')) || 0,
                  partial = qty * price;

            sub += partial;
            $(this).find('.precio-label').val(price ? `C$ ${price.toFixed(2)}` : '');
            $(this).find('.subtotal-label').val(partial ? `C$ ${partial.toFixed(2)}` : '');
            $(this).find('.interes-input').val(opt.data('interes') || 0);
        });
        tSub.text(sub.toFixed(2));
        tTot.text(sub.toFixed(2));
    }

    rpt.on('change', '.insumo-select', function () {
        const opt   = $(this).find(':selected'),
              stock = parseFloat(opt.data('stock')) || 0,
              parent= $(this).closest('.insumo-item');
        parent.find('.cantidad-input')
              .attr('max', stock)
              .val('')
              .attr('placeholder', `Máx ${stock}`);
        calc();
    }).on('change', '.cantidad-input', function () {
        let max = parseFloat($(this).attr('max')) || Infinity,
            v   = parseFloat($(this).val()) || 0;
        if (v > max) {
            alert(`Excede stock (${max})`);
            $(this).val('');
        }
        calc();
    });

    // Clonar insumo
    btnAdd.click(function () {
        idx++;
        let clone = rpt.find('.insumo-item').first().clone();
        clone.find('select,input').each(function () {
            let name = $(this).attr('name');
            if (name) $(this).attr('name', name.replace(/\[\d+\]/, `[${idx}]`));
            $(this).is('select') ? $(this).prop('selectedIndex', 0) : $(this).val('');
        });
        rpt.append(clone);
    });

    // Cuando abre modal crear, recalcula
    $('#crearModal').on('shown.bs.modal', calc);
});
</script>
@endpush
