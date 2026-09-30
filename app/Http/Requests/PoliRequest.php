<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PoliRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('poli')?->id;

        return [
            'nama_poli' => 'required|string|max:100|unique:poli,nama_poli' . ($id ? ",{$id}" : ''),
            'lokasi'    => 'nullable|string|max:100',
            'status'    => 'required|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_poli.required' => 'Nama poli wajib diisi.',
            'nama_poli.unique'   => 'Nama poli ini sudah terdaftar.',
        ];
    }
}
