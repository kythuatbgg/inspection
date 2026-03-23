<?php
echo "open_basedir: " . ini_get("open_basedir");
echo " / exists: " . (file_exists(__DIR__."/../vendor/autoload.php") ? "YES" : "NO");
