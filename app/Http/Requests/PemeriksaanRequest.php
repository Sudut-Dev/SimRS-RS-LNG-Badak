<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PemeriksaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('pemeriksaan')?->id;

        return [
            'pendaftaran_id'  => 'required|exists:pendaftaran,id' . ($id ? '' : '|unique:pemeriksaan,pendaftaran_id'),
            'tekanan_darah'   => 'nullable|string|max:20',
            'berat_badan'     => 'nullable|numeric|min:1|max:300',
            'tinggi_badan'    => 'nullable|numeric|min:30|max:250',
            'suhu_tubuh'      => 'nullable|numeric|min:30|max:45',
            'nadi'            => 'nullable|integer|min:30|max:250',
            'diagnosa'        => 'required|string',
            'kode_icd'        => 'nullable|string|max:10',
            'tindakan'        => 'nullable|string',
            'resep'           => 'nullable|string',
            'catatan_dokter'  => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'pendaftaran_id.required'  => 'Data pendaftaran wajib diisi.',
            'pendaftaran_id.unique'    => 'Pendaftaran ini sudah memiliki data pemeriksaan.',
            'diagnosa.required'        => 'Diagnosa wajib diisi.',
            'berat_badan.min'          => 'Berat badan tidak valid.',
            'berat_badan.max'          => 'Berat badan tidak valid (max 300 kg).',
            'tinggi_badan.min'         => 'Tinggi badan tidak valid.',
            'tinggi_badan.max'         => 'Tinggi badan tidak valid (max 250 cm).',
            'suhu_tubuh.min'           => 'Suhu tubuh tidak valid.',
            'suhu_tubuh.max'           => 'Suhu tubuh tidak valid (max 45°C).',
        ];
    }
}
