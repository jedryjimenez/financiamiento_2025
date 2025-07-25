@if(session('success'))
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Éxito',
            text: '{{ session('success') }}',
            confirmButtonText: 'OK'
        });
    </script>
@endif

@if(session('error'))
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Caja cerrada',
            text: '{{ session('error') }}',
            confirmButtonText: 'Entendido'
        });
    </script>
@endif

<h4>Cuotas</h4>
<table class="table">
    <thead>
        <tr>
            <th>#</th>
            <th>Vencimiento</th>
            <th>Monto (C$)</th>
            <th>Estado</th>
            <th>Acción</th>
        </tr>
    </thead>
    <tbody>
        @foreach($credito->cuotas as $cuota)
            <tr class="{{ $cuota->estado == 'pagada' ? 'table-success' : '' }}">
                <td>{{ $cuota->numero }}</td>
                <td>{{ $cuota->fecha_vencimiento->format('d/m/Y') }}</td>
                <td>{{ number_format($cuota->monto, 2) }}</td>
                <td>{{ ucfirst($cuota->estado) }}</td>
                <td>
                    @if($cuota->estado == 'pendiente')
                        <form action="{{ route('cuotas.pagar', $cuota) }}" method="POST">
                            @csrf
                            <button class="btn btn-sm btn-primary">Marcar pagada</button>
                        </form>
                    @endif
                </td>
            </tr>
        @endforeach
    </tbody>
</table>