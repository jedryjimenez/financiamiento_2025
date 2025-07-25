{{-- resources/views/posventa/create.blade.php --}}
@extends('layouts.app')

@push('styles')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
        rel="stylesheet" />
    <style>
        .tabla-items td,
        .tabla-items th {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="container-fluid py-4">
        <h2>POS Venta</h2>
        @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>@endif
        @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>@endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form id="pos-form" action="{{ route('posventa.store') }}" method="POST">
            @csrf
            <div class="row g-3">
                {{-- Panel izquierdo --}}
                <div class="col-lg-9">
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-2">
                                <label>Buscar insumo</label>
                                <select id="buscador" class="form-select"></select>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col">
                                    <label>Cantidad</label>
                                    <input type="number" step="0.01" id="cantidad" class="form-control" value="0">
                                </div>
                                <div class="col">
                                    <label>Stock</label>
                                    <input type="text" id="stock" class="form-control" readonly>
                                </div>
                                <div class="col">
                                    <label>P. venta</label>
                                    <input type="text" id="precio" class="form-control" readonly>
                                </div>
                                <div class="col">
                                    <label>Desc.</label>
                                    <input type="number" step="0.01" id="descuento" class="form-control" value="0">
                                </div>
                                <div class="col-2 d-flex align-items-end">
                                    <button type="button" id="btn-agregar" class="btn btn-dark w-100">Agregar</button>
                                </div>
                            </div>

                            <table class="table table-bordered tabla-items">
                                <thead>
                                    <tr>
                                        <th>Num</th>
                                        <th>Artículo</th>
                                        <th>Cantidad</th>
                                        <th>P. venta</th>
                                        <th>Desc.</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="tbody-items"></tbody>
                            </table>
                            <p class="text-end mb-0"><strong>Total C$ <span id="total-label">0.00</span></strong></p>
                        </div>
                    </div>
                </div>

                {{-- Panel derecho --}}
                <div class="col-lg-3">
                    <div class="card mb-3">
                        <div class="card-body">
                            <h5>Datos de la venta</h5>

                            <div class="mb-2">
                                <label>Tipo de venta</label>
                                <select name="modo" id="modo" class="form-select">
                                    <option value="contado">Contado</option>
                                    <option value="credito">Crédito</option>
                                </select>
                            </div>

                            <div class="mb-2">
                                <label>Cliente</label>
                                <select name="productor_id" id="select-cliente" class="form-select">
                                    <option value="">Público General</option>
                                    @foreach($clientes as $cl)
                                        <option value="{{ $cl->id }}">{{ $cl->nombre }} – {{ $cl->cedula }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-2">
                                <label>Fecha</label>
                                <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                            </div>
                            <div class="mb-2">
                                <label>Total</label>
                                <input type="text" id="total-input" class="form-control" readonly>
                            </div>
                            <div class="mb-2">
                                <label>Paga con</label>
                                <input type="number" step="0.01" name="paga_con" id="paga-con" class="form-control">
                            </div>
                            <div class="mb-2">
                                <label>Cambio</label>
                                <input type="text" id="cambio" class="form-control" readonly>
                            </div>
                            <button class="btn btn-primary w-100 mt-2">Aceptar</button>
                        </div>
                    </div>

                    {{-- Botones de caja --}}
                    <div class="d-grid gap-2">
                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal"
                            data-bs-target="#modalIngreso">
                            Ingreso efectivo
                        </button>
                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal"
                            data-bs-target="#modalEgreso">
                            Egreso efectivo
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    {{-- Modal Ingreso --}}
    <div class="modal fade" id="modalIngreso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('caja.movimientos.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Ingreso de efectivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tipo" value="ingreso">
                    <div class="mb-3">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Concepto</label>
                        <input type="text" name="concepto" class="form-control" placeholder="Motivo del Ingreso" required>
                    </div>
                    <div class="mb-3">
                        <label>Categoría (opcional)</label>
                        <input type="text" name="categoria" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Egreso --}}
    <div class="modal fade" id="modalEgreso" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form action="{{ route('caja.movimientos.store') }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Egreso de efectivo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="tipo" value="egreso">
                    <div class="mb-3">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label>Concepto</label>
                        <input type="text" name="concepto" class="form-control" value="" placeholder="Motivo del Egreso" required>
                    </div>
                    <div class="mb-3">
                        <label>Categoría (opcional)</label>
                        <input type="text" name="categoria" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Monto</label>
                        <input type="number" step="0.01" name="monto" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-danger">Guardar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css"
            rel="stylesheet" />
    @endpush
    <script>
        let items = [];

        $('#select-cliente').select2({
            theme: 'bootstrap-5',
            placeholder: 'Buscar cliente...',
            allowClear: true
        });
        $('#modo').on('change', function () {
            if ($(this).val() === 'credito') {
                if (!$('#select-cliente').val()) {
                    alert('Selecciona un cliente para venta a crédito.');
                }
            }
        });

        $('#buscador').select2({
            theme: 'bootstrap-5',
            ajax: {
                url: '{{ route("posventa.buscar") }}',
                delay: 250,
                data: params => ({ q: params.term }),
                processResults: data => data
            },
            placeholder: 'Nombre del insumo'
        }).on('select2:select', e => {
            const d = e.params.data;
            $('#precio').val(d.precio);
            $('#stock').val(d.stock);
            $('#cantidad').val(1);
            $('#descuento').val(0);
        });

        function recalcular() {
            let total = 0;
            $('#tbody-items').empty();
            items.forEach((it, i) => {
                total += it.subtotal;
                $('#tbody-items').append(`
                                                                                <tr>
                                                                                  <td>${i + 1}</td>
                                                                                  <td>${it.nombre}<input type="hidden" name="items[${i}][insumo_id]" value="${it.id}"></td>
                                                                                  <td>${it.cantidad}<input type="hidden" name="items[${i}][cantidad]" value="${it.cantidad}"></td>
                                                                                  <td>${it.precio.toFixed(2)}<input type="hidden" name="items[${i}][precio]" value="${it.precio}"></td>
                                                                                  <td>${it.descuento.toFixed(2)}<input type="hidden" name="items[${i}][descuento]" value="${it.descuento}"></td>
                                                                                  <td>${it.subtotal.toFixed(2)}</td>
                                                                                  <td><button type="button" class="btn btn-sm btn-danger" onclick="eliminar(${i})">×</button></td>
                                                                                </tr>
                                                                            `);
            });
            $('#total-label').text(total.toFixed(2));
            $('#total-input').val(total.toFixed(2));
            const paga = parseFloat($('#paga-con').val()) || 0;
            $('#cambio').val((paga - total > 0 ? paga - total : 0).toFixed(2));
        }
        function eliminar(i) { items.splice(i, 1); recalcular(); }

        $('#btn-agregar').click(() => {
            const sel = $('#buscador').select2('data')[0];
            if (!sel) { alert('Seleccione un insumo'); return; }
            const cant = parseFloat($('#cantidad').val()) || 0;
            const stock = parseFloat($('#stock').val()) || 0;
            const precio = parseFloat($('#precio').val()) || 0;
            const desc = parseFloat($('#descuento').val()) || 0;
            if (cant <= 0) { alert('Cantidad inválida'); return; }
            if (cant > stock) { alert('Stock insuficiente'); return; }
            const subtotal = cant * precio - desc;
            items.push({ id: sel.id, nombre: sel.text, cantidad: cant, precio: precio, descuento: desc, subtotal: subtotal });
            $('#buscador').val(null).trigger('change');
            $('#precio,#stock,#cantidad,#descuento').val('');
            recalcular();
        });

        $('#paga-con').on('input', recalcular);

        $('#modo').on('change', function () {
            if ($(this).val() === 'credito') {
                $('#paga-con').prop('disabled', true).val('');
                $('#cambio').val('0.00');
            } else {
                $('#paga-con').prop('disabled', false);
            }
        }).trigger('change');
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

@endpush