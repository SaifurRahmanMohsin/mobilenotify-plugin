<?php namespace Mohsin\Notify\Models;

use Model;
use Mohsin\Notify\Classes\FCMManager;

/**
 * Settings Model
 */
class Settings extends Model
{
    public $implement = ['System.Behaviors.SettingsModel'];

    public $settingsCode = 'mohsin_notify_settings';

    public $settingsFields = 'fields.yaml';

    public function afterSave()
    {
        FCMManager::instance()->refreshConfig();
    }
}
