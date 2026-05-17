<?php

// Navigation component — outputs nav HTML based on session role
// Expects session helpers to be loaded: aulanet_is_logged_in(), aulanet_user_role(), aulanet_get_user().

$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$parent = dirname($scriptDir);
$siteRoot = (basename($scriptDir) === 'pages') ? $parent : $scriptDir;

// helper to build full path
$P = function (string $path) use ($siteRoot) {
    return $siteRoot . $path;
};

$user = aulanet_get_user();
$logged = aulanet_is_logged_in();
$role = aulanet_user_role();
?>
<nav>
    <div class="nav-container">
        <a href="<?php echo htmlspecialchars($P('/index.php'), ENT_QUOTES, 'UTF-8'); ?>" class="logo">
            <i data-lucide="graduation-cap" class="logo-icon"></i>
            <span class="logo-text">AulaNet</span>
        </a>
        <div class="nav-links">
            <?php if (!$logged) : ?>
                <a href="<?php echo htmlspecialchars($P('/pages/login.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">Login</a>
                <a href="<?php echo htmlspecialchars($P('/pages/register.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link primary">Register</a>
            <?php else : ?>
                <a href="<?php echo htmlspecialchars($P('/pages/home.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
                    <i data-lucide="home"></i>
                    <span class="nav-text">Home</span>
                </a>
                <a href="<?php echo htmlspecialchars($P('/pages/create-question.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
                    <i data-lucide="plus-circle"></i>
                    <span class="nav-text">Ask Question</span>
                </a>
                <a href="<?php echo htmlspecialchars($P('/pages/profile.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
                    <i data-lucide="user"></i>
                    <span class="nav-text"><?php echo htmlspecialchars($user['name'] ?? 'Profile', ENT_QUOTES, 'UTF-8'); ?></span>
                </a>
                <?php if ($role === 'admin') : ?>
                    <a href="<?php echo htmlspecialchars($P('/pages/admin.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link">
                        <i data-lucide="shield"></i>
                        <span class="nav-text">Admin</span>
                    </a>
                <?php endif; ?>
                <a href="<?php echo htmlspecialchars($P('/pages/logout.php'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-link logout">
                    <i data-lucide="log-out"></i>
                    <span class="nav-text">Logout</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<?php

// Ensure icons initialize when included
echo "<script>lucide.createIcons();</script>";

?>