<?php
class EImageProcessor extends Exception {}
	class EImageProcessorNotEnoughMemory extends EImageProcessor {}

interface IImageProcessor
{
	public function CreateFittedImage($max_width, $max_height, $dest = null, $output_directly = false);
	public function CreateCroppedImage($dest_width, $dest_height, $left, $top, $ratio, $dest = null, $output_directly = false);
	public function CreateFittedAndCroppedImage($dest_width, $dest_height, $top = false, $dest = null, $output_directly = false);
	public function SetCallback($func, $method = null);
}

abstract class ImageProcessor implements IImageProcessor
{
	final public static function SrcImageUrl(stdClass $row, $dir, $fext = 'ext', $fkey = 'id') { return "$dir/image_{$row->$fkey}.{$row->$fext}"; }

	final public static function ImageUrl(stdClass $row, $host, $dir, $type = false, $w = false, $h = false, $fext = 'ext', $fkey = 'id')
	 {
		if($type)
		 {
			$type = "/$type";
			if($w) $type .= "/w$w";
			if($h) $type .= "/h$h";
		 }
		return self::GetHost($host)."$type$dir/image_{$row->$fkey}.{$row->$fext}";
	 }

	final public static function IUrl(stdClass $row, $host, $dir, $w, $h, $fext = 'ext', $fkey = 'id')
	 {
		return self::GetHost($host)."/$row->icon_type/w$w/h$h".('crop' === $row->icon_type ? "/left$row->crop_left/top$row->crop_top/ratio$row->crop_ratio" : '')."$dir/image_{$row->$fkey}.{$row->$fext}";
	 }

	final public static function Create($src, &$image_info = null)
	 {
		if(!($image_info = GetImageSize($src))) return new EmptyImageProcessor($src, $image_info);
		switch($image_info[2])
		 {
			case 1: return new GIFProcessor($src, $image_info);
			case 2: return new JPGProcessor($src, $image_info);
			case 3: return new PNGProcessor($src, $image_info);
			default: return new EmptyImageProcessor($src, $image_info);
		 }
	 }

	final public static function GetFittedImageSize($width, $height, $max_width, $max_height)
	 {
		if($width <= $max_width && $height <= $max_height) return array('width' => $width, 'height' => $height);
		$prop = $max_height / $height;
		$tmp = $max_width / $width;
		if($tmp && ($prop > $tmp || !$prop)) $prop = $tmp;
		return array('width' => round($width * $prop), 'height' => round($height * $prop));
	 }

	final public static function CalcRatio(stdClass $row)
	 {
		$row->ratio = $row->width / $row->height;
		$row->ratio_style = 'padding-bottom:'.(100 / $row->ratio)."%;max-width:{$row->width}px;";
	 }

	final public static function CalcDimensions(stdClass $row)
	 {
		if('f' === $row->icon_type && $row->width && $row->height)
		 {
			$size = self::GetFittedImageSize($row->width, $row->height, $row->max_width, $row->max_height);
			$row->width = $size['width'];
			$row->height = $size['height'];
		 }
		else
		 {
			$row->width = $row->max_width;
			$row->height = $row->max_height;
		 }
		self::CalcRatio($row);
	 }

	final public function SetCallback($func, $method = null)
	 {
		$this->callback = $method ? array($func, $method) : $func;
		return $this;
	 }

	final protected function GetCallback() { return $this->callback; }
	final protected function GetSrc() { return $this->src; }
	final protected function GetImageInfo($fld = null) { return null === $fld ? $this->image_info : @$this->image_info[$fld]; }

	final protected function __construct($src, $image_info)
	 {
		$this->src = $src;
		$this->image_info = $image_info;
	 }

	final private static function GetHost($host) { return true === $host ? Page::GetStaticHost() : $host; }

	private $src;
	private $image_info;
	private $callback;
}

class EmptyImageProcessor extends ImageProcessor
{
	final public function CreateFittedImage($max_width, $max_height, $dest = null, $output_directly = false) { return null; }
	final public function CreateCroppedImage($dest_width, $dest_height, $left, $top, $ratio, $dest = null, $output_directly = false) { return null; }
	final public function CreateFittedAndCroppedImage($dest_width, $dest_height, $top = false, $dest = null, $output_directly = false) { return null; }
}

