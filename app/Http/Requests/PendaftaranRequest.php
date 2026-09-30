<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PendaftaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'pasien_id'         => 'required|exists:pasien,id',
            'jadwal_dokter_id'  => 'required|exists:jadwal_dokter,id',
            'tanggal_periksa'   => 'required|date|after_or_equal:today',
            'keluhan'           => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'pasien_id.required'        => 'Pasien wajib dipilih.',
            'pasien_id.exists'          => 'Data pasien tidak ditemukan.',
            'jadwal_dokter_id.required' => 'Jadwal dokter wajib dipilih.',
            'jadwal_dokter_id.exists'   => 'Jadwal dokter tidak ditemukan.',
            'tanggal_periksa.required'  => 'Tanggal periksa wajib diisi.',
            'tanggal_periksa.after_or_equal' => 'Tanggal periksa tidak boleh sebelum hari ini.',
        ];
    }
}
