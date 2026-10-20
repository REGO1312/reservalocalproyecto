<?php
/**
 * Portal Cliente - Redirección a mis citas
 */

require_once dirname(__DIR__, 2) . '/app/bootstrap.php';
require_login();
redirect('/mis-citas.php');
