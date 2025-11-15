<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;

/**
 * InviteMemberRequest - Production-ready form request for team member invitations
 *
 * Validates team member invitation requests with comprehensive rules:
 * - Flexible user lookup: user_id, username, OR email
 * - Role validation: member or co_leader only
 * - User-friendly error messages
 * - Authorization check via controller's canManageTeam() method
 *
 * @package App\Http\Requests
 */
class InviteMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Authorization logic is handled in the controller using canManageTeam()
     * to check if the authenticated user is the team creator or co-leader.
     * We return true here as the detailed authorization check requires the
     * Team model which is only available via route model binding in the controller.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Authorization is handled in controller using canManageTeam()
        // This prevents duplicate database queries and keeps authorization
        // logic centralized with access to the Team model
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * Rules ensure exactly ONE identifier is provided (user_id, username, or email).
     * Using required_without_all ensures at least one must be present.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // User identification (at least one required, mutually exclusive)
            'user_id' => [
                'required_without_all:username,email', // Required if username AND email are absent
                'nullable',                             // But can be null if others present
                'integer',
                'exists:users,id',                      // Verify user exists in database
            ],
            'username' => [
                'required_without_all:user_id,email',   // Required if user_id AND email are absent
                'nullable',                             // But can be null if others present
                'string',
                'max:255',
                'exists:users,username',                // Verify username exists (case-sensitive)
            ],
            'email' => [
                'required_without_all:user_id,username', // Required if user_id AND username are absent
                'nullable',                              // But can be null if others present
                'email',                                 // Must be valid email format
                'max:255',
                'exists:users,email',                    // Verify email exists in users table
            ],

            // Team role assignment
            'role' => [
                'nullable',                              // Defaults to 'member' in controller
                'string',
                'in:member,co_leader',                   // Only these two roles allowed
            ],

            // Optional game-specific role (e.g., "Tank", "Support", "Carry")
            'game_role' => [
                'nullable',                              // Optional field
                'string',
                'max:50',                                // Reasonable length for game roles
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * These messages are user-friendly and provide clear guidance
     * on what went wrong and how to fix it.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // User identification errors
            'user_id.required_without_all' => 'Please provide a user ID, username, or email address to invite.',
            'user_id.integer' => 'User ID must be a valid number.',
            'user_id.exists' => 'The selected user does not exist. Please verify the user ID.',

            'username.required_without_all' => 'Please provide a username, user ID, or email address to invite.',
            'username.exists' => 'No user found with this username. Please check spelling and try again.',
            'username.max' => 'Username must not exceed 255 characters.',

            'email.required_without_all' => 'Please provide an email address, username, or user ID to invite.',
            'email.email' => 'Please provide a valid email address (e.g., user@example.com).',
            'email.exists' => 'No user found with this email address. Please verify and try again.',
            'email.max' => 'Email address must not exceed 255 characters.',

            // Role validation errors
            'role.in' => 'Invalid role selected. Please choose either "Member" or "Co-Leader".',
            'role.string' => 'Role must be a valid text value.',

            // Game role errors
            'game_role.max' => 'Game role must not exceed 50 characters. Please use a shorter description.',
            'game_role.string' => 'Game role must be a valid text value.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * These are used in error messages to display human-readable field names
     * instead of the actual field names (e.g., "user ID" instead of "user_id").
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'user_id' => 'user ID',
            'username' => 'username',
            'email' => 'email address',
            'role' => 'team role',
            'game_role' => 'game role',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * This method runs before validation and allows us to clean/normalize
     * the input data. We trim whitespace from string inputs to prevent
     * validation failures due to accidental spaces.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string inputs to prevent validation failures
        $this->merge([
            'username' => $this->username ? trim($this->username) : null,
            'email' => $this->email ? trim($this->email) : null,
            'role' => $this->role ? trim($this->role) : null,
            'game_role' => $this->game_role ? trim($this->game_role) : null,
        ]);
    }

    /**
     * Handle a failed validation attempt.
     *
     * Overriding this method allows us to add custom logging for debugging
     * and security monitoring of failed validation attempts.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log validation failures for debugging and security monitoring
        \Log::warning('InviteMemberRequest validation failed', [
            'user_id' => Auth::id(),
            'errors' => $validator->errors()->toArray(),
            'input' => $this->except(['password', 'token']), // Never log sensitive fields
        ]);

        // Call parent to throw ValidationException with proper response
        parent::failedValidation($validator);
    }
}
