<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateLobbyRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by controller middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'game_id' => 'required|integer|exists:game_join_configurations,game_id,is_enabled,1',
            'join_method' => 'required|string|in:steam_lobby,steam_connect,lobby_code,join_command,private_match,server_address,manual_invite',
        ];

        // Dynamic validation based on join method
        $joinMethod = $this->input('join_method');

        return match($joinMethod) {
            'steam_lobby' => array_merge($rules, [
                'steam_lobby_link' => ['required', 'string', 'regex:/^steam:\/\/joinlobby\/\d+\/\d+\/\d+$/'],
            ]),

            'steam_connect' => array_merge($rules, [
                'server_ip' => 'required|string',
                'server_port' => 'required|integer|between:1,65535',
                'server_password' => 'nullable|string|max:255',
            ]),

            'lobby_code' => array_merge($rules, [
                'lobby_code' => 'required|string|max:50',
            ]),

            'join_command' => array_merge($rules, [
                'join_command' => 'required|string|max:255',
            ]),

            'private_match' => array_merge($rules, [
                'match_name' => 'required|string|max:100',
                'match_password' => 'nullable|string|max:255',
            ]),

            'server_address' => array_merge($rules, [
                'server_ip' => 'required|string',
                'server_port' => 'required|integer|between:1,65535',
            ]),

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
            'game_id.required' => 'Please select a game',
            'game_id.exists' => 'Selected game is not supported or not enabled for lobbies',
            'join_method.required' => 'Please select a join method',
            'join_method.in' => 'Invalid join method',
            'steam_lobby_link.required' => 'Steam lobby link is required',
            'steam_lobby_link.regex' => 'Invalid Steam lobby link format. Expected: steam://joinlobby/[appid]/[lobbyid]/[profileid]',
            'server_ip.required' => 'Server IP or domain is required',
            'server_port.required' => 'Server port is required',
            'server_port.between' => 'Server port must be between 1 and 65535',
            'lobby_code.required' => 'Lobby code is required',
            'join_command.required' => 'Join command is required',
            'match_name.required' => 'Match name is required',
        ];
    }
}
