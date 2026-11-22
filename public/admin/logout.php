<?php
declare(strict_types=1);

require __DIR__ . '/../../app/bootstrap.php';

start_session();
logout();
start_session();
flash('Du bist abgemeldet. A presto!');
redirect('/admin/login.php');
