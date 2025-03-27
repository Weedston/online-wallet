   <nav>
    <ul>
        <li><a href="/dashboard">Dashboard</a></li>
        <li><a href="/transfer">Transfer</a></li>
        <li><a href="/support">Support</a></li>
        <li><a href="/p2p" class="p2p-link">P2P Market</a></li> <!-- Выделение этой ссылки -->
        <li><a href="/logout">Logout</a></li>
        <?php
        if (isset($_SESSION['admin']) || $_SESSION['admin'] == true) {
            echo '<li><a href="/adm_support">Admin Support</a></li>';        
        }
        ?>
    </ul>
	</nav>