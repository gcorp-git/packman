<?
class Packman {
	private $_algorythm = '';
	private $_digest_size = 0;

	function __construct( string $algorytm='md5' ) {
		if ( !in_array( $algorytm, hash_algos() ) ) {
			throw new Exception( 'Unknown hashing algorythm.' );
		}

		$this->_algorythm = $algorytm;
		$this->_digest_size = strlen( hash( $this->_algorythm, '', $row=true ) );
	}

	function read_password( string $prompt='' ) {
		// todo: hide entered characters

		if ( !empty( $prompt ) ) echo $prompt;

		$password = readline();

		if ( empty( $password ) ) {
			throw new Exception( 'Empty password.' );
		}

		return $password;
	}

	function encrypt_file( string $filename='', string $password='' ) {
		$content = file_get_contents( $filename );
		$imprint = hash( $this->_algorythm, $password . $content, $row=true );
		
		$encrypted = $this->encrypt( $content, $password . $imprint ) . $imprint;

		$filename = $this->_encrypt_filename( $filename, md5( $password . $imprint ) );

		file_put_contents( $filename, $encrypted );
	}

	function decrypt_file( string $filename='', string $password='' ) {
		$content = file_get_contents( $filename );
		$imprint = substr( $content, -$this->_digest_size, $this->_digest_size );
		$content = substr( $content, 0, -$this->_digest_size );
		
		$decrypted = $this->encrypt( $content, $password . $imprint );
		
		$current_imprint = hash( $this->_algorythm, $password . $decrypted, $row=true );

		if ( $imprint !== $current_imprint ) {
			throw new Exception( 'Decryption failed.' );
		}

		$filename = $this->_decrypt_filename( $filename, md5( $password . $imprint ) );

		file_put_contents( $filename, $decrypted );
	}

	function encrypt( string $message='', string $password='' ) {
		$size = strlen( $message );
		$size = $size + ( $this->_digest_size - ( $size % $this->_digest_size ) );

		$key = '';
		$hash = hash( $this->_algorythm, $password, $row=true );

		for ( $i = 0; $i < $size; $i += $this->_digest_size ) {
			$hash = hash( $this->_algorythm, $password . $hash, $row=true );
			$key .= $hash;
		}

		return $message ^ $key;
	}

	/* private */

	private function _encrypt_filename( string $filename='', string $password='' ) {
		$pi = pathinfo( $filename );

		$encrypted = $this->encrypt( $pi['basename'], $password );
		$encrypted = substr( $encrypted, 0, strlen( $pi['basename'] ) );
		$encrypted = $this->_base64url_encode( $encrypted );

		return $pi['dirname'] . DIRECTORY_SEPARATOR . $encrypted;
	}

	private function _decrypt_filename( $filename='', $password='' ) {
		$pi = pathinfo( $filename );

		$decrypted = $this->_base64url_decode( $pi['basename'] );
		$decrypted = $this->encrypt( $decrypted, $password );
		$decrypted = substr( $decrypted, 0, strlen( $pi['basename'] ) );

		return $pi['dirname'] . DIRECTORY_SEPARATOR . $decrypted;
	}

	private function _base64url_encode( $data ) {
		return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
	}

	private function _base64url_decode( $data ) {
		return base64_decode(
			strtr( $data, '-_', '+/' ) .
			str_repeat( '=', 3 - ( 3 + strlen( $data ) ) % 4 )
		);
	}

}