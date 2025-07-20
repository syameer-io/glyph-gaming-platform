<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Services\SteamApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SteamAuthController extends Controller
{
    protected $steamApiService;

    public function __construct(SteamApiService $steamApiService)
    {
        $this->steamApiService = $steamApiService;
    }

    public function redirect()
    {
        $params = [
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => config('services.steam.callback_url'),
            'openid.realm' => config('services.steam.callback_url'),
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
        ];

        $steamUrl = 'https://steamcommunity.com/openid/login?' . http_build_query($params);
        
        return redirect($steamUrl);
    }

    public function callback(Request $request)
    {
        $params = [
            'openid.assoc_handle' => $request->get('openid_assoc_handle'),
            'openid.signed' => $request->get('openid_signed'),
            'openid.sig' => $request->get('openid_sig'),
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'check_authentication',
        ];

        $signed = explode(',', $request->get('openid_signed'));
        
        foreach ($signed as $item) {
            $val = $request->get('openid_' . str_replace('.', '_', $item));
            $params['openid.' . $item] = $val;
        }

        $response = $this->steamApiService->validateOpenId($params);

        if (strpos($response, 'is_valid:true') !== false) {
            $claimedId = $request->get('openid_claimed_id');
            $steamId = substr($claimedId, strrpos($claimedId, '/') + 1);

            $user = Auth::user();
            $user->update(['steam_id' => $steamId]);

            // Fetch initial Steam data
            $this->steamApiService->fetchUserData($user);

            return redirect()->route('dashboard')->with('success', 'Steam account linked successfully!');
        }

        return redirect()->route('dashboard')->with('error', 'Failed to link Steam account.');
    }

    public function showLinkPage()
    {
        return view('steam.link');
    }
}