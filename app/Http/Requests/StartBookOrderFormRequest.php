<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StartBookOrderFormRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:rfc,dns', 'max:160'],
            'consent' => ['accepted'],
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => 'Votre prénom est requis.',
            'last_name.required' => 'Votre nom est requis.',
            'email.required' => 'Votre email est requis.',
            'email.email' => 'Cet email n\'est pas valide.',
            'consent.accepted' => 'Vous devez accepter le traitement de vos données.',
            'website.prohibited' => 'Erreur de soumission.',
        ];
    }
}
