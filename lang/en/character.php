<?php

return [
    'list_title' => 'My characters',
    'empty'      => "You don't have any characters yet. Create one in the game!",
    'view'       => 'View',
    'back'       => 'Back to list',

    // columns
    'class'  => 'Class',
    'name'   => 'Name',
    'resets' => 'Resets',
    'level'  => 'Level',
    'points' => 'Points',
    'master_points' => 'Master points',
    'kills'  => 'PK score',
    'actions' => 'Actions',

    // actions
    'add_points' => 'Add points',
    'rename'     => 'Rename',
    'reset'      => 'Reset',
    'clear_pk'   => 'Clear PK',
    'unstick'    => 'Move to town',
    'delete'     => 'Delete',
    'save'       => 'Save',
    'cancel'     => 'Cancel',

    'rename_label'   => 'New name (max 10 chars, letters & numbers)',
    'reset_confirm'  => 'Reset this character? Its level goes back to 1.',
    'clearpk_confirm' => 'Clear this character\'s PK status?',
    'unstick_confirm' => 'Move this character to the safe town?',

    'delete_title'   => 'Delete character',
    'delete_warning' => 'The character will be locked and its name freed. Enter your security code to confirm.',
    'security_code'  => 'Security code',

    'offline_note'   => 'The character must be offline to perform actions.',
    'stale_note'     => 'Stats update when you log out of the game. While online, the data may be delayed.',

    // success
    'nothing_to_add' => 'No points to add.',
    'points_added'   => 'Points added.',
    'renamed'    => 'Character renamed.',
    'reset_done' => 'Character reset.',
    'pk_cleared' => 'PK status cleared.',
    'unstuck'    => 'Character moved to town.',
    'deleted'    => 'Character deleted.',

    // errors
    'err_name_length' => 'Name must be 1 to 10 characters.',
    'err_name_chars'  => 'Name may only contain letters and numbers.',
    'err_name_taken'  => 'That name is already taken.',
    'err_reset_level' => 'Level :level is required to reset.',
    'err_safezone'    => 'Safe map not found.',
    'err_online'      => 'The character is online. Log out of the game first.',
    'err_security_code' => 'Incorrect security code.',
    'err_not_enough_points' => 'Not enough level-up points.',
    'err_unreachable' => 'The game server is unreachable. Please try again.',
];
