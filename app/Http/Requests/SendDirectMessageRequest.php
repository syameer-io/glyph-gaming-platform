<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendDirectMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Authorization is handled in the controller (friendship check).
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:2000'],
            'recipient_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Message cannot be empty.',
            'content.max' => 'Message cannot exceed 2000 characters.',
            'recipient_id.exists' => 'The selected recipient does not exist.',
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
            'content' => 'message',
            'recipient_id' => 'recipient',
        ];
    }
}
