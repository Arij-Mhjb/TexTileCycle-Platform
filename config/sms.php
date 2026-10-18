<?php

/*
| Envoi de SMS (module Fournisseurs : identifiants du compte fournisseur).
| SMS_DRIVER=log    → le SMS est enregistré (démo, aucun envoi réel)
| SMS_DRIVER=twilio → envoi réel via l'API REST Twilio
*/
return [
    'driver' => env('SMS_DRIVER', 'log'),
    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '216'),
    'twilio' => [
        'sid' => env('TWILIO_ACCOUNT_SID'),
        'token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],
];
