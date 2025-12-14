<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class StoreTeamInvitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled in the service layer for more detailed control.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // At least one identifier is required
            'user_id' => [
                'required_without_all:username,email',
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'username' => [
                'required_without_all:user_id,email',
                'nullable',
                'string',
                'max:255',
                'exists:users,username',
            ],
            'email' => [
                'required_without_all:user_id,username',
                'nullable',
                'email',
                'max:255',
                'exists:users,email',
            ],

            // Role assignment
            'role' => [
                'nullable',
                'string',
                'in:member,co_leader',
            ],

            // Optional message
            'message' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'user_id.required_without_all' => 'Please provide a user ID, username, or email address.',
            'user_id.exists' => 'No user found with this ID.',
            'username.required_without_all' => 'Please provide a user ID, username, or email address.',
            'username.exists' => 'No user found with the username ":input".',
            'email.required_without_all' => 'Please provide a user ID, username, or email address.',
            'email.email' => 'Please provide a valid email address.',
            'email.exists' => 'No user found with this email address.',
            'role.in' => 'Invalid role. Please select Member or Co-Leader.',
            'message.max' => 'Message must not exceed 500 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => $this->username ? trim($this->username) : null,
            'email' => $this->email ? trim($this->email) : null,
            'message' => $this->message ? trim($this->message) : null,
        ]);

        // Log request for debugging (don't log email for privacy)
        Log::debug('StoreTeamInvitationRequest validation', [
            'has_user_id' => $this->has('user_id'),
            'has_username' => $this->has('username'),
            'has_email' => $this->has('email'),
            'role' => $this->role,
            'has_message' => !empty($this->message),
        ]);
    }
}
