<?php

namespace App\Http\Requests;

use App\Models\DirectMessage;
use Illuminate\Foundation\Http\FormRequest;

class EditDirectMessageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * User must be the sender of the message to edit it.
     */
    public function authorize(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // Get the message from route parameter
        $message = $this->route('message');

        // If message exists, verify ownership
        if ($message instanceof DirectMessage) {
            return $message->canEdit(auth()->id());
        }

        return true; // Let controller handle missing message
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
        ];
    }
}
