<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DokterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        $id = $this->route('dokter')?->id;

        return [
            'nama_dokter'       => 'required|string|max:100',
            'spesialisasi'      => 'required|string|max:100',
            'no_sip'            => 'required|string|max:30|unique:dokter,no_sip' . ($id ? ",{$id}" : ''),
            'no_hp'             => 'nullable|string|regex:/^[0-9]{8,15}$/|max:15',
            'biaya_konsultasi'  => 'required|numeric|min:0',
            'status'            => 'required|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'nama_dokter.required'      => 'Nama dokter wajib diisi.',
            'spesialisasi.required'     => 'Spesialisasi wajib diisi.',
            'no_sip.required'           => 'Nomor SIP (Surat Izin Praktik) wajib diisi.',
            'no_sip.unique'             => 'Nomor SIP ini sudah terdaftar untuk dokter lain.',
            'no_hp.regex'               => 'Nomor HP harus berupa angka 8-15 digit.',
            'biaya_konsultasi.required' => 'Biaya konsultasi wajib diisi.',
            'biaya_konsultasi.min'      => 'Biaya konsultasi tidak boleh negatif.',
        ];
    }
}
