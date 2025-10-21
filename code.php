<?php
if (isset($_GET['code'])) {
    echo htmlspecialchars($_GET['code']);
} else {
    echo 'No code provided';
}
?>