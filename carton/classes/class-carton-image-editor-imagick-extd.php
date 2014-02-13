<?php
/**
 * WordPress Imagick Image Editor
 *
 * @package WordPress
 * @subpackage Image_Editor
 */

/**
 * WordPress Image Editor Class for Image Manipulation through Imagick PHP Module
 *
 * @since 3.5.0
 * @package WordPress
 * @subpackage Image_Editor
 * @uses WP_Image_Editor Extends class
 */
class WP_Image_Editor_Imagick_Extd extends WP_Image_Editor_Imagick {

	/**
	 * Saves current image to file.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param string $destfilename
	 * @param string $mime_type
	 * @return array|WP_Error {'path'=>string, 'file'=>string, 'width'=>int, 'height'=>int, 'mime-type'=>string}
	 */
	protected function _save( $image, $filename = null, $mime_type = null ) {
		list( $filename, $extension, $mime_type ) = $this->get_output_format( $filename, $mime_type );

		if ( ! $filename )
			$filename = $this->generate_filename( null, null, $extension );

		try {
			// Store initial Format
			$orig_format = $this->image->getImageFormat();

			$this->image->setImageFormat( strtoupper( $this->get_extension( $mime_type ) ) );

			if( $orig_format == 'GIF' )
				$this->make_image( $filename, array( $image, 'writeImages' ), array( $filename, true ) );
			else
			    $this->make_image( $filename, array( $image, 'writeImage' ), array( $filename ) );

			// Reset original Format
			$this->image->setImageFormat( $orig_format );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'image_save_error', $e->getMessage(), $filename );
		}

		// Set correct file permissions
		$stat = stat( dirname( $filename ) );
		$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
		@ chmod( $filename, $perms );

		return array(
			'path' => $filename,
			'file' => wp_basename( apply_filters( 'image_make_intermediate_size', $filename ) ),
			'width' => $this->size['width'],
			'height' => $this->size['height'],
			'mime-type' => $mime_type,
		);
	}
	

	/**
	 * Resizes current image.
	 *
	 * @since 3.5.0
	 * @access public
	 *
	 * @param int $max_w
	 * @param int $max_h
	 * @param boolean $crop
	 * @return boolean|WP_Error
	 */
	public function resize( $max_w, $max_h, $crop = false ) {

        $size    = array($max_w,$max_h);
        $border  = array(0, 0); //border in percents
        $b_size  = array( $size[0]-(($size[0]/100)*(2*$border[0])), $size[1]-(($size[1]/100)*(2*$border[1])) );


	    if( $this->image->getImageFormat() == 'GIF' ) {
	        $this->image->trimImage(0);
	        
	        
            $p_size = $this->image->getImageGeometry();
            $n_size = $this->get_croped_dimmensions( array($p_size['width'],$p_size['height']), $b_size );

            $left = ($size[0]-$n_size[0])/2;
            $top  = ($size[1]-$n_size[1]);

            $gradient = new Imagick();
            $gradient->newPseudoImage( $size[0], $size[1], "gradient:" . $this->image->getImagePixelColor(1, 1)->getColorAsString() . "-" . $this->image->getImagePixelColor($n_size[0]-1, $n_size[1]-1)->getColorAsString() );


	        /* Text to write */
	        $text = get_bloginfo('name');
	        
	        /* Font properties */   
	        $draw = new ImagickDraw();
            $draw->setFont(CARTON_THUMBNAIL_REBUILD_DIR . 'fonts/CALIBRI.otf');

            $draw->setStrokeAntialias(true);
            $draw->setTextAntialias(true);

            if( $max_w >= 800 )
                $draw->setFontSize( ($n_size[0] / 100) * 2);
            else if( $max_w >= 500 )
                $draw->setFontSize( ($n_size[0] / 100) * 3);
            else if( $max_w >= 200 )
                $draw->setFontSize( ($n_size[0] / 100) * 5);
                
            $draw->setGravity(imagick::GRAVITY_EAST);
            
	        $this->image = $this->image->coalesceImages();
	        do {

                $this->image->setImageResolution(72,72); 
                $this->image->resampleImage(72,72, imagick::FILTER_UNDEFINED, 1); 
                $this->image->scaleImage( $n_size[0], $n_size[1] );
                $this->image->sharpenImage( 4,2 );

                if($max_w >= 200 ) {
                    $draw->setFillColor('#000000');
                    $this->image->annotateImage($draw, 4.5, 0.5, 0, $text);
                    $draw->setFillColor('#FFFFFF');
                    $this->image->annotateImage($draw, 5.5, -0.5, 0, $text);
                    $draw->setFillColor('#888888');
                    $this->image->annotateImage($draw, 5, 0, 0, $text);
                }

//                $this->image->setBackgroundColor(new ImagickPixel('white'));
//                $src = $this->image->clone();
//                $this->image->compositeImage( $gradient, imagick::COMPOSITE_OVER, 0, 0 );
//                $this->image->compositeImage( $src, imagick::COMPOSITE_OVER, $left, $top);
//                $src->destroy();

	        } while( $this->image->nextImage() );
	        $this->image = $this->image->deconstructImages();
	        
            $gradient->destroy();

	        return $this->update_size();
	    }

        
        $bgcolor = $this->image->getImagePixelColor(1, 1)->getColor();
        $this->image->trimImage(0);
        
        $p_size = $this->image->getImageGeometry();
        $n_size = $this->get_croped_dimmensions( array($p_size['width'],$p_size['height']), $b_size );
        
        $left = ($size[0]-$n_size[0])/2;
        $top  = ($size[1]-$n_size[1]);
        
        $this->image->setImageResolution(72,72); 
        $this->image->resampleImage(72,72, imagick::FILTER_UNDEFINED, 1); 
        $this->image->scaleImage( $n_size[0], $n_size[1] );
        $this->image->sharpenImage( 4,2 );
	    $src = $this->image->clone();

	    $this->image->cropImage(1,1, 0,0);
	    $this->image->scaleImage( $size[0], $size[1] );

        $gradient = new Imagick();
//        $gradient->newPseudoImage( $size[0], $size[1], "gradient:" . $src->getImagePixelColor(1, 1)->getColorAsString() . "-" . $src->getImagePixelColor($n_size[0]-1, $n_size[1]-1)->getColorAsString() );
        $gradient->newPseudoImage( $size[0], $size[1], "gradient:white-white" );
        $this->image->compositeImage( $gradient, imagick::COMPOSITE_OVER, 0, 0 );
        $gradient->destroy();
	    $this->image->compositeImage( $src, imagick::COMPOSITE_OVER, $left, $top);
        $src->destroy();
        
        
        /*
		$dims = image_resize_dimensions( $this->size['width'], $this->size['height'], $max_w, $max_h, $crop );

		if ( ! $dims )
			return new WP_Error( 'error_getting_dimensions', __('Could not calculate resized image dimensions') );

        list( $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h ) = $dims;

		if ( $crop ) {
			return $this->crop( $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h );
		}

		try { */
			/**
			 * @TODO: Thumbnail is more efficient, given a newer version of Imagemagick.
			 * $this->image->thumbnailImage( $dst_w, $dst_h );
			 */ /*
			$this->image->scaleImage( $dst_w, $dst_h );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'image_resize_error', $e->getMessage() );
		} */

		return $this->update_size();
	}
    
    
    private function get_croped_dimmensions( $dimm, $size ) {
		$scale  = array(1,1);
		
		foreach( array(0,1) as $i ) $scale[$i] = $size[$i] / $dimm[$i];

		$scale = ($scale[0]<=$scale[1]) ? $scale[0] : $scale[1];
		return array ( (int) ($dimm[0] * $scale), (int) ($dimm[1] * $scale) );
	}
}
