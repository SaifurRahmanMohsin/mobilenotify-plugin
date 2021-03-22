<?php return [
    'plugin' => [
        'name' => 'Notify',
        'description' => 'Mobile plugin to manage notifications'
    ],
    'user' => [
        'fcm_token'           => 'FCM Token',
        'notification_sent'   => 'Notification Sent!',
        'sent_to_group'       => 'Notification sent to group!',
        'send_notification'   => 'Send Notification',
        'send_to_group'       => 'Send to Group',
        'target_group'        => 'Target Group',
        'message'             => 'Message',
        'send'                => 'Send',
        'cancel'              => 'Cancel',
        'error_missing_token' => 'Error! This feature works only for users who have an FCM token associated with them.'
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
