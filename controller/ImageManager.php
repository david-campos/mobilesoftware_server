<?php
/**
 * Created by PhpStorm.
 * User: David Campos R.
 * Date: 20/01/2017
 * Time: 15:56
 */

namespace controller;


use Exception;
use exceptions\CouldntMoveImageException;

class ImageManager
{
    /**
     * Directory where the images are saved, to be safe the images should be saved in another
     * server, but by now we will leave it this way.
     */
    public static const IMAGE_DIR = '/../public_html/profile_pics/';
    public static const IMAGE_IDX_FILE = 'idx.txt';
    public static const IMAGE_LOG_FILE = 'log.txt';
    public static const IMAGE_EXTENSIONS = 'png,jpg,jpeg';
    public static const IMAGE_EXTENSIONS_PATTERN = '/^(png|jpg|jpeg)$/i';
    public static const FIELD_NAME = "picture";

    public function saveUploadedImage(): int {
        $extension = $this->getImageExtension();
        if ($this->isExtensionValid($extension) &&
            $this->isNotFakeImage($_FILES[static::FIELD_NAME]['tmp_name']) &&
            $this->isFileSizeValid($_FILES[static::FIELD_NAME]['size'])
        ) {
            do {
                $nextIdx = $this->getNextIdx();
                $targetFile = dirname(__FILE__) . static::IMAGE_DIR . "img$nextIdx.$extension";
                if ($repeated = file_exists($targetFile)) {
                    file_put_contents(
                        dirname(__FILE__) . static::IMAGE_DIR . static::IMAGE_LOG_FILE,
                        date('Y-m-d H:i:s') . "\tRepeated idx\t$nextIdx" . PHP_EOL,
                        FILE_APPEND | LOCK_EX);
                }
            } while ($repeated);

            if (!move_uploaded_file($_FILES[static::FIELD_NAME]['tmp_name'], $targetFile)) {
                throw new CouldntMoveImageException();
            }

            return $nextIdx;
        } else
            return 0;
    }

    private function getImageExtension(): string {
        return pathinfo(basename($_FILES[static::FIELD_NAME]['name']), PATHINFO_EXTENSION);
    }

    private function isExtensionValid(string $ext): bool {
        return preg_match(static::IMAGE_EXTENSIONS_PATTERN, $ext) === 1;
    }

    private function isFileSizeValid(int $fileSize) {
        return $fileSize < 500000;
    }

    private function isNotFakeImage(string $name) {
        return getimagesize($name) !== false;
    }

    private function getNextIdx(): int {
        $file_idx_route = dirname(__FILE__) . static::IMAGE_DIR . static::IMAGE_IDX_FILE;
        $idx = 0;
        if (file_exists($file_idx_route)) {
            $fp = fopen($file_idx_route, 'r+');
        } else {
            $fp = fopen($file_idx_route, 'w');
            $idx = (int)1;
        }
        if (flock($fp, LOCK_EX)) {
            if ($idx === 0) {
                $idx = (int)fread($fp, filesize($file_idx_route));
            }
            ftruncate($fp, 0);
            fseek($fp, 0, SEEK_SET);
            fwrite($fp, $idx + 1);
            flock($fp, LOCK_UN);
            fclose($fp);
        } else {
            fclose($fp);
            throw new Exception('Unable to lock image index file.');
        }
        return $idx;
    }
}