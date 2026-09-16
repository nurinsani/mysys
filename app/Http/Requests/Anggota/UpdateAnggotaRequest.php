<?php

namespace App\Http\Requests\Anggota;

use App\Models\Anggota;
use Illuminate\Support\Facades\Auth;

class UpdateAnggotaRequest extends StoreAnggotaRequest
{
    public function rules(): array
    {
        $rules = parent::rules();
        // Route::resource('anggota', ...) menghasilkan parameter {anggotum},
        // BUKAN {anggota} — inflector Laravel salah menyingularkan kata ini
        // (mirip "data" -> "datum"). Diverifikasi lewat `route:list`.
        $noSedangDiedit = $this->route('anggotum');

        $rules['ktp'] = [
            'required',
            'digits:16',
            function ($attribute, $value, $fail) use ($noSedangDiedit) {
                $duplikat = Anggota::where('ktp', $value)
                    ->where('unit', Auth::user()->unit)
                    ->where('no', '!=', $noSedangDiedit)
                    ->exists();
                if ($duplikat) {
                    $fail('NIK sudah terdaftar di unit ini.');
                }
            },
        ];

        return $rules;
    }
}
