<?php
class Watermark
{
	public function __construct($watermark_file, $alpha_level = 100, $woff = 7, $hoff = 10, $walign = 'left', $halign = 'bottom')
	 {
		$this->file_name = $watermark_file;
		$this->watermark_img_obj = $this->CreateImgObj($watermark_file);
		$this->alpha_level = $alpha_level / 100;
		$this->woff = $woff;
		$this->hoff = $hoff;
		$this->walign = $walign;
		$this->halign = $halign;
	 }

	public function GetFileName() { return $this->file_name; }

	public function CreateFromFile($main_img_file)
	 {
		return $this->Create($this->CreateImgObj($main_img_file));
	 }

	public function Create($main_img_obj)
	 {
		$woff = $this->woff;
		$hoff = $this->hoff;
		$this->alpha_level;
		$main_img_obj_w = imagesx( $main_img_obj );
		$main_img_obj_h = imagesy( $main_img_obj );
		$watermark_img_obj_w = imagesx( $this->watermark_img_obj );
		$watermark_img_obj_h = imagesy( $this->watermark_img_obj );
		$main_img_obj_min_x = floor( ( $main_img_obj_w / 2 ) - ( $watermark_img_obj_w / 2 ) );
		$main_img_obj_max_x = ceil( ( $main_img_obj_w / 2 ) + ( $watermark_img_obj_w / 2 ) );
		$main_img_obj_min_y = floor( ( $main_img_obj_h / 2 ) - ( $watermark_img_obj_h / 2 ) );
		$main_img_obj_max_y = ceil( ( $main_img_obj_h / 2 ) + ( $watermark_img_obj_h / 2 ) );
		$return_img = imagecreatetruecolor( $main_img_obj_w, $main_img_obj_h );
		switch($this->walign)
		 {
			case 'center': $woff+=$main_img_obj_min_x;break;
			case 'right': $woff=$main_img_obj_w-$watermark_img_obj_w-$woff;break;
		 }
		switch($this->halign)
		 {
			case 'middle': $hoff+=$main_img_obj_min_y;break;
			case 'bottom': $hoff=$main_img_obj_h-$watermark_img_obj_h-$hoff;break;
		 }
		for($y = 0; $y < $main_img_obj_h; ++$y)
		 {
			for($x = 0; $x < $main_img_obj_w; ++$x)
			 {
				$return_color = NULL;
				$watermark_x = $x - $woff; 
				$watermark_y = $y - $hoff;  
				$main_rgb = imagecolorsforindex($main_img_obj, imagecolorat($main_img_obj, $x, $y));  
				if($watermark_x >= 0 && $watermark_x < $watermark_img_obj_w && $watermark_y >= 0 && $watermark_y < $watermark_img_obj_h)
				 {
					$watermark_rbg = imagecolorsforindex($this->watermark_img_obj,imagecolorat($this->watermark_img_obj,$watermark_x,$watermark_y));  
					$watermark_alpha    = round( ( ( 127 - $watermark_rbg['alpha'] ) / 127 ), 2 );  
					$watermark_alpha    = $watermark_alpha * $this->alpha_level;  
					$avg_red    = $this->get_ave_color($main_rgb['red'], $watermark_rbg['red'], $watermark_alpha);  
					$avg_green  = $this->get_ave_color($main_rgb['green'], $watermark_rbg['green'], $watermark_alpha);  
					$avg_blue   = $this->get_ave_color($main_rgb['blue'], $watermark_rbg['blue'], $watermark_alpha);  
					$return_color   = $this->get_image_color($return_img, $avg_red, $avg_green, $avg_blue);  
				 }
				else $return_color   = imagecolorat( $main_img_obj, $x, $y );
				imagesetpixel( $return_img, $x, $y, $return_color );  
			 }
		 }
		return $return_img;
	 }

	private function get_ave_color( $color_a, $color_b, $alpha_level ) { return round((($color_a * (1 - $alpha_level)) + ($color_b * $alpha_level))); }// average two colors given an alpha

	private function get_image_color($im, $r, $g, $b)// returns closest pallette-color match for RGB values  
	 {
		$c = imagecolorexact($im, $r, $g, $b);
		if($c != -1) return $c;
		$c = imagecolorallocate($im, $r, $g, $b);
		if($c != -1) return $c;
		return imagecolorclosest($im, $r, $g, $b);
	 }

	private function CreateImgObj($src)
	 {
		$img = GetImageSize($src);
		switch($img[2])
		 {
			case 1: return ImageCreateFromGIF($src);
			case 2: return ImageCreateFromJPEG($src);
			case 3: return ImageCreateFromPNG($src);
			default: return null;
		 }
	 }

	private $watermark_img_obj;
	private $alpha_level;
	private $woff;
	private $hoff;
	private $walign;
	private $halign;
	private $file_name;
}
?>