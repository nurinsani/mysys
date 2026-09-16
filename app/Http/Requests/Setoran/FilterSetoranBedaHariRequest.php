<?php

namespace App\Http\Requests\Setoran;

use Illuminate\Foundation\Http\FormRequest;

class FilterSetoranBedaHariRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code_kel' => 'required|string',
        ];
    }
}
