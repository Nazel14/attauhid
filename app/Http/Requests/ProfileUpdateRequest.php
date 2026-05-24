<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Admin tidak bisa edit nama
        if ($this->user()->role === 'admin') {
            return [];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}