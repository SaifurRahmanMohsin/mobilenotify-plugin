<?php namespace Mohsin\Notify\Classes;

use FCM;
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

        $notificationBuilder = new PayloadNotificationBuilder($title);
        $notificationBuilder->setBody($message)
                            ->setSound('default');

        $dataBuilder = new PayloadDataBuilder();
        // $dataBuilder->addData(['a_data' => 'my_data']);

        $option = $optionBuilder->build();
        $notification = $notificationBuilder->build();
        $data = $dataBuilder->build();

        $downstreamResponse = FCM::sendTo($token, $option, $notification, $data);
        // dd($downstreamResponse);

        $downstreamResponse->numberSuccess();
        $downstreamResponse->numberFailure();
        $downstreamResponse->numberModification();

        // return Array - you must remove all this tokens in your database
        $downstreamResponse->tokensToDelete();

        // return Array (key : oldToken, value : new token - you must change the token in your database)
        $downstreamResponse->tokensToModify();

        // return Array - you should try to resend the message to the tokens in the array
        $downstreamResponse->tokensToRetry();

        // return Array (key:token, value:error) - in production you should remove from your database the tokens
        $downstreamResponse->tokensWithError();
    }
}
