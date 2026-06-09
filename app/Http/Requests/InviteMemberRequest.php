<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InviteMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role->value === 'admin';
    }

    public function rules(): array
    {
        return [
            'email'  => 'required|email|unique:users,email',
            'role'   => 'required|in:admin,agent,staff',
            'avatar' => 'nullable|string',
        ];
    }
}