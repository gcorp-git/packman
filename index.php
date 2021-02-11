<?
if ( empty( $argv[1] ) || !in_array( $argv[1], [ 'encrypt', 'decrypt' ] ) ) {
	exit( "Unknown command, encrypt or decrypt is required.\n" );
}
if ( empty( $argv[2] ) || !is_file( $argv[2] ) ) {
	exit( "Drag a file on encrypt.cmd or decrypt.cmd to work with.\n" );
}

require_once __DIR__ . '/packman.php';

try {
	$packman = new Packman( 'sha3-512' );

	$password = $packman->read_password( "Password:\n" );

	$started = microtime( true );

	switch ( $argv[1] ) {
		case 'encrypt':
			$packman->encrypt_file( $argv[2], $password );
			break;
		case 'decrypt':
			$packman->decrypt_file( $argv[2], $password );
			break;
	}

	$finished = microtime( true );
	
	$time = number_format( ( $finished - $started ), 3 );
	$memory = memory_get_peak_usage( true );

	echo "Time: {$time} sec\n";
	echo "Memory: {$memory} bytes\n";

} catch ( Exception | Error $e ) {
	echo "Error: {$e->getMessage()}\n";
	exit;
}