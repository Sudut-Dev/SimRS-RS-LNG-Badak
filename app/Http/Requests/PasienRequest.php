<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PasienRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $id = $this->route('pasien')?->id;

        return [
            'nik'              => [
                'required',
                'string',
                'digits:16',      // NIK wajib tepat 16 digit angka
                'unique:pasien,nik' . ($id ? ",{$id}" : ''),
            ],
            'nama_pasien'      => 'required|string|max:100',
            'jenis_kelamin'    => 'required|in:L,P',
            'tempat_lahir'     => 'required|string|max:100',
            'tanggal_lahir'    => 'required|date|before:today',
            'alamat'           => 'required|string',
            'no_hp'            => 'nullable|string|regex:/^[0-9]{8,15}$/|max:15',
            'golongan_darah'   => 'required|in:A,B,AB,O,-',
            'jenis_pembayaran' => 'required|in:umum,bpjs,asuransi',
            'no_bpjs'          => 'nullable|string|digits:13|required_if:jenis_pembayaran,bpjs',
        ];
    }

    public function messages(): array
    {
        return [
            'nik.required'           => 'NIK wajib diisi.',
            'nik.digits'             => 'NIK harus tepat 16 digit angka.',
            'nik.unique'             => 'NIK ini sudah terdaftar untuk pasien lain.',
            'nama_pasien.required'   => 'Nama pasien wajib diisi.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'tempat_lahir.required'  => 'Tempat lahir wajib diisi.',
            'tanggal_lahir.required' => 'Tanggal lahir wajib diisi.',
            'tanggal_lahir.before'   => 'Tanggal lahir tidak boleh hari ini atau masa depan.',
            'alamat.required'        => 'Alamat wajib diisi.',
            'no_hp.regex'            => 'Nomor HP harus berupa angka 8-15 digit.',
            'golongan_darah.required' => 'Golongan darah wajib dipilih.',
            'jenis_pembayaran.required' => 'Jenis pembayaran wajib dipilih.',
            'no_bpjs.required_if'    => 'Nomor BPJS wajib diisi jika jenis pembayaran adalah BPJS.',
            'no_bpjs.digits'         => 'Nomor BPJS harus tepat 13 digit.',
        ];
    }
}
