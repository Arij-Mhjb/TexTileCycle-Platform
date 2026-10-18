<?php

/*
| Envoi de SMS (module Fournisseurs : identifiants du compte fournisseur).
| SMS_DRIVER=log    → le SMS est enregistré (démo, aucun envoi réel)
| SMS_DRIVER=twilio → envoi réel via l'API REST Twilio
| SMS_DRIVER=smsgate → envoi réel via l'API « SMS Gateway for Android » (sms-gate.app) :
|                     le SMS part de la carte SIM d'un téléphone Android (numéros tunisiens OK)
| SMS_DRIVER=infobip → envoi réel via l'API SMS Infobip (essai gratuit : vers le numéro vérifié à l'inscription)
*/
return [
    'driver' => env('SMS_DRIVER', 'log'),
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '216'),
    'infobip' => [
        'base_url' => env('INFOBIP_BASE_URL'),
        'api_key' => env('INFOBIP_API_KEY'),
        'from' => env('INFOBIP_FROM', 'TexTileCycle'),
    ],
    'smsgate' => [
        'url' => env('SMSGATE_URL', 'https://api.sms-gate.app/3rdparty/v1/messages'),
        'username' => env('SMSGATE_USERNAME'),
        'password' => env('SMSGATE_PASSWORD'),
    ],
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],
];
