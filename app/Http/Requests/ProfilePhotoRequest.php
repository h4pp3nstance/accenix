<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfilePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() || session()->has('user_info');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile_picture' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif,webp',
                'max:2048', // 2MB max
                'dimensions:min_width=100,min_height=100,max_width=2000,max_height=2000'
            ],
            'remove_photo' => [
                'nullable',
                'string',
                'in:0,1'
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'profile_picture.image' => 'File harus berupa gambar.',
            'profile_picture.mimes' => 'Format file harus: jpeg, jpg, png, gif, atau webp.',
            'profile_picture.max' => 'Ukuran gambar maksimal 2MB.',
            'profile_picture.dimensions' => 'Dimensi gambar minimal 100x100px dan maksimal 2000x2000px.',
            'remove_photo.in' => 'Nilai remove_photo tidak valid.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'profile_picture' => 'foto profil',
            'remove_photo' => 'remove photo flag'
        ];
    }
}
