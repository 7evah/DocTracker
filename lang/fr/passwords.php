<?php

return [

    'reset' => 'Votre mot de passe a été réinitialisé.',
    'sent' => 'Si un compte existe pour cette adresse, un mot de passe temporaire vient d’y être envoyé.',
    'throttled' => 'Veuillez patienter avant de réessayer.',
    'token' => 'Ce jeton de réinitialisation est invalide ou expiré.',
    'user' => 'Aucun compte ne correspond à cette adresse e-mail.',

    /*
    | The forgot-password flow mails a one-off password instead of a reset
    | link, so the wording has to carry three things: what the string is, how
    | long it lasts, and that the existing password still works (§4).
    */
    'temporary' => [
        'intro' => 'Voici un mot de passe temporaire pour vous reconnecter à DocFlow :',
        'expires' => 'Il est valable :minutes minutes et ne peut servir qu’une fois.',
        'then' => 'Après connexion, vous serez invité à choisir un nouveau mot de passe.',
        'unaffected' => 'Si vous n’êtes pas à l’origine de cette demande, ignorez ce message : votre mot de passe actuel reste valable.',
    ],

    'change' => [
        'title' => 'Nouveau mot de passe',
        'heading' => 'Choisissez un nouveau mot de passe',
        'intro' => 'Vous vous êtes connecté avec un mot de passe temporaire. Choisissez-en un nouveau pour continuer.',
        'password' => 'Nouveau mot de passe',
        'confirmation' => 'Confirmer le mot de passe',
        'submit' => 'Enregistrer et continuer',
        'done' => 'Mot de passe mis à jour.',
        'reused' => 'Choisissez un mot de passe différent du mot de passe temporaire.',
    ],

];
