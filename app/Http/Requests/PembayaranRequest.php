<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PembayaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'pendaftaran_id'    => 'required|exists:pendaftaran,id',
            'biaya_konsultasi'  => 'required|numeric|min:0',
            'biaya_tindakan'    => 'nullable|numeric|min:0',
            'biaya_obat'        => 'nullable|numeric|min:0',
            'biaya_admin'       => 'nullable|numeric|min:0',
            'diskon'            => 'nullable|numeric|min:0',
            'jumlah_bayar'      => 'required|numeric|min:0',
            'metode_bayar'      => 'required|in:tunai,transfer,bpjs,asuransi',
            'catatan'           => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'pendaftaran_id.required'   => 'Data pendaftaran wajib dipilih.',
            'biaya_konsultasi.required' => 'Biaya konsultasi wajib diisi.',
            'biaya_konsultasi.min'      => 'Biaya konsultasi tidak boleh negatif.',
            'jumlah_bayar.required'     => 'Jumlah pembayaran wajib diisi.',
            'jumlah_bayar.min'          => 'Jumlah pembayaran tidak boleh negatif.',
            'metode_bayar.required'     => 'Metode pembayaran wajib dipilih.',
        ];
    }
}
