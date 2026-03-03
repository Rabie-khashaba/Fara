<?php

return [
    'auto_create_users' => env('SOCIAL_AUTH_AUTO_CREATE_USERS', false),
    'default_user_type' => env('SOCIAL_AUTH_DEFAULT_USER_TYPE', 'admin'),
    'default_user_active' => env('SOCIAL_AUTH_DEFAULT_USER_ACTIVE', true),
];
