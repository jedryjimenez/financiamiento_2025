@extends('layouts.app')

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between mb-3">
            <h2>Recepciones de Insumos</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalCrear">
                + Nueva Recepción
            </button>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Filtros & Export --}}
        <form method="GET" class="row g-2 mb-3">
            <div class="col-md-3">
                <select name="proveedor_id" class="form-select">
                    <option value="">— Proveedor —</option>
                    @foreach($proveedores as $p)
                        <option value="{{ $p->id }}" {{ request('proveedor_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="tipo_pago" class="form-select">
                    <option value="">— Tipo Pago —</option>
                    <option value="contado" {{ request('tipo_pago') == 'contado' ? 'selected' : '' }}>Contado</option>
                    <option value="credito" {{ request('tipo_pago') == 'credito' ? 'selected' : '' }}>Crédito</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-3 d-flex">
                <button class="btn btn-primary me-2">Filtrar</button>
                <a href="{{ route('recepcion.export', request()->all()) }}" class="btn btn-outline-success">
                    Exportar CSV
                </a>
            </div>
        </form>

        {{-- Tabla --}}
        <div class="table-responsive">
            <table class="table table-bordered align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Factura</th>
                        <th>Proveedor</th>
                        <th>Fecha</th>
                        <th>Tipo</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Abonado</th>
                        <th class="text-end">Saldo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recepciones as $r)
                        <tr>
                            <td>{{ $r->numero_factura }}</td>
                            <td>{{ $r->proveedor->nombre }}</td>
                            <td>{{ $r->fecha_factura->format('d/m/Y') }}</td>
                            <td class="text-capitalize">{{ $r->tipo_pago }}</td>
                            <td class="text-end">C$ {{ number_format($r->total_factura, 2) }}</td>
                            <td class="text-end">C$ {{ number_format($r->abonado, 2) }}</td>
                            <td class="text-end">C$ {{ number_format($r->total_factura - $r->abonado, 2) }}</td>
                            <td class="text-center">
                                @if($r->tipo_pago == 'credito' && $r->abonado < $r->total_factura)
                                    <button class="btn btn-sm btn-success mb-1" data-bs-toggle="modal"
                                        data-bs-target="#modalAbonar{{ $r->id }}">
                                        Abonar
                                    </button>
                                @endif
                                <button class="btn btn-sm btn-info mb-1" data-bs-toggle="modal"
                                    data-bs-target="#modalVer{{ $r->id }}">
                                    Ver
                                </button>
                                <form action="{{ route('recepcion.destroy', $r) }}" method="POST" class="d-inline"
                                    onsubmit="return confirm('¿Eliminar?')">
                                    @csrf @method('DELETE')
                                    <button class="btn btn-sm btn-danger">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center">Sin recepciones aún.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Paginación --}}
        <div class="d-flex justify-content-end">
            {{ $recepciones->links() }}
        </div>
    </div>

    {{-- Modal Crear --}}
    <div class="modal fade" id="modalCrear" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <form action="{{ route('recepcion.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Nueva Recepción</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <label class="form-label">Proveedor</label>
                                <select name="proveedor_id" class="form-select" required>
                                    <option value="">— Seleccionar —</option>
                                    @foreach($proveedores as $p)
                                        <option value="{{ $p->id }}">{{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Factura #</label>
                                <input type="text" name="numero_factura" class="form-control" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="fecha_factura" class="form-control"
                                    value="{{ now()->toDateString() }}" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Tipo Pago</label>
                                <select name="tipo_pago" class="form-select">
                                    <option value="contado">Contado</option>
                                    <option value="credito">Crédito</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Comprobante</label>
                                <input type="file" name="comprobante" class="form-control">
                            </div>
                        </div>

                        <h5>Ítems</h5>
                        <table class="table table-sm" id="items-table">
                            <thead>
                                <tr>
                                    <th>Insumo</th>
                                    <th>Cant.</th>
                                    <th>Precio C.</th>
                                    <th>Precio V.</th>
                                    <th>Subtotal</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="item-row d-none" id="template-row">
                                    <td><select class="form-select insumo-select"></select></td>
                                    <td><input type="number" class="form-control cantidad" min="1" value="1"></td>
                                    <td><input type="number" step="0.01" class="form-control compra"></td>
                                    <td><input type="number" step="0.01" class="form-control venta"></td>
                                    <td><input type="text" class="form-control subtotal" readonly></td>
                                    <td><button type="button" class="btn btn-sm btn-danger btn-remove">×</button></td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Total:</strong></td>
                                    <td><input type="text" id="totalFactura" class="form-control text-end" readonly></td>
                                    <td><button type="button" id="btnAdd" class="btn btn-sm btn-success">+ Añadir</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-primary">Registrar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Modales Abonar y Ver --}}
    @foreach($recepciones as $r)
        {{-- Abonar --}}
        <div class="modal fade" id="modalAbonar{{ $r->id }}" tabindex="-1">
            <div class="modal-dialog modal-sm">
                <div class="modal-content">
                    <form action="{{ route('recepcion.abonar', $r) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title">Abonar — {{ $r->numero_factura }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p>Saldo pendiente:
                                <strong>C$ {{ number_format($r->total_factura - $r->abonado, 2) }}</strong>
                            </p>
                            <div class="mb-2">
                                <label>Fecha abono</label>
                                <input type="date" name="fecha_abono" class="form-control" value="{{ now()->toDateString() }}"
                                    required>
                            </div>
                            <div class="mb-2">
                                <label>Comprobante</label>
                                <input type="text" name="comprobante" class="form-control" placeholder="N° transferencia, etc.">
                            </div>
                            <div class="mb-2">
                                <label>Monto</label>
                                <input type="number" name="monto" step="0.01" min="0.01"
                                    max="{{ $r->total_factura - $r->abonado }}" class="form-control" required>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-success">Abonar</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Ver --}}
        <div class="modal fade" id="modalVer{{ $r->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            Factura {{ $r->numero_factura }}
                        </h5>
                        <a href="{{ route('recepcion.pdf', $r) }}" class="btn btn-sm btn-outline-primary ms-3" target="_blank">
                            PDF
                        </a>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>
                            <strong>Proveedor:</strong> {{ $r->proveedor->nombre }}<br>
                            <strong>Fecha:</strong> {{ $r->fecha_factura->format('d/m/Y') }}<br>
                            <strong>Tipo:</strong> {{ ucfirst($r->tipo_pago) }}
                        </p>

                        <h5>Ítems</h5>
                        <table class="table table-sm mb-3">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Insumo</th>
                                    <th>Cant.</th>
                                    <th>Precio C.</th>
                                    <th>Precio V.</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($r->items as $i => $it)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $it->insumo->nombre }}</td>
                                        <td>{{ $it->cantidad }}</td>
                                        <td class="text-end">C$ {{ number_format($it->precio_compra, 2) }}</td>
                                        <td class="text-end">C$ {{ number_format($it->precio_venta, 2) }}</td>
                                        <td class="text-end">C$ {{ number_format($it->subtotal, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="5" class="text-end">Total</th>
                                    <th class="text-end">C$ {{ number_format($r->total_factura, 2) }}</th>
                                </tr>
                                @if($r->tipo_pago == 'credito')
                                    <tr>
                                        <th colspan="5" class="text-end">Abonado</th>
                                        <th class="text-end">C$ {{ number_format($r->abonado, 2) }}</th>
                                    </tr>
                                    <tr>
                                        <th colspan="5" class="text-end">Saldo</th>
                                        <th class="text-end">C$ {{ number_format($r->total_factura - $r->abonado, 2) }}</th>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>

                        <h5>Historial de Abonos</h5>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Fecha</th>
                                    <th>Comp.</th>
                                    <th class="text-end">Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($r->abonos as $i => $ab)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $ab->fecha_abono->format('d/m/Y') }}</td>
                                        <td>{{ $ab->comprobante ?: '-' }}</td>
                                        <td class="text-end">C$ {{ number_format($ab->monto, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">Sin abonos</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>

                        @if($r->comprobante)
                            <p class="mt-3">
                                <strong>Comprobante digital:</strong><br>
                                <img src="{{ Storage::url($r->comprobante) }}" style="max-width:200px">
                            </p>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <a href="{{ route('recepcion.pdf', $r) }}" class="btn btn-primary" target="_blank">
                            Descargar PDF
                        </a>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let idx = 0, total = 0,
                tpl = document.getElementById('template-row'),
                tbody = document.querySelector('#items-table tbody'),
                totalFld = document.getElementById('totalFactura'),
                opts = `<option value="">— Seleccionar —</option>
                        @foreach($insumos as $ins)
                              <option value="{{ $ins->id }}"
                                      data-precio_compra="{{ $ins->precio_compra }}"
                                      data-precio_venta="{{ $ins->precio_venta }}">
                                {{ $ins->nombre }}
                              </option>
                        @endforeach`;

            function recalc() {
                total = 0;
                tbody.querySelectorAll('.item-row:not(.d-none)').forEach(r => {
                    total += parseFloat(r.querySelector('.subtotal').value) || 0;
                });
                totalFld.value = total.toFixed(2);
            }

            function bindRow(row) {
                const sel = row.querySelector('.insumo-select'),
                    qty = row.querySelector('.cantidad'),
                    pc = row.querySelector('.compra'),
                    pv = row.querySelector('.venta'),
                    sb = row.querySelector('.subtotal');
                sel.addEventListener('change', () => {
                    let o = sel.selectedOptions[0];
                    pc.value = o.dataset.precio_compra;
                    pv.value = o.dataset.precio_venta;
                    sb.value = (pc.value * qty.value).toFixed(2);
                    recalc();
                });
                [qty, pc, pv].forEach(i =>
                    i.addEventListener('input', () => {
                        sb.value = (qty.value * pc.value).toFixed(2);
                        recalc();
                    })
                );
                row.querySelector('.btn-remove')
                    .addEventListener('click', () => { row.remove(); recalc(); });
            }

            document.getElementById('btnAdd').addEventListener('click', () => {
                let row = tpl.cloneNode(true);
                row.id = ''; row.classList.remove('d-none');
                let sel = row.querySelector('.insumo-select');
                sel.innerHTML = opts;
                sel.name = `items[${idx}][insumo_id]`; sel.required = true;
                ['cantidad', 'compra', 'venta'].forEach((cls, i) => {
                    let el = row.querySelector('.' + cls);
                    el.name = `items[${idx}][${cls === 'compra' ? 'precio_compra' : cls === 'venta' ? 'precio_venta' : 'cantidad'}]`;
                    el.required = cls !== 'cantidad' ? true : true;
                });
                bindRow(row);
                tbody.appendChild(row);
                idx++;
            });

            document.getElementById('btnAdd').click();
        });
    </script>
@endsection