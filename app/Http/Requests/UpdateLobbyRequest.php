<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLobbyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller policy
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $lobby = $this->route('lobby');
        $joinMethod = $lobby?->join_method;

        $rules = [];

        // Dynamic validation based on the lobby's join method
        return match($joinMethod) {
            'steam_lobby' => [
                'steam_lobby_link' => ['nullable', 'string', 'regex:/^steam:\/\/joinlobby\/\d+\/\d+\/\d+$/'],
            ],

            'steam_connect' => [
                'server_ip' => 'nullable|string',
                'server_port' => 'nullable|integer|between:1,65535',
                'server_password' => 'nullable|string|max:255',
            ],

            'lobby_code' => [
                'lobby_code' => 'nullable|string|max:50',
            ],

            'join_command' => [
                'join_command' => 'nullable|string|max:255',
            ],

            'private_match' => [
                'match_name' => 'nullable|string|max:100',
                'match_password' => 'nullable|string|max:255',
            ],

            'server_address' => [
                'server_ip' => 'nullable|string',
                'server_port' => 'nullable|integer|between:1,65535',
            ],

            default => $rules,
        };
    }

    /**
     * Get custom validation messages
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'steam_lobby_link.regex' => 'Invalid Steam lobby link format. Expected: steam://joinlobby/[appid]/[lobbyid]/[profileid]',
            'server_port.between' => 'Server port must be between 1 and 65535',
        ];
    }
}