abstract class NonEmptyImageProcessor extends ImageProcessor
{
	final public function CreateFittedImage($max_width, $max_height, $dest = null, $output_directly = false)
	 {
		list($width, $height, $type) = $this->GetImageInfo();
		if(!$dest || $output_directly) header('Content-type: '.$this->GetImageInfo('mime'));
		if(($max_height && $max_height < $height) || ($max_width && $max_width < $width))
		 {
			$new_size = self::GetFittedImageSize($width, $height, $max_width, $max_height);
			if(!($ch = $this->GetImageInfo('channels'))) $ch = 3;
			$this->CheckMemory($this->CalculateRequiredMemorySize($this->GetImageInfo(0), $this->GetImageInfo(1), $this->GetImageInfo('bits'), $ch) + $this->CalculateRequiredMemorySize($new_size['width'], $new_size['height'], 1));
		 }
		elseif($dest)
		 {
			if($callback = $this->GetCallback()) return $this->Write(call_user_func($callback, $this->Read(), $type), $dest, $output_directly);
			else
			 {
				$status = copy($this->GetSrc(), $dest);
				if($output_directly) readfile($this->GetSrc());
				return $status;
			 }
		 }
		else return ($callback = $this->GetCallback()) ? $this->Write(call_user_func($callback, $this->Read(), $type)) : readfile($this->GetSrc());
		$dest_img = ImageCreateTrueColor($new_size['width'], $new_size['height']);
		if(($type == 1) || ($type == 3))
		 {
			imagealphablending($dest_img, false);
			imagesavealpha($dest_img, true);
			$transparent = imagecolorallocatealpha($dest_img, 255, 255, 255, 127);
			imagefilledrectangle($dest_img, 0, 0, $new_size['width'], $new_size['height'], $transparent);
			imagecolortransparent($dest_img, $transparent);
		 }
		ImageCopyResampled($dest_img, $this->Read(), 0, 0, 0, 0, $new_size['width'], $new_size['height'], $width, $height);
		if($type == 1 && $transparent >= 0)
		 {
			imagecolortransparent($dest_img, $transparent);
			for($y = 0; $y < $new_size['height']; ++$y)
			 for($x = 0; $x < $new_size['width']; ++$x)
			  if(((imagecolorat($dest_img, $x, $y) >> 24) & 0x7F) >= 100) imagesetpixel($dest_img, $x, $y, $transparent);
		 }
		return $this->Write(($callback = $this->GetCallback()) ? call_user_func($callback, $dest_img, $type) : $dest_img, $dest, $output_directly);
	 }

	final public function CreateCroppedImage($dest_width, $dest_height, $left, $top, $ratio, $dest = null, $output_directly = false)
	 {
		list($width, $height, $type) = $this->GetImageInfo();
		if(!$dest || $output_directly) header('Content-type: '.$this->GetImageInfo('mime'));
		$dest_img = ImageCreateTrueColor($dest_width, $dest_height);
		if($type == 1 || $type == 3)
		 {
			imagealphablending($dest_img, false);
			imagesavealpha($dest_img, true);
			$transparent = imagecolorallocatealpha($dest_img, 255, 255, 255, 127);
			imagefilledrectangle($dest_img, 0, 0, $dest_width, $dest_height, $transparent);
		 }
		// resource $dst_image, resource $src_image, int $dst_x, int $dst_y, int $src_x, int $src_y, int $dst_w, int $dst_h, int $src_w, int $src_h)
		ImageCopyResampled($dest_img, $this->Read(), 0, 0, $left, $top, $dest_width, $dest_height, round($dest_width / $ratio), round($dest_height / $ratio));
		if($type == 1 && $transparent >= 0)
		 {
			imagecolortransparent($dest_img, $transparent);
			for($y = 0; $y < $dest_height; ++$y)
			 for($x = 0; $x < $dest_width; ++$x)
			  if(((imagecolorat($dest_img, $x, $y) >> 24) & 0x7F) >= 100) imagesetpixel($dest_img, $x, $y, $transparent);
		 }
		return $this->Write(($callback = $this->GetCallback()) ? call_user_func($callback, $dest_img, $type) : $dest_img, $dest, $output_directly);
	 }

