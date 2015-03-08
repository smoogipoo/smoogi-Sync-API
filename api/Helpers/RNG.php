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

    /*
     * Todo: Improve this maybe?
     * Several years out of date...
     */
    public static function IncrementalString($currentString, $alphabet)
    {
        if ($currentString == '')
            return 'a';
        else
        {
            $lastChar = substr($currentString, strlen($currentString) - 1);
            $newString = '';
            if ($lastChar == '9')
            {
                for ($i = strlen($currentString) - 2; $i > -1; $i--)
                {
                    if (substr($currentString, $i, 1) != '9')
                    {
                        $backwardChars = substr($currentString, 0, $i);
                        $numForwardChars = strlen($currentString) - ($i + 1);
                        $newChar = substr($alphabet, strrpos($alphabet, substr($currentString, $i, 1)) + 1, 1);
                        $newString = $backwardChars . $newChar;
                        for ($n = 1; $n < $numForwardChars + 1; $n++)
                            $newString .= 'a';
                        return $newString;
                    }
                }

                $numChars = strlen($currentString);
                for ($r = 0; $r < $numChars + 1; $r++)
                    $newString .= 'a';
            }
            else 
                $newString = substr($currentString, 0, strlen($currentString) - 1) . substr($alphabet, strrpos($alphabet, substr($currentString, strlen($currentString) - 1)) + 1, 1);
            return $newString;  
        }
    }
}

?>