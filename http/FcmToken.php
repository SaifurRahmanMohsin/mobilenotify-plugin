<?php namespace Mohsin\Notify\Http;

use Backend\Classes\Controller;
use RainLab\User\Models\User;

/**
 * Fcm Token Back-end Controller
 */
class FcmToken extends Controller
{
    public $implement = [
        'Mohsin.Rest.Behaviors.RestController'
    ];

    public $restConfig = 'config_rest.yaml';

    public function update($token)
    {
        $data = request()->headers->all();
        $user_id = array_get($data, 'user-id')[0];
        $user = User::find($user_id);
        if ($user == null) {
            return response()->json(['data' => 'invalid-user'], 401);
        } else {
            $user -> fcm_token = $token;
            $user -> save();
            return response()->json(['data' => 'success']);
        }
    }
}
