<?php
function listFiles($dir) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));

    foreach ($rii as $file) {
        if ($file->isFile()) {
            $path = $file->getRealPath();
            // Skip unwanted folders
            if (strpos($path, DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR) !== false) continue;
            if (strpos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;

            echo $path . "<br>";
        }
    }
}

listFiles(__DIR__);
?>