	final public function CreateFittedAndCroppedImage($dest_width, $dest_height, $top = false, $dest = null, $output_directly = false)
	 {
		list($width, $height, $type) = $this->GetImageInfo();
		if(!$dest || $output_directly) header('Content-type: '.$this->GetImageInfo('mime'));
		if($width == $dest_width && $height == $dest_height)
		 {
			$dest_img = $this->Read();
			imagealphablending($dest_img, true);
			imagesavealpha($dest_img, true);
		 }
		else
		 {
			$dest_img = ImageCreateTrueColor($dest_width, $dest_height);
			if($width <= $dest_width || $height <= $dest_height)
			 {
				if(3 == $type)
				 {
					imagealphablending($dest_img, true);
					imagesavealpha($dest_img, true);
					$transparent = imagecolorallocatealpha($dest_img, 0, 0, 0, 127);
					imagefill($dest_img, 0, 0, $transparent);
				 }
				else
				 {
					$white = imagecolorallocate($dest_img, 255, 255, 255);
					imagefill($dest_img, 0, 0, $white);
					imagecolortransparent($dest_img, $white);
				 }
				$x_offset = round(($dest_width - $width) / 2);
				$y_offset = round(($dest_height - $height) / 2);
				ImageCopy($dest_img, $this->Read(), $x_offset, $y_offset, 0, 0, $width, $height);
			 }
			else
			 {
				$ration = $height / $width;
				$sample_ration = $dest_height / $dest_width;
				$resize = $sample_ration < $ration ? $dest_width / $width : $dest_height / $height;
				if($sample_ration > $ration)
				 {
					$fragment_width = $height / $sample_ration;
					$fragment_height = $height;
				 }
				else
				 {
					$fragment_width = $width;
					$fragment_height = $width * $sample_ration;
				 }
				$new_height = $resize * $height;
				$new_width = $resize * $width;
				$x_offset = round(($width - $fragment_width) / 2);
				$y_offset = $top ? 0 : round(($height - $fragment_height) / 2);
				if(($type == 1) || ($type == 3))// 3 - png, 1 - gif
				 {
					imagealphablending($dest_img, false);
					imagesavealpha($dest_img, true);
					$transparent = imagecolorallocatealpha($dest_img, 255, 255, 255, 127);
					imagefilledrectangle($dest_img, 0, 0, $new_width, $new_height, $transparent);
				 }
				ImageCopyResampled($dest_img, $this->Read(), 0, 0, $x_offset, $y_offset, $dest_width, $dest_height, $fragment_width, $fragment_height);
				if($type == 1 && $transparent >= 0)
				 {
					imagecolortransparent($dest_img, $transparent);
					for($y = 0; $y < $dest_height; ++$y)
					 for($x = 0; $x < $dest_width; ++$x)
					  if(((imagecolorat($dest_img, $x, $y) >> 24) & 0x7F) >= 100) imagesetpixel($dest_img, $x, $y, $transparent);
				 }
			 }
		 }
		return $this->Write(($callback = $this->GetCallback()) ? call_user_func($callback, $dest_img, $type) : $dest_img, $dest, $output_directly);
	 }

	final protected function CalculateRequiredMemorySize($width, $height, $bits, $channels = 3)
	 {
		return round(($width * $height * $bits * $channels / 8 + pow(2, 16)) * 1.7);
	 }

	final protected function CheckMemory($size)
	 {
		$curr_mem = ini_get('memory_limit');
		$unit = substr($curr_mem, -1);
		if(is_numeric($unit)) $curr_mem_conv = $curr_mem;
		else
		 {
			$curr_mem = substr($curr_mem, 0, -1);
			switch($unit)
			 {
				case 'M': $curr_mem_conv = $curr_mem * 1048576; break;
				case 'K': $curr_mem_conv = $curr_mem * 1024; break;
				default:
			 }
		 }
		if($curr_mem_conv < $size && false === ini_set('memory_limit', $size + $curr_mem_conv + 10485760)) throw new EImageProcessorNotEnoughMemory();
	 }

	abstract protected function Write($img, $dest = null, $output_directly = false);
	abstract protected function Read();
}

class JPGProcessor extends NonEmptyImageProcessor
{
	final protected function Write($img, $dest = null, $output_directly = false)
	 {
		ImageInterlace($img, true);
		if($dest)
		 {
			$status = ImageJPEG($img, $dest, 100);
			if($output_directly) ImageJPEG($img, null, 100);
			return $status;
		 }
		else return ImageJPEG($img, null, 100);
	 }

	final protected function Read() { return ImageCreateFromJPEG($this->GetSrc()); }
}

abstract class TransparentImageProcessor extends NonEmptyImageProcessor
{

}

class GIFProcessor extends TransparentImageProcessor
{
	final protected function Write($img, $dest = null, $output_directly = false)
	 {
		if($dest)
		 {
			$status = ImageGIF($img, $dest);
			if($output_directly) ImageGIF($img);
			return $status;
		 }
		else return ImageGIF($img);
	 }

	final protected function Read() { return ImageCreateFromGIF($this->GetSrc()); }
}

class PNGProcessor extends TransparentImageProcessor
{
	final protected function Write($img, $dest = null, $output_directly = false)
	 {
		if($dest)
		 {
			$status = ImagePNG($img, $dest);
			if($output_directly) ImagePNG($img);
			return $status;
		 }
		else return ImagePNG($img);
	 }

	final protected function Read() { return ImageCreateFromPNG($this->GetSrc()); }
}
?>