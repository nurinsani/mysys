<?php

namespace App\Http\Requests\Transaksi;

use Illuminate\Foundation\Http\FormRequest;

class StoreInputTransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cif' => 'required|exists:anggota,cif',
            'nominal' => 'required|numeric|min:1',
            'jenis_transaksi' => 'required|in:1,2,3,4,5',
            'keterangan' => 'nullable|string',
            'jenis_pemindahan' => 'required|in:debet,kredit',
            'jenis_simpanan' => 'required|in:pokok,wajib',
        ];
    }
}
