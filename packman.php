<?
class Packman {
	private $_algorythm = '';
	private $_digest_size = 0;

	function __construct( string $algorytm='md5' ) {
		if ( !in_array( $algorytm, hash_algos() ) ) {
			throw new Exception( 'Unknown hashing algorythm' );
		}

		$this->_algorythm = $algorytm;
		$this->_digest_size = strlen( hash( $this->_algorythm, '', $row=true ) );
	}

	function read_seed( string $prompt='' ) {
		if ( !empty( $prompt ) ) echo "{$prompt}";

		$seed = readline();

		if ( empty( $seed ) ) {
			throw new Exception( 'Empty seed' );
		}

		return $seed;
	}

	function encrypt_file( string $filename='', string $seed='' ) {
		$content = file_get_contents( $filename );
		$iv = hash( $this->_algorythm, $content, $row=true );
		
		$encrypted = $this->encrypt( $content, $seed . $iv ) . $iv;

		$filename = $this->_encrypt_filename( $filename, md5( $seed . $iv ) );

		file_put_contents( $filename, $encrypted );
	}

	function decrypt_file( string $filename='', string $seed='' ) {
		$content = file_get_contents( $filename );
		$iv = substr( $content, -$this->_digest_size, $this->_digest_size );
		$content = substr( $content, 0, -$this->_digest_size );
		
		$decrypted = $this->encrypt( $content, $seed . $iv );
		
		if ( $iv !== hash( $this->_algorythm, $decrypted, $row=true ) ) {
			throw new Exception( 'Decryption failed.' );
		}

		$filename = $this->_decrypt_filename( $filename, md5( $seed . $iv ) );

		file_put_contents( $filename, $decrypted );
	}

	function encrypt( string $message='', string $seed='' ) {
		$size = strlen( $message );
		$size = $size + ( $this->_digest_size - ( $size % $this->_digest_size ) );

		$key = '';
		$hash = hash( $this->_algorythm, $seed, $row=true );

		for ( $i = 0; $i < $size; $i += $this->_digest_size ) {
			$hash = hash( $this->_algorythm, $seed . $hash, $row=true );
			$key .= $hash;
		}

		return $message ^ $key;
	}

	/* private */

	private function _encrypt_filename( string $filename='', string $seed='' ) {
		$pi = pathinfo( $filename );

		$encrypted = $this->encrypt( $pi['basename'], $seed );
		$encrypted = substr( $encrypted, 0, strlen( $pi['basename'] ) );
		$encrypted = bin2hex( $encrypted );

		return $pi['dirname'] . DIRECTORY_SEPARATOR . $encrypted . '.pkg';
	}

	private function _decrypt_filename( $filename='', $seed='' ) {
		$pi = pathinfo( $filename );

		$decrypted = hex2bin( $pi['filename'] );
		$decrypted = $this->encrypt( $decrypted, $seed );
		$decrypted = substr( $decrypted, 0, strlen( $pi['filename'] ) );

		return $pi['dirname'] . DIRECTORY_SEPARATOR . $decrypted;
	}

}