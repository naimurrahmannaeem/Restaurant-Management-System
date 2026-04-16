<?php
require_once __DIR__ . '/../config/db.php';
session_destroy();
redirect('/restaurant/admin/login.php');
