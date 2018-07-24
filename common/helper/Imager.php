<?php
/**
 * Created by Sergey Estrin
 */
namespace common\helper;
use Yii;
use yii\base\BaseObject;


/**
 * Class DataHelper
 * @package common\components\helpers
 */
class Imager extends BaseObject
{
    /**
     * @param $imagePath
     * @param $imagePathResize
     * @param $maxWidth
     * @param $maxHeight
     * @return mixed
     */
    public static function imageResize($imagePath, $imagePathResize, $maxWidth, $maxHeight){
        $exifData = self::getExif($imagePath);
        /**
        switch(orientation)
        {
            case 1:
                // No rotation required.
                return URI;
            case 2:
                bmp.RotateFlip(RotateFlipType.RotateNoneFlipX);
                break;
            case 3:
                bmp.RotateFlip(RotateFlipType.Rotate180FlipNone);
                break;
            case 4:
                bmp.RotateFlip(RotateFlipType.Rotate180FlipX);
                break;
            case 5:
                bmp.RotateFlip(RotateFlipType.Rotate90FlipX);
                break;
            case 6:
                bmp.RotateFlip(RotateFlipType.Rotate90FlipNone);
                break;
            case 7:
                bmp.RotateFlip(RotateFlipType.Rotate270FlipX);
                break;
            case 8:
                bmp.RotateFlip(RotateFlipType.Rotate270FlipNone);
                break;
        }
         */
        $image = imagecreatefromstring(file_get_contents($imagePath));
        $imageWidth  = imagesx($image);
        $imageHeight = imagesy($image);
        if ($imageWidth/$imageHeight > $maxWidth/$maxHeight){
            $maxHeight = $maxWidth * $imageHeight / $imageWidth;
        } else {
            $maxWidth = $maxHeight * $imageWidth / $imageHeight;
        }

        if ($imageWidth > $maxWidth) {
            $imageResize = imagecreatetruecolor($maxWidth, $maxHeight);
            imagecopyresized($imageResize, $image, 0, 0, 0, 0, $maxWidth, $maxHeight, $imageWidth, $imageHeight);
        } else{
            $imageResize = imagecreatefromstring(file_get_contents($imagePath));
        }
        switch ($exifData) {
            case '2':
                // RotateNoneFlipX
                imageflip($imageResize, IMG_FLIP_HORIZONTAL);
                break;
            case '3':
                // Rotate180FlipNone
                $imageResize = imagerotate($imageResize, 180, 0);
                break;
            case '4':
                // Rotate180FlipX
                imageflip($imageResize, IMG_FLIP_HORIZONTAL);
                $imageResize = imagerotate($imageResize, 180, 0);
                break;
            case '5':
                // Rotate90FlipX
                imageflip($imageResize, IMG_FLIP_HORIZONTAL);
                $imageResize = imagerotate($imageResize, -90, 0);
                break;
            case '6':
                // Rotate90FlipNone
                $imageResize = imagerotate($imageResize, -90, 0);
                break;
            case '7':
                // Rotate270FlipX
                imageflip($imageResize, IMG_FLIP_HORIZONTAL);
                $imageResize = imagerotate($imageResize, -270, 0);
                break;
            case '8':
                // Rotate270FlipNone
                $imageResize = imagerotate($imageResize, -270, 0);
                break;

            default:
                break;
        }
        imagejpeg($imageResize, $imagePathResize);
        imagedestroy($image);
        imagedestroy($imageResize);
        if (!file_exists($imagePathResize)) return false;

        return true;
    } // end imageResize()
    /**
     * Make temporary file name
     * @param  string $path
     * @return int
     */
    public static function getExif($path)
    {
        static $hasExif;
//        $exif = @exif_read_data('/tmp/php9RB3SL.jpg');

        if ($hasExif === null)  {
            $hasExif = self::checkExifCommand();
            if (!$hasExif) {
                Yii::error('No exiftool on the server.', 'image_exif');
            }
        }
        $exif = $hasExif ? `exiftool -Orientation -n {$path}` : null;
        $exif = explode(':',$exif);
        if (!isset($exif[1])) {
            return null;
        }
        $exif = $exif[1];
        $exif = preg_replace("/[^0-9]/", '', $exif[1]);
        return $exif;
    } // end getExif()

    /**
     * @return bool
     */
    private static function checkExifCommand()
    {
        return (bool) strlen(`which exiftool`);
    } // end checkExifCommand()


}