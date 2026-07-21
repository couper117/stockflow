<?php

return [

    // Generic on purpose: never reveal whether the TIN, email, or password was
    // wrong (see CLAUDE.md §8).
    'failed' => 'Invalid TIN, email, or password.',
    'password' => 'The provided password is incorrect.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'unauthenticated' => 'You must be signed in to do that.',
    'logged_in' => 'Signed in successfully.',
    'logged_out' => 'Signed out successfully.',

];
