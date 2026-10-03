<?php

session_set_cookie_params([
    'httponly' => true,
    'secure' => false,//va a cmabiar a true cuando sea https
    'samesite' => 'Lax'
]);

session_start();