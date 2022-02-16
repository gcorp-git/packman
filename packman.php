<?php
class Packman {

	static function encryptFile(
		string $filename,
		string $password,
		string $algorythm='md5',
		int $randomBytesLength=64,
	): void {
		self::_assert(file_exists($filename) ?: 'File not found.');
		self::_assert(!empty($password) ?: 'Password cannot be empty.');
		self::_assert($randomBytesLength >= 0 ?: 'Incorrect random bytes length.');

		$pathinfo = pathinfo($filename);
		$body = file_get_contents($filename);

		$header = [];
		$header['algorythm'] = $algorythm;
		$header['digest_size'] = self::_getDigestSize($header['algorythm']);
		$header['random_bytes'] = random_bytes($randomBytesLength);
		$imprintBody = $header['random_bytes'] . $password . $pathinfo['basename'] . $body;
		$header['imprint'] = hash($header['algorythm'], $imprintBody, $row=true);
		$key = $header['random_bytes'] . $password . $header['imprint'];
		$header['filename'] = $pathinfo['basename'];
		
		self::_encrypt($header, $header['filename'], md5($key) . $key);
		self::_encrypt($header, $body, $key);

		file_put_contents(
			$pathinfo['dirname'] . DIRECTORY_SEPARATOR . md5($header['random_bytes']),
			self::_packHeader($header) . $body,
		);
	}

	static function decryptFile(string $filename, string $password): void {
		self::_assert(file_exists($filename) ?: 'File not found.');
		self::_assert(!empty($password) ?: 'Password cannot be empty.');
		
		$pathinfo = pathinfo($filename);
		$content = file_get_contents($filename);

		$header = [];
		$body = substr($content, self::_unpackHeader($header, $content));
		$key = $header['random_bytes'] . $password . $header['imprint'];

		self::_encrypt($header, $header['filename'], md5($key) . $key);
		self::_encrypt($header, $body, $key);

		$imprintBody = $header['random_bytes'] . $password . $header['filename'] . $body;
		$imprint = hash($header['algorythm'], $imprintBody, $row=true);

		self::_assert($imprint === $header['imprint'] ?: 'Decryption failed.');

		file_put_contents(
			$pathinfo['dirname'] . DIRECTORY_SEPARATOR . $header['filename'],
			$body,
		);
	}

	static function readPassword(string $prompt=''): string {
		if (!empty($prompt)) echo $prompt;

		$password = readline();

		self::_assert(!empty($password) ?: 'Password cannot be empty.');

		return $password;
	}

	private static function _encrypt(array &$header, string &$message, string $password): void {
		$size = strlen($message);
		$size = $size + ($header['digest_size'] - ($size % $header['digest_size']));

		$key = '';
		$hash = hash($header['algorythm'], $password, $row=true);

		for ($i = 0; $i < $size; $i += $header['digest_size']) {
			$hash = hash($header['algorythm'], $password . $hash, $row=true);
			$key .= $hash;
		}

		$message ^= $key;
	}

	private static function _packHeader(array &$header): string {
		$bAlgorythmLength = pack('C', strlen($header['algorythm']));
		$bFilenameLength = pack('N', strlen($header['filename']));
		$bRandomBytesLength = pack('N', strlen($header['random_bytes']));

		return implode( '', [
			$bAlgorythmLength, $header['algorythm'],
			$bFilenameLength, $header['filename'],
			$bRandomBytesLength, $header['random_bytes'],
			$header['imprint'],
		]);
	}

	private static function _unpackHeader(array &$header, string &$content): int {
		$pos = 0;

		$algorythmLength = unpack('C', substr($content, $pos, 1))[1];
		$pos += 1;
		$header['algorythm'] = substr($content, $pos, $algorythmLength);
		$pos += $algorythmLength;

		$header['digest_size'] = self::_getDigestSize($header['algorythm']);
		
		$filenameLength = unpack('N', substr($content, $pos, 4))[1];
		$pos += 4;
		$header['filename'] = substr($content, $pos, $filenameLength);
		$pos += $filenameLength;

		$randomBytesLength = unpack('N', substr($content, $pos, 4))[1];
		$pos += 4;
		$header['random_bytes'] = substr($content, $pos, $randomBytesLength);
		$pos += $randomBytesLength;
		
		$header['imprint'] = substr($content, $pos, $header['digest_size']);
		$pos += $header['digest_size'];

		return $pos;
	}

	private static function _getDigestSize(string $algorythm): int {
		self::_assert(in_array($algorythm, hash_algos()) ?: 'Unknown hashing algorythm.');

		return strlen(hash($algorythm, '', $row=true));
	}

	private static function _assert(bool|string $v): void {
		if ($v === true) return;

		$v = ($v === false) ? 'Assertion failed.' : $v;

		throw new Exception($v);
	}

}
