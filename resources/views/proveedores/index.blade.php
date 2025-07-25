@extends('layouts.app')

@section('content')
    <div class="container py-5">
        <h2>Lista de Proveedores</h2>

        {{-- Botón Crear --}}
        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#crearModal">
            Nuevo Proveedor
        </button>

        {{-- Mensaje de éxito --}}
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        {{-- Tabla de proveedores --}}
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($proveedores as $p)
                    <tr>
                        <td>{{ $p->nombre }}</td>
                        <td>{{ $p->direccion }}</td>
                        <td>{{ $p->telefono }}</td>
                        <td>{{ $p->email }}</td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                data-bs-target="#editarModal{{ $p->id }}">
                                Editar
                            </button>
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                data-bs-target="#eliminarModal{{ $p->id }}">
                                Eliminar
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Modales fuera de la tabla --}}
    {{-- Modal Crear --}}
    <div class="modal fade" id="crearModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <form class="modal-content" action="{{ route('proveedores.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Nuevo Proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label>Nombre *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label>Dirección</label>
                        <input type="text" name="direccion" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Teléfono</label>
                        <input type="text" name="telefono" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>

    @foreach($proveedores as $p)
        {{-- Modal Editar --}}
        <div class="modal fade" id="editarModal{{ $p->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <form class="modal-content" action="{{ route('proveedores.update', $p) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Proveedor</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label>Nombre *</label>
                            <input type="text" name="nombre" value="{{ old('nombre', $p->nombre) }}" class="form-control"
                                required>
                        </div>
                        <div class="mb-3">
                            <label>Dirección</label>
                            <input type="text" name="direccion" value="{{ old('direccion', $p->direccion) }}"
                                class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Teléfono</label>
                            <input type="text" name="telefono" value="{{ old('telefono', $p->telefono) }}" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label>Email</label>
                            <input type="email" name="email" value="{{ old('email', $p->email) }}" class="form-control">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Modal Eliminar --}}
        <div class="modal fade" id="eliminarModal{{ $p->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-sm">
                <form class="modal-content" action="{{ route('proveedores.destroy', $p) }}" method="POST">
                    @csrf @method('DELETE')
                    <div class="modal-header">
                        <h5 class="modal-title">¿Eliminar proveedor?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p>¿Seguro que deseas eliminar a <strong>{{ $p->nombre }}</strong>?</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Sí, eliminar</button>
                    </div>
                </form>
            </div>
        </div>
    @endforeach
@endsection