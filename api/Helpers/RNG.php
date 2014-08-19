<?php

abstract class RNG
{
	const NUMERICAL = '0123456789';
	const ALPHABET = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	const ALPHANUMERICAL = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

	public static function FixedString($length, $alphabet)
	{
		$ret = '';
		for ($i = 0; $i < $length; $i++)
			$ret .= $alphabet[rand(0, strlen($alphabet) - 1)];
		return $ret;
	}
}

?>