<?php

// All menu PDFs are listed here.
// key          => same value is stored in the "type" column of club_menu_pdf,
//                 and is used in the URL and in JS (example: "mgt7")
// s3_folder    => S3 folder (key prefix) where this PDF type is stored
// display_name => optional, file name shown in the link (null = original uploaded name)
// multiple     => optional, true = many entries (each one becomes its own menu item)
// menu_title   => used with "multiple", {label} is replaced by the text typed in admin
return [
    'mgt7' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'RWITC_MGT-7.pdf',
    ],

    'register_of_directors' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Register_of_Directors.pdf',
    ],

    'register_of_contracts_1' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Register_of_Contracts-I.pdf',
    ],

    'register_of_contracts_2' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Register_of_Contracts-Part-II.pdf',
    ],

    'agm_notice' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,
        'multiple'     => true,
        'menu_title'   => 'Notice for the {label} AGM',
    ],

    'annual_report' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,
        'multiple'     => true,
        'menu_title'   => '{label} Annual Report',
    ],


    'scrutinizer_report_agm' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,
        'multiple'     => true,
        'menu_title'   => 'Scrutinizer Report-{label} AGM',
    ],

    'scrutinizer_report_egm' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,
        'multiple'     => true,
        'menu_title'   => 'Scrutinizer Report-{label} EGM',
    ],

    'scrutinizer_report' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,
        'multiple'     => true,
        'menu_title'   => 'Scrutinizer Report-{label}',
    ],

    'egm_notice' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'EGM_Notice.pdf',
    ],

    'working_group' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Working_Group.pdf',
    ],

    'medication_rules' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Medication_Rules.pdf',
    ],

    'rules_of_racing' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Rules_of_Racing.pdf',
    ],

    'racing_calendar' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Racing_Calendar.pdf',
    ],   
    
    'jockey_riding_weight' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,   // original uploaded file name (with extension) is used
    ],

    'memorandum_articles' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Memorandum_Articles_of_Association.pdf',
    ],

    'ready_reckoner' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Ready_Reckoner.txt',
    ],

    'record_timings' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => null,   // original uploaded file name (with extension) is used
    ],

    'standard_timings' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Standard_Timings.pdf',
    ],

    'affiliated_clubs' => [
        's3_folder'    => 'uploads/club/',
        'display_name' => 'Affiliated_Clubs_with_RWITC.pdf',
    ],
];
