<?php
class fileDir {
	  private $fileInfo;
	  private $fileLocation;
	  private $error;
	  private $direct;
	  private $webpQuality;
	   
	  function __construct($dir){
	      $this->direct = $dir;
	      $this->webpQuality = 82;
	      if(!is_dir($this->direct)){
	          die('Supplied directory is not valid: '.$this->direct);   
	      }
	  }

	  private function createImageFromMime($tmpName, $mime) {
		  if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
			  return @imagecreatefromjpeg($tmpName);
		  }
		  if ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
			  return @imagecreatefrompng($tmpName);
		  }
		  if ($mime === 'image/gif' && function_exists('imagecreatefromgif')) {
			  return @imagecreatefromgif($tmpName);
		  }
		  if ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
			  return @imagecreatefromwebp($tmpName);
		  }
		  return false;
	  }

	  private function uploadAsWebp($tmpName, $targetPath) {
		  if (!function_exists('imagewebp') || !function_exists('getimagesize')) {
			  return false;
		  }
		  $info = @getimagesize($tmpName);
		  if ($info === false || !isset($info['mime'])) {
			  return false;
		  }
		  $image = $this->createImageFromMime($tmpName, $info['mime']);
		  if ($image === false) {
			  return false;
		  }
		  // Keep transparency when possible.
		  if (function_exists('imagepalettetotruecolor')) {
			  @imagepalettetotruecolor($image);
		  }
		  imagealphablending($image, true);
		  imagesavealpha($image, true);
		  $ok = @imagewebp($image, $targetPath, $this->webpQuality);
		  imagedestroy($image);
		  return $ok;
	  }
	  
	  function upload($theFile){
	      $this->fileInfo = $theFile;
		  $ext = strtolower(substr(strrchr($this->fileInfo['name'],'.'),1));
		  $filename = rand(0000,9999).time().'.webp';
	      $this->fileLocation = $this->direct . $filename;
		  $tmpName = $this->fileInfo['tmp_name'];
		  if ($this->uploadAsWebp($tmpName, $this->fileLocation)) {
			  return $filename;
		  }
		  // Fallback: keep original extension if WebP conversion is unavailable.
		  $fallbackName = rand(0000,9999).time().'.'.$ext;
		  $fallbackLocation = $this->direct . $fallbackName;
	        if(move_uploaded_file($tmpName, $fallbackLocation)){
				$this->fileLocation = $fallbackLocation;
	            return $fallbackName;
	        } else {
	            return 'File could not be uploaded';
	            $this->error = "Error: File could not be uploaded.\n";
	            $this->error .= 'Here is some more debugging info:';
	            $this->error .= print_r($_FILES);   
	        }
	  }
	  
	  function overwrite($theFile){
	      $this->fileInfo = $theFile;
	      $this->fileLocation = $this->direct . $this->fileInfo['name'];
	      if(file_exists($this->fileLocation)){
	          $this->delete($this->fileInfo['name']);
	      }
	      return $this->upload($this->fileInfo);
	  }
	  
	  function location(){
	      return $this->fileLocation;   
	  }
	  
	  function fileName(){
	      return $this->fileInfo['name'];
	  }
	  
	  function delete($fileName){
	      $this->fileLocation = $this->direct.$fileName;
	      if(is_file($this->fileLocation)){
	        @unlink($this->fileLocation);
	        return 'Your file was successfully deleted';
	      } else {
	        return 'No such file exists: '.$this->fileLocation; 
	      }
	  }
	  
	  function reportError(){
	      return $this->error;  
	  }
	}
?>