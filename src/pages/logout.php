<?php

declare(strict_types=1);

require __DIR__ . '/../includes/session.php';

// Log out and redirect to homepage
aulanet_logout_user();
header('Location: ../index.php');
exit;
