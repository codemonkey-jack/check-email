<?php namespace CheckEmail;
defined( 'ABSPATH' ) || exit; // Exit if accessed directly.
class Check_Email_Log_Autoloader {

	protected $prefixes = array();

	protected $files = array();

	public function register() {
		spl_autoload_register( array( $this, 'load_class' ) );

		// file exists check is already done in `add_file`.
		foreach ( $this->files as $file ) {
			$this->require_file( $file );
		}
	}

	public function add_namespace( $prefix, $base_dir, $prepend = false ) {
		// normalize namespace prefix
		$prefix = trim( $prefix, '\\' ) . '\\';

		// normalize the base directory with a trailing separator
		$base_dir = rtrim( $base_dir, DIRECTORY_SEPARATOR ) . '/';

		// initialize the namespace prefix array
		if ( false === isset( $this->prefixes[ $prefix ] ) ) {
			$this->prefixes[ $prefix ] = array();
		}

		// retain the base directory for the namespace prefix
		if ( $prepend ) {
			array_unshift( $this->prefixes[ $prefix ], $base_dir );
		} else {
			array_push( $this->prefixes[ $prefix ], $base_dir );
		}
	}

	public function add_file( $filename ) {
		if ( ! in_array( $filename, $this->files, true ) ) {
			$this->files[] = $filename;
		}
	}

	public function load_class( $class ) {
		// the current namespace prefix
		$prefix = $class;
		// work backwards through the namespace names of the fully-qualified
		// class name to find a mapped file name
		while ( false !== $pos = strrpos( $prefix, '\\' ) ) {

			// retain the trailing namespace separator in the prefix
			$prefix = substr( $class, 0, $pos + 1 );

			// the rest is the relative class name
			$relative_class = substr( $class, $pos + 1 );

			// try to load a mapped file for the prefix and relative class
			$mapped_file = $this->load_mapped_file( $prefix, $relative_class );
			if ( $mapped_file !== false ) {
				return $mapped_file;
			}

			// remove the trailing namespace separator for the next iteration
			// of strrpos()
			$prefix = rtrim( $prefix, '\\' );
		}

		// never found a mapped file
		return false;
	}

	protected function load_mapped_file( $prefix, $relative_class ) {
		// are there any base directories for this namespace prefix?
		if ( false === isset( $this->prefixes[ $prefix ] ) ) {
			return false;
		}

		// look through base directories for this namespace prefix
		foreach ( $this->prefixes[ $prefix ] as $base_dir ) {
			// replace the namespace prefix with the base directory,
			// replace namespace separators with directory separators
			// in the relative class name, append with .php
			$file = $base_dir
			        . str_replace( '\\', '/', $relative_class )
			        . '.php';

			// if the mapped file exists, require it
			if ( $this->require_file( $file ) ) {
				// yes, we're done
				return $file;
			}
		}

		// never found it
		return false;
	}
	protected function require_file( $file ) {
		if ( file_exists( $file ) ) {
			require_once $file;

			return true;
		}

		return false;
	}
}
