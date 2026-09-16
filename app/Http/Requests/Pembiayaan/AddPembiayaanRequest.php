<?php

namespace App\Http\Requests\Pembiayaan;

use Illuminate\Foundation\Http\FormRequest;

class AddPembiayaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 'unit' dan 'id' SENGAJA tidak divalidasi dari input di sini —
            // controller mengambilnya dari Auth::user()->unit / Auth::id(),
            // bukan dari body request, supaya tidak bisa dipalsukan client.
            'jenis_pembiayaan' => 'required|integer',
            'no_rek' => 'required|string',
            'cif' => 'required|string',
            'pengajuan' => 'required|integer',
            'tenor' => 'required|integer',
            'disetujui' => 'required|integer',
            'tgl_wakalah' => 'required|date',
            'tgl_akad' => 'required|date',
            'bidang_usaha' => 'required|string',
            'keterangan_usaha' => 'required|string',
            'param_tanggal' => 'required|date',
            'cao' => 'required|string',
            'kode_kel' => 'required|string',
            'nama' => 'required|string',
            'tgl_lahir' => 'required|date',
            'omzet' => 'nullable|numeric|min:1',
        ];
    }
}
