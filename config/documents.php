<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Storage
    |--------------------------------------------------------------------------
    */

    'disk' => env('DOCUMENTS_DISK', 'documents'),

    /*
    | Root folder inside the disk. Files land at
    | documents/{project_id}/{document_id}/{revision}/{random}.{ext} (§53).
    */
    'root' => 'documents',

    /*
    |--------------------------------------------------------------------------
    | Upload constraints
    |--------------------------------------------------------------------------
    |
    | Extensions and MIME types are BOTH validated. Extension alone is
    | trivially spoofed; MIME alone is unreliable for CAD formats, which
    | browsers report inconsistently or as octet-stream (§32).
    */

    'max_size_kb' => (int) env('DOCUMENTS_MAX_SIZE_KB', 25600), // 25 MB

    'allowed_extensions' => [
        'pdf',
        'dwg', 'dxf',
        'doc', 'docx',
        'xls', 'xlsx',
        'ppt', 'pptx',
        'png', 'jpg', 'jpeg',
        'zip',
    ],

    /*
    | Accepted MIME types. CAD and Office formats are frequently reported as
    | application/octet-stream, so that type is allowed but only in
    | combination with an allowed extension.
    */
    'allowed_mimes' => [
        'application/pdf',
        'application/octet-stream',
        'image/vnd.dwg',
        'image/x-dwg',
        'application/acad',
        'application/dxf',
        'image/vnd.dxf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/png',
        'image/jpeg',
        'application/zip',
        'application/x-zip-compressed',
    ],

    /*
    | Types the in-browser viewer can render today. Everything else is
    | download-only (§33 — PDF preview first, other formats later).
    */
    'previewable_mimes' => [
        'application/pdf',
        'image/png',
        'image/jpeg',
    ],

];
