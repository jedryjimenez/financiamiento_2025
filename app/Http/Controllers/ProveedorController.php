<?php

namespace App\Http\Controllers;

use App\Models\Proveedor;
use Illuminate\Http\Request;

class ProveedorController extends Controller
{
    /**
     * Mostrar listado de proveedores.
     */
    public function index()
    {
        $proveedores = Proveedor::orderBy('nombre')->get();
        return view('proveedores.index', compact('proveedores'));
    }

    /**
     * Almacenar un nuevo proveedor.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'email' => 'nullable|email|unique:proveedores,email',
        ]);

        Proveedor::create($data);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    /**
     * Actualizar un proveedor existente.
     */
    public function update(Request $request, Proveedor $proveedor)
    {
        // Validación
        $data = $request->validate([
            'nombre' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono' => 'nullable|string|max:50',
            'email' => "nullable|email|unique:proveedores,email,{$proveedor->id}",
        ]);

        // Ejecuta el UPDATE
        $proveedor->update($data);

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    /**
     * Eliminar un proveedor.
     */
    public function destroy(Proveedor $proveedor)
    {
        $proveedor->delete();

        return redirect()
            ->route('proveedores.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}
