<?php

namespace App\Http\Requests;

use App\Models\Team;
use App\Models\TeamMember;
use App\Services\TeamRoleService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class AssignTeamRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only team leaders and co-leaders can assign roles.
     */
    public function authorize(): bool
    {
        $team = $this->route('team');
        $user = $this->user();

        if (!$team || !$user) {
            return false;
        }

        // Get user's membership in this team
        $membership = $team->members()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$membership) {
            return false;
        }

        // Only leaders and co-leaders can assign roles
        return $membership->canManageRoles();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => 'required|string|max:50',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Add custom validation for game-specific roles.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $team = $this->route('team');
            $role = $this->input('role');

            if (!$team || !$role) {
                return;
            }

            // Validate role is valid for the team's game
            $teamRoleService = new TeamRoleService();
            if (!$teamRoleService->validateRoleForGame($role, $team->game_appid)) {
                $gameName = $team->game_name ?? 'this game';
                $validator->errors()->add(
                    'role',
                    "The role '{$role}' is not valid for {$gameName}."
                );
            }
        });
    }

    /**
     * Get custom error messages for validation.
     */
    public function messages(): array
    {
        return [
            'role.required' => 'A game role must be specified.',
            'role.string' => 'The game role must be a valid string.',
            'role.max' => 'The game role name is too long.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'role' => 'game role',
        ];
    }
}
