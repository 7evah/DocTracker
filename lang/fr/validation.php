<?php

/*
| Only the rules DocFlow actually uses are translated here. Anything missing
| falls back to Laravel's built-in English messages (fallback_locale=en),
| so an untranslated rule degrades to readable text rather than a raw key.
*/

return [

    'accepted' => 'Le champ :attribute doit être accepté.',
    'after' => 'Le champ :attribute doit être une date postérieure au :date.',
    'after_or_equal' => 'Le champ :attribute doit être une date postérieure ou égale au :date.',
    'array' => 'Le champ :attribute doit être un tableau.',
    'before' => 'Le champ :attribute doit être une date antérieure au :date.',
    'before_or_equal' => 'Le champ :attribute doit être une date antérieure ou égale au :date.',
    'boolean' => 'Le champ :attribute doit être vrai ou faux.',
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le champ :attribute n’est pas une date valide.',
    'declined' => 'Le champ :attribute doit être refusé.',
    'different' => 'Les champs :attribute et :other doivent être différents.',
    'digits' => 'Le champ :attribute doit contenir :digits chiffres.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',
    'file' => 'Le champ :attribute doit être un fichier.',
    'filled' => 'Le champ :attribute doit avoir une valeur.',
    'image' => 'Le champ :attribute doit être une image.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'integer' => 'Le champ :attribute doit être un entier.',
    'max' => [
        'array' => 'Le champ :attribute ne peut contenir plus de :max éléments.',
        'file' => 'Le fichier :attribute ne peut pas dépasser :max kilo-octets.',
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
    ],
    'mimes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'mimetypes' => 'Le champ :attribute doit être un fichier de type : :values.',
    'min' => [
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'file' => 'Le fichier :attribute doit faire au moins :min kilo-octets.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'present' => 'Le champ :attribute doit être présent.',
    'regex' => 'Le format du champ :attribute est invalide.',
    'required' => 'Le champ :attribute est obligatoire.',
    'required_if' => 'Le champ :attribute est obligatoire quand :other vaut :value.',
    'required_with' => 'Le champ :attribute est obligatoire quand :values est présent.',
    'same' => 'Les champs :attribute et :other doivent correspondre.',
    'size' => [
        'array' => 'Le champ :attribute doit contenir :size éléments.',
        'file' => 'Le fichier :attribute doit faire :size kilo-octets.',
        'numeric' => 'Le champ :attribute doit valoir :size.',
        'string' => 'Le champ :attribute doit contenir :size caractères.',
    ],
    'string' => 'Le champ :attribute doit être une chaîne de caractères.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'uploaded' => 'Le téléversement du fichier :attribute a échoué.',
    'url' => 'Le champ :attribute doit être une URL valide.',

    'custom' => [
        'document_number' => [
            'unique' => 'Ce numéro de document existe déjà pour ce projet.',
        ],
    ],

    /*
    | Field names substituted into :attribute, so messages read naturally
    | in French rather than exposing snake_case column names.
    */
    'attributes' => [
        'name' => 'nom',
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'password_confirmation' => 'confirmation du mot de passe',
        'current_password' => 'mot de passe actuel',
        'department' => 'département',
        'phone' => 'téléphone',
        'job_title' => 'fonction',
        'avatar' => 'photo de profil',
        'title' => 'titre',
        'description' => 'description',
        'project_id' => 'projet',
        'discipline_id' => 'discipline',
        'document_number' => 'numéro de document',
        'revision' => 'révision',
        'version_notes' => 'notes de version',
        'file' => 'fichier',
        'reviewer_id' => 'vérificateur',
        'approver_id' => 'approbateur',
        'assigned_to' => 'personne affectée',
        'deadline' => 'échéance',
        'due_date' => 'date d’échéance',
        'priority' => 'priorité',
        'status' => 'statut',
        'comment' => 'commentaire',
        'project_code' => 'code projet',
        'client' => 'client',
        'location' => 'localisation',
        'manager_id' => 'chef de projet',
        'start_date' => 'date de début',
        'end_date' => 'date de fin',
    ],

];
