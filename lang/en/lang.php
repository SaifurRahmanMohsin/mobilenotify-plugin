<?php return [
    'plugin' => [
        'name' => 'Notify',
        'description' => 'Mobile plugin to manage notifications'
    ],
    'settings' => [
        'name' => 'FCM Settings',
        'description' => 'Manage the FCM configuration.',
        'logging_enabled_label' => 'Logging Enabled?',
        'logging_enabled_comment' => 'Turn on if you want to log the FCM requests',
        'server_key_label' => 'Server Key',
        'server_key_comment' => 'Enter the FCM server key',
        'sender_id_label' => 'Sender ID',
        'sender_id_comment' => 'Enter the FCM sender ID',
    ]
];
