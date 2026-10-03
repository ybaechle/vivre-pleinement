<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\ChecksSubmissionDelay;
use App\Support\SiteContact;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CommentFormRequest extends FormRequest
{
    use ChecksSubmissionDelay;

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'author_name' => ['required', 'string', 'max:80'],
            'author_email' => [
                'required',
                'email:rfc,dns',
                'max:160',
                /**
                 * Le badge « Auteure » est attribué sur cette adresse : un
                 * visiteur ne doit pas pouvoir l'obtenir en la saisissant.
                 */
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (mb_strtolower(trim((string) $value)) === mb_strtolower(trim(SiteContact::email()))) {
                        $fail('Cette adresse est réservée à l\'auteure du site.');
                    }
                },
            ],
            'content' => ['required', 'string', 'min:5', 'max:5000'],
            'consent' => ['accepted'],
            'website' => ['nullable', 'prohibited'],
            'ts' => ['required', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'author_name.required' => 'Votre nom est requis.',
            'author_email.required' => 'Votre email est requis.',
            'author_email.email' => 'Cet email n\'est pas valide.',
            'content.required' => 'Votre commentaire est vide.',
            'content.min' => 'Votre commentaire est un peu court.',
            'content.max' => 'Votre commentaire est trop long (5000 caractères maximum).',
            'consent.accepted' => 'Vous devez accepter le traitement de vos données.',
            'website.prohibited' => 'Erreur de soumission.',
        ];
    }
}
