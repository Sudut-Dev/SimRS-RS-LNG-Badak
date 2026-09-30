<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JadwalDokterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'dokter_id'   => 'required|exists:dokter,id',
            'poli_id'     => 'required|exists:poli,id',
            'hari'        => 'required|in:Senin,Selasa,Rabu,Kamis,Jumat,Sabtu,Minggu',
            'jam_mulai'   => 'required|date_format:H:i',
            'jam_selesai' => 'required|date_format:H:i|after:jam_mulai',
            'kuota'       => 'required|integer|min:1|max:100',
            'status'      => 'required|in:aktif,nonaktif',
        ];
    }

    public function messages(): array
    {
        return [
            'dokter_id.required'   => 'Dokter wajib dipilih.',
            'dokter_id.exists'     => 'Dokter tidak ditemukan.',
            'poli_id.required'     => 'Poli wajib dipilih.',
            'poli_id.exists'       => 'Poli tidak ditemukan.',
            'hari.required'        => 'Hari wajib dipilih.',
            'jam_mulai.required'   => 'Jam mulai wajib diisi.',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.after'    => 'Jam selesai harus setelah jam mulai.',
            'kuota.required'       => 'Kuota pasien wajib diisi.',
            'kuota.min'            => 'Kuota minimal 1 pasien.',
            'kuota.max'            => 'Kuota maksimal 100 pasien.',
        ];
    }
}
