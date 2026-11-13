<?php

namespace App\Http\Requests\Admin;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UserUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (config('fortify.lowercase_usernames') && is_string($this->input('email'))) {
            $this->merge(['email' => Str::lower($this->input('email'))]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->targetUser()->id),
            'password' => ['nullable', 'string', Password::default(), 'confirmed'],
            'is_admin' => ['boolean'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * Revoking your own administrator access could leave the system without one.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->has('is_admin') && ! $this->boolean('is_admin') && $this->targetUser()->is($this->user())) {
                    $validator->errors()->add('is_admin', 'Você não pode remover o seu próprio acesso de administrador.');
                }
            },
        ];
    }

    /**
     * The user being edited, resolved by route model binding.
     */
    private function targetUser(): User
    {
        /** @var User */
        return $this->route('user');
    }
}
