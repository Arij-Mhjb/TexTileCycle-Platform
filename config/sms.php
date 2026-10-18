<?php

/*
| Envoi de SMS (module Fournisseurs : identifiants du compte fournisseur).
| SMS_DRIVER=log    → le SMS est enregistré (démo, aucun envoi réel)
| SMS_DRIVER=twilio → envoi réel via l'API REST Twilio
| SMS_DRIVER=smsgate → envoi réel via l'API « SMS Gateway for Android » (sms-gate.app) :
|                     le SMS part de la carte SIM d'un téléphone Android (numéros tunisiens OK)
*/
return [
    'driver' => env('SMS_DRIVER', 'log'),
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '216'),
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
