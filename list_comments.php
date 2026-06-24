<?php
$dirs = ['app', 'database/migrations'];
$filesWithComments = [];

$iter = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator(__DIR__)
);

foreach ($iter as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $path = $file->getPathname();
        
        $inDir = false;
        foreach ($dirs as $dir) {
            if (strpos($path, __DIR__ . DIRECTORY_SEPARATOR . $dir) === 0) {
                $inDir = true;
                break;
            }
        }
        if (!$inDir) continue;

        $content = file_get_contents($path);
        $tokens = token_get_all($content);
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[0] === T_COMMENT || $token[0] === T_DOC_COMMENT) {
                    $filesWithComments[] = str_replace(__DIR__ . DIRECTORY_SEPARATOR, '', $path);
                    break;
                }
            }
        }
    }
}
echo implode(PHP_EOL, $filesWithComments);
