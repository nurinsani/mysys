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

            // Data Anggota & Domisili
            'tempat_lahir' => 'nullable|string',
            'alamat' => 'nullable|string',
            'rtrw' => 'nullable|string',
            'desa' => 'nullable|string',
            'kecamatan' => 'nullable|string',
            'kota' => 'nullable|string',
            'kode_pos' => 'nullable|string',
            'alamat_domisili' => 'nullable|string',
            'rtrw_domisili' => 'nullable|string',
            'desa_domisili' => 'nullable|string',
            'kecamatan_domisili' => 'nullable|string',
            'kota_domisili' => 'nullable|string',
            'kode_pos_domisili' => 'nullable|string',
            'status_menikah' => 'nullable|string',
            'agama' => 'nullable|string',
            'pendidikan' => 'nullable|string',
            'kewarganegaraan' => 'nullable|string',
            'waris' => 'nullable|string',
            'pekerjaan_pasangan' => 'nullable|string',
            'no_hp' => 'nullable|string',
            'hp_pasangan' => 'nullable|string',
            'ibu_kandung' => 'nullable|string',
        ];
    }
}
