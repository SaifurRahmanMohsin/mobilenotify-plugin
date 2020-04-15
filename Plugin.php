<?php namespace Mohsin\Notify;

use Log;
use App;
use Lang;
use Flash;
use Event;
use Backend;
use FCMGroup;
use ApplicationException;
use System\Classes\PluginBase;
use Mohsin\Notify\Classes\FCMManager;
use Illuminate\Foundation\AliasLoader;
use GuzzleHttp\Exception\ClientException;
use RainLab\User\Models\User as UserModel;
use RainLab\User\Models\UserGroup as UserGroupModel;
use RainLab\User\Controllers\Users as UsersController;

/**
 * Notify Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * @var array Plugin dependencies
     */
    public $require = ['RainLab.User'];

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

            // For Single Notification
            $model->addFillable([
                'fcm_token'
            ]);

            // For Group Notification
            $model->bindEvent('model.afterSave', function () use ($model) {
                if (!empty($model->fcm_token)) {
                    foreach ($model->groups as $group) {
                        try {
                            $fcm_tokens = array_unique(array_column($group->users()->get(['fcm_token'])->toArray(), 'fcm_token'), SORT_STRING);
                            $groupSlug = $group->code;
                            if (empty($group->fcm_token)) {
                                $groupKey = FCMGroup::createGroup($groupSlug, $fcm_tokens);
                                $group->fcm_token = $groupKey;
                                $group->fcm_tokens = $fcm_tokens;
                                $group->save();
                            } else {
                                $newKeys = array_except($fcm_tokens, $group->fcm_tokens);
                                $groupKey = FCMGroup::addToGroup($groupSlug, $group->fcm_token, $newKeys);
                                $existingtokens = $group->fcm_tokens;
                                $group->fcm_token = $groupKey;
                                $group->fcm_tokens = array_merge($existingtokens, $newKeys);
                                $group->save();
                            }
                        } catch (ClientException $ex) {
                            Log::error($ex->getMessage());
                        }
                    }
                }
            });
        });

        UserGroupModel::extend(function ($model) {
            $model->addCasts([
                'fcm_tokens' => 'array',
            ]);
        });
    }

    protected function extendUsersController()
    {
        UsersController::extend(function ($controller) {
            $controller->addDynamicMethod('onSendMessage', function () use ($controller) {
                return $controller->makePartial('$/mohsin/notify/assets/partials/sendForm.htm', ['isToGroup' => false ]);
            });

            $controller->addDynamicMethod('onSendGroupMessage', function () use ($controller) {
                return $controller->makePartial('$/mohsin/notify/assets/partials/sendForm.htm', ['isToGroup' => true, 'controller' => $controller ]);
            });

            $controller->addDynamicMethod('onSendNotification', function () use ($controller) {

                $data = post();
                $userId = array_get($data, 'user_id');
                $message = array_get($data, 'notification_message');

                if (empty($message)) {
                    throw new ApplicationException(Lang::get('mohsin.notify::lang.notify.empty_message'));
                }

                $user = UserModel::find($userId);
                $token = $user->fcm_token;
                FCMManager::instance()->sendMessage($token, null, $message);
                Flash::success('Notification Sent!');
            });

            $controller->addDynamicMethod('onSendGroupNotification', function () use ($controller) {

                $data = post();
                $userId = array_get($data, 'user_id');
                $message = array_get($data, 'notification_message');
                $groupId = array_get($data, 'user_group');

                if (empty($message)) {
                    throw new ApplicationException(Lang::get('mohsin.notify::lang.notify.empty_message'));
                }

                $group = UserGroupModel::find($groupId);
                FCMManager::instance()->sendMessageToGroup($group->fcm_token, null, $message);
                Flash::success('Notification sent to group!');
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

    /**
     * Registers API nodes exposed by this plugin.
     *
     * @return array
     */
    public function registerNodes()
    {
        return [
            'account/update_fcm/{token}' => [
                'controller' => 'Mohsin\Notify\Http\FcmToken@update',
                'action'     => 'store',
                'middleware' => 'api'
            ],
        ];
    }
}
