<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSantriRequest extends FormRequest
{
    /**
     * Tentukan apakah pengguna diizinkan membuat request ini.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Dapatkan aturan validasi yang berlaku untuk request.
     */
    public function rules(): array
    {
        // Ambil ID Santri dari route parameter
        $santriId = $this->route('santri'); 

        return [
            // Data Santri
            'kelas_id' => 'required|exists:kelas,id',
            'guru_id' => 'required|exists:gurus,id',
            'nis' => ['required', 'string', 'max:255', Rule::unique('santris', 'nis')->ignore($santriId)],
            'name' => 'required|string|max:255',
            'gender' => ['required', Rule::in(['L', 'P'])],
            'target_juz' => 'required|numeric|min:0|max:30',
            
            // Password bersifat opsional saat update (nullable)
            'password' => 'nullable|string|min:4',
        ];
    }

    /**
     * Pesan error kustom
     */
    public function messages(): array
    {
        return [
            // NIS
            'nis.required' => 'NIS harus diisi.',
            'nis.unique' => 'NIS sudah terdaftar.',
            'nis.max' => 'NIS maksimal 255 karakter.',
            
            // Nama
            'name.required' => 'Nama santri harus diisi.',
            'name.max' => 'Nama santri maksimal 255 karakter.',
            
            // Gender
            'gender.required' => 'Jenis kelamin harus dipilih.',
            'gender.in' => 'Jenis kelamin harus L atau P.',
            
            // Target Juz
            'target_juz.required' => 'Target juz harus diisi.',
            'target_juz.numeric' => 'Target juz harus berupa angka.',
            'target_juz.min' => 'Target juz minimal 0.',
            'target_juz.max' => 'Target juz maksimal 30.',
            
            // Kelas
            'kelas_id.required' => 'Kelas harus dipilih.',
            'kelas_id.exists' => 'Kelas yang dipilih tidak valid.',
            
            // Guru
            'guru_id.required' => 'Guru harus dipilih.',
            'guru_id.exists' => 'Guru yang dipilih tidak valid.',
            
            // Password (opsional saat update)
            'password.min' => 'Password minimal 4 karakter.',
        ];
    }

    /**
     * Kustomisasi atribut untuk pesan error
     */
    public function attributes(): array
    {
        return [
            'nis' => 'NIS',
            'name' => 'Nama Santri',
            'gender' => 'Jenis Kelamin',
            'target_juz' => 'Target Juz',
            'kelas_id' => 'Kelas',
            'guru_id' => 'Guru',
            'password' => 'Password',
        ];
    }
}