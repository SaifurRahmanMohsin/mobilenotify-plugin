<?php namespace Mohsin\Notify;

use App;
use Lang;
use Flash;
use Event;
use Backend;
use ApplicationException;
use System\Classes\PluginBase;
use Mohsin\Notify\Classes\FCMManager;
use Illuminate\Foundation\AliasLoader;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Controllers\Users as UsersController;

/**
 * Notify Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'mohsin.notify::lang.plugin.name',
            'description' => 'mohsin.notify::lang.plugin.description',
            'author'      => 'Saifur Rahman Mohsin',
            'icon'        => 'icon-bullhorn'
        ];
    }

    /**
     * Boot method, called right before the request route.
     *
     * @return array
     */
    public function boot()
    {
        FCMManager::instance()->refreshConfig();

        App::register('\LaravelFCM\FCMServiceProvider');

        $facade = AliasLoader::getInstance();
        $facade->alias('FCM', '\LaravelFCM\Facades\FCM');
        $facade->alias('FCMGroup', '\LaravelFCM\Facades\FCMGroup');

        $this->extendUserApi();
        $this->extendUserModel();
        $this->extendUsersController();
    }

    protected function extendUserApi()
    {
        Event::listen('mohsin.user.afterAuthenticate', function ($provider, $user) {
            $fcm_token = array_get(post(), 'extras.fcm_token', '');
            $user->fcm_token = $fcm_token;
            $user->save();
        });
    }

    protected function extendUserModel()
    {
        UserModel::extend(function ($model) {
            $model->addFillable([
                'fcm_token'
            ]);
        });
    }

    protected function extendUsersController()
    {
        UsersController::extend(function ($controller) {
            $controller->addDynamicMethod('onSendMessage', function () use ($controller) {
                return $controller->makePartial('$/mohsin/notify/assets/partials/sendForm.htm');
            });

            $controller->addDynamicMethod('onSendNotification', function () use ($controller) {
                $data = post();
                $userId = array_get($data, 'user_id');
                $title = array_get($data, 'notification_title');
                $message = array_get($data, 'notification_message');

                if (empty($title)) {
                    throw new ApplicationException(Lang::get('mohsin.notify::lang.notify.empty_title'));
                }
                if (empty($message)) {
                    throw new ApplicationException(Lang::get('mohsin.notify::lang.notify.empty_message'));
                }

                $user = UserModel::find($userId);
                $token = $user->fcm_token;
                FCMManager::instance()->sendMessage($token, $title, $message);

                Flash::success('Notification Sent!');
            });
        });

        UsersController::extendFormFields(function ($widget) {

            if (!$widget->model instanceof UserModel) {
                return;
            }

            $widget->addTabFields([
                'fcm_token' => [
                    'label' => 'FCM Token',
                    'span'  => 'full'
                ]
            ]);

            $widget->addSecondaryTabFields([
                '_send_message' => [
                    'context' => 'preview',
                    'type'    => 'partial',
                    'path'    => '$/mohsin/notify/assets/partials/btnSend.htm'
                ]
            ]);
        });
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'mohsin.notify.allow_notification' => [
                'tab' => 'mohsin.notify::lang.plugin.name',
                'label' => 'Allow sending notification to users'
            ],
            'mohsin.notify.manage_settings' => [
                'tab' => 'mohsin.notify::lang.plugin.name',
                'label' => 'Manage the settings'
            ]
        ];
    }

    /**
     * Registers settings controller for this plugin.
     *
     * @return array
     */
    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'mohsin.notify::lang.settings.name',
                'description' => 'mohsin.notify::lang.settings.description',
                'category'    => 'Mobile',
                'icon'        => 'icon-bullhorn',
                'class'       => 'Mohsin\Notify\Models\Settings',
                'order'       => 502,
                'permissions' => ['mohsin.notify.access_settings'],
            ]
        ];
    }
}
