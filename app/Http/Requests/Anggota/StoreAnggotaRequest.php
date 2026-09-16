<?php

namespace App\Http\Requests\Anggota;

use App\Models\Anggota;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class StoreAnggotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cao' => 'required',
            'kode_kel' => 'required',
            'nama' => 'required',
            'alamat' => 'required',
            'rtrw' => 'required',
            'desa' => 'required',
            'kecamatan' => 'required',
            'kota' => 'required',
            'tgl_lahir' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $umur = Carbon::parse($value)->age;
                    if ($umur > 60) {
                        $fail('Umur tidak boleh lebih dari 60 tahun.');
                    }
                },
            ],
            'ktp' => [
                'required',
                'digits:16',
                function ($attribute, $value, $fail) {
                    $duplikat = Anggota::where('ktp', $value)
                        ->where('unit', Auth::user()->unit)
                        ->exists();
                    if ($duplikat) {
                        $fail('NIK sudah terdaftar di unit ini.');
                    }
                },
            ],
            'kelamin' => 'required|in:L,P',
            'kewarganegaraan' => 'required',
            'status_menikah' => 'required',
            'agama' => 'required',
            'no_hp' => 'required|min:11',
            'hp_pasangan' => 'required|min:11',
            'ibu_kandung' => 'required',
            'pendidikan' => 'required',
            'tempat_lahir' => 'required',
            'waris' => 'required',
            'pekerjaan_pasangan' => 'required',
            'kode_pos' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'cao.required' => 'Nama AO tidak boleh kosong.',
            'kode_kel.required' => 'Nama Kelompok tidak boleh kosong.',
            'nama.required' => 'Nama tidak boleh kosong.',
            'alamat.required' => 'Alamat tidak boleh kosong.',
            'rtrw.required' => 'RT/RW tidak boleh kosong.',
            'desa.required' => 'Desa tidak boleh kosong.',
            'kecamatan.required' => 'Kecamatan tidak boleh kosong.',
            'kota.required' => 'Kabupaten tidak boleh kosong.',
            'kode_pos.required' => 'Kode Pos tidak boleh kosong.',
            'kelamin.required' => 'Jenis Kelamin tidak boleh kosong.',
            'kelamin.in' => 'Jenis Kelamin tidak valid.',
            'tgl_lahir.required' => 'Tanggal Lahir tidak boleh kosong.',
            'ktp.required' => 'NIK tidak boleh kosong.',
            'ktp.digits' => 'NIK harus tepat 16 digit.',
            'kewarganegaraan.required' => 'Kewarganegaraan tidak boleh kosong.',
            'status_menikah.required' => 'Status Menikah tidak boleh kosong.',
            'agama.required' => 'Agama tidak boleh kosong.',
            'no_hp.required' => 'No. Hp tidak boleh kosong.',
            'no_hp.min' => 'No Hp minimal 11 karakter.',
            'hp_pasangan.required' => 'No Hp Pasangan tidak boleh kosong.',
            'hp_pasangan.min' => 'No Hp Pasangan minimal 11 karakter.',
            'ibu_kandung.required' => 'Ibu Kandung tidak boleh kosong.',
            'pendidikan.required' => 'Pendidikan tidak boleh kosong.',
            'tempat_lahir.required' => 'Tempat Lahir tidak boleh kosong.',
            'waris.required' => 'Waris tidak boleh kosong.',
            'pekerjaan_pasangan.required' => 'Pekerjaan Pasangan tidak boleh kosong.',
        ];
    }
}
