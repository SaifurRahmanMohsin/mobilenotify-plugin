<?php namespace Mohsin\Notify\Classes;

use FCM;
use Event;
use BackendAuth;
use Carbon\Carbon;
use Mohsin\Notify\Models\Settings;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;

/**
 * FCM Manager.
 *
 * @package Mohsin.Notify
 * @author Saifur Rahman Mohsin
 */
class FCMManager
{
    use \October\Rain\Support\Traits\Singleton;

    public function refreshConfig()
    {
        $config = app()['config']->get('fcm', []);
        $newConfig = [
            'driver' => 'http',
            'log_enabled' => Settings::get('logging_enabled', false),
            'http' => [
                'server_key' => Settings::get('server_key'),
                'sender_id' => Settings::get('sender_id'),
                'server_send_url' => 'https://fcm.googleapis.com/fcm/send',
                'server_group_url' => 'https://android.googleapis.com/gcm/notification',
                'timeout' => 30.0, // in second
            ]
        ];
        app()['config']->set('fcm', array_merge($newConfig, $config));
    }

    public function sendMessage($token, $title, $message)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $payloadData = [
            'message' => $message,
            'created_by' => BackendAuth::getUser()->id,
            'created_at' => Carbon::now()->toIso8601String()
        ];

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData($payloadData);

        $option = $optionBuilder->build();
        $data = $dataBuilder->build();

        Event::fire('mohsin.notify.notification_sent', [$payloadData]);
        $downstreamResponse = FCM::sendTo($token, $option, null, $data);
    }

    public function sendMessageToGroup($token, $title, $message)
    {
        $optionBuilder = new OptionsBuilder();
        $optionBuilder->setTimeToLive(60*20);

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'message' => $message,
            'created_by' => BackendAuth::getUser()->id,
            'created_at' => Carbon::now()->toIso8601String()
        ]);

        $option = $optionBuilder->build();
        $data = $dataBuilder->build();

        $downstreamResponse = FCM::sendToGroup($token, $option, null, $data);
    }
}
