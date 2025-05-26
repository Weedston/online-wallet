   <nav>
    <ul>
        <li><a href="/dashboard"><?= htmlspecialchars($translations['menuwallet_dashboard']) ?></a></li>
        <li><a href="/transfer"><?= htmlspecialchars($translations['menuwallet_transfer']) ?></a></li>
        <li><a href="/support"><?= htmlspecialchars($translations['menuwallet_support']) ?></a></li>
        <li><a href="/p2p" class="p2p-link"><?= htmlspecialchars($translations['menuwallet_p2pch']) ?></a></li>
		<li><a href="p2p-profile"><?= htmlspecialchars($translations['p2p_menu_profile']) ?></a></li>
        <li><a href="/logout"><?= htmlspecialchars($translations['menuwallet_logout']) ?></a></li>
        <?php
        if (isset($_SESSION['admin']) ) {
            echo '<li><a href="/adm_support">Admin Support</a></li>';        
        }
        ?>
    </ul>
	</nav>