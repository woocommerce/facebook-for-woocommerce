<?php
/**
 * Copyright (c) Facebook, Inc. and its affiliates. All Rights Reserved
 *
 * This source code is licensed under the license found in the
 * LICENSE file in the root directory of this source tree.
 *
 * @package FacebookCommerce
 */

defined( 'ABSPATH' ) or exit;

if ( ! class_exists('WC_Facebookcommerce_CustomAutoloader') ):
	class WC_Facebookcommerce_CustomAutoloader {
    /**
    * Autoload constructor. See https://codereview.stackexchange.com/questions/150170/recursive-autoloader-to-find-php-files
    * as a reference
    *
    * @param string $namespace
    * @param string $dir
    */
    public function __construct( $namespace, $dir )
    {
        // Make sure it ends with a '\'.
        $namespace       = rtrim( $namespace, '\\' ) . '\\';
        $this->namespace = $namespace;
        $this->length    = strlen( $namespace );
        //Make sure it uses DIRECTORY SEPARATOR to separate folders and it ends with that character
        $this->dir       = rtrim(str_replace( '/', DIRECTORY_SEPARATOR, $dir ), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
    }
    /**
    * @param string $search
    */
    public function load( $search ){
      if ( strncmp( $this->namespace, $search, $this->length ) !== 0 ) {
        return;
      }
      $name = substr( $search, $this->length );
      //Replaces \ by DIRECTORY_SEPARATOR in the full name of the class
      $path = $this->dir .str_replace( '\\', DIRECTORY_SEPARATOR, $name ). '.php';
      if ( is_readable( $path ) ) {
        require_once ($path);
      }
    }
	}

endif;
