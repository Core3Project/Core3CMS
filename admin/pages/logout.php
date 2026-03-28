<?php
Auth::logout();
header('Location: ' . dirname($_SERVER['SCRIPT_NAME']) . '/login');
exit;
