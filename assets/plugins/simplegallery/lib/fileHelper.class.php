<?php

class fileHelper {
    /**
     * @param array $ids
     * @param $folder
     */
    static  function clearFolders($ids = array(), $folder) {
        foreach ($ids as $id) self::rmDir($folder.$id.'/');
    }

    /**
     * @param $dirPath
     */
    static function rmDir($dirPath) {
        if (is_dir($dirPath)) {
            foreach(new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dirPath, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::CHILD_FIRST) as $path) {
                $path->isDir() ? rmdir($path->getPathname()) : unlink($path->getPathname());
            }
            rmdir($dirPath);
        }
    }

    /**
     * @param $file
     * @return string
     */
    static function getInexistantFilename($file) {
        list($dir, $file, $fileext, $filename) = array_values(pathinfo($file));
        $i = 1;
        while (file_exists("{$dir}/{$file}")) {
            $i++;
            $file = "{$filename}({$i}).{$fileext}";
        }
        return $file;
    }
}