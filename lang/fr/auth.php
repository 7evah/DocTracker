<?php

return [

    /*
    | Deliberately says "or", and never which of the two was wrong: telling
    | someone the address exists but the password is wrong turns the login
    | form into a way of discovering who holds an account (§39). Being vague
    | is the point — being inaccurate was not. The previous wording claimed
    | the credentials matched *no account*, which reads as "this address is
    | unknown" to somebody who has simply mistyped their password.
    */
    'failed' => 'Adresse e-mail ou mot de passe incorrect.',
    'password' => 'Le mot de passe est incorrect.',
    'throttle' => 'Trop de tentatives de connexion. Réessayez dans :seconds secondes.',
    'inactive' => 'Ce compte est désactivé. Contactez un administrateur.',

    'login' => [
        'title' => 'Connexion',
        'heading' => 'Connexion à DocFlow',
        'subheading' => 'Accédez au suivi des documents techniques et des approbations.',
        'email' => 'Adresse e-mail',
        'password' => 'Mot de passe',
        'remember' => 'Se souvenir de moi',
        'forgot' => 'Mot de passe oublié ?',
        'submit' => 'Se connecter',
    ],

    'forgot_password' => [
        'title' => 'Mot de passe oublié',
        'intro' => 'Indiquez votre adresse e-mail : nous vous enverrons un lien de réinitialisation.',
        'submit' => 'Envoyer le lien',
    ],

    'reset_password' => [
        'title' => 'Réinitialiser le mot de passe',
        'password' => 'Nouveau mot de passe',
        'confirm' => 'Confirmer le mot de passe',
        'submit' => 'Réinitialiser',
    ],

    'confirm_password' => [
        'title' => 'Confirmer le mot de passe',
        'intro' => 'Cette zone est sécurisée. Confirmez votre mot de passe pour continuer.',
        'submit' => 'Confirmer',
    ],

    'verify_email' => [
        'title' => 'Vérifier l’adresse e-mail',
        'intro' => 'Un lien de vérification vient de vous être envoyé.',
        'sent' => 'Un nouveau lien de vérification a été envoyé.',
        'resend' => 'Renvoyer le lien',
    ],

    'profile' => [
        'title' => 'Profil',
        'information' => 'Informations personnelles',
        'information_hint' => 'Mettez à jour vos informations et votre adresse e-mail.',
        'password' => 'Mot de passe',
        'password_hint' => 'Utilisez un mot de passe long et unique.',
        'current_password' => 'Mot de passe actuel',
        'new_password' => 'Nouveau mot de passe',
        'saved' => 'Enregistré.',
    ],

];
