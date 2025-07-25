{{-- resources/views/productores/index.blade.php --}}
@extends('layouts.app')

@section('content')
<div class="container">
  <h2>Lista de Productores</h2>

  {{-- Botón “Nuevo Productor” --}}
  <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#crearModal">
    Nuevo Productor
  </button>

  @if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th>Nombre</th>
        <th>Cédula</th>
        <th>Teléfono</th>
        <th>Dirección</th>
        <th class="text-end">Saldo Total</th>
        <th class="text-center">Acciones</th>
      </tr>
    </thead>
    <tbody>
      @foreach($productores as $p)
        <tr>
          <td>{{ $p->nombre }}</td>
          <td>{{ $p->cedula }}</td>
          <td>{{ $p->telefono }}</td>
          <td>{{ $p->direccion }}</td>
          <td class="text-end">C$ {{ number_format($p->saldoAcumulado, 2) }}</td>
          <td class="text-center">
            {{-- Editar siempre disponible --}}
            <button class="btn btn-sm btn-warning"
                    data-bs-toggle="modal"
                    data-bs-target="#editarModal{{ $p->id }}">
              Editar
            </button>

            {{-- Eliminar sólo si saldo = 0 --}}
            @if($p->saldoAcumulado > 0)
              <button class="btn btn-sm btn-danger" disabled title="No se puede eliminar: crédito activo">
                Crédito Activo
              </button>
            @else
              <button class="btn btn-sm btn-danger"
                      data-bs-toggle="modal"
                      data-bs-target="#eliminarModal{{ $p->id }}">
                Eliminar
              </button>
            @endif
          </td>
        </tr>

        {{-- Modal Editar --}}
        <div class="modal fade" id="editarModal{{ $p->id }}" tabindex="-1" aria-labelledby="editarLabel{{ $p->id }}" aria-hidden="true">
          <div class="modal-dialog">
            <div class="modal-content">
              <form action="{{ route('productores.update', $p) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                  <h5 class="modal-title" id="editarLabel{{ $p->id }}">Editar Productor</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                  <div class="mb-3">
                    <label>Nombre *</label>
                    <input type="text" name="nombre" class="form-control" value="{{ $p->nombre }}" required>
                  </div>
                  <div class="mb-3">
                    <label>Cédula</label>
                    <input type="text" name="cedula" class="form-control" value="{{ $p->cedula }}">
                  </div>
                  <div class="mb-3">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" class="form-control" value="{{ $p->telefono }}">
                  </div>
                  <div class="mb-3">
                    <label>Dirección</label>
                    <textarea name="direccion" class="form-control">{{ $p->direccion }}</textarea>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success">Guardar cambios</button>
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        {{-- Modal Eliminar --}}
        @if($p->saldoAcumulado <= 0)
          <div class="modal fade" id="eliminarModal{{ $p->id }}" tabindex="-1" aria-labelledby="eliminarLabel{{ $p->id }}" aria-hidden="true">
            <div class="modal-dialog modal-sm">
              <div class="modal-content">
                <form action="{{ route('productores.destroy', $p) }}" method="POST">
                  @csrf @method('DELETE')
                  <div class="modal-header">
                    <h5 class="modal-title" id="eliminarLabel{{ $p->id }}">¿Eliminar productor?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <p>¿Seguro de eliminar a <strong>{{ $p->nombre }}</strong>?</p>
                  </div>
                  <div class="modal-footer">
                    <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @endif

      @endforeach
    </tbody>
  </table>
</div>

{{-- Modal Crear Productor --}}
<div class="modal fade" id="crearModal" tabindex="-1" aria-labelledby="crearLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="{{ route('productores.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="crearLabel">Nuevo Productor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label>Nombre *</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Cédula</label>
            <input type="text" name="cedula" class="form-control">
          </div>
          <div class="mb-3">
            <label>Teléfono</label>
            <input type="text" name="telefono" class="form-control">
          </div>
          <div class="mb-3">
            <label>Dirección</label>
            <textarea name="direccion" class="form-control"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
