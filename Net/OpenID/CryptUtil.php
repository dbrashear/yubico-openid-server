<?php

/**
 * CryptUtil: A suite of wrapper utility functions for the OpenID
 * library.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: See the COPYING file included in this distribution.
 *
 * @package OpenID
 * @author JanRain, Inc. <openid@janrain.com>
 * @copyright 2005 Janrain, Inc.
 * @license http://www.gnu.org/copyleft/lesser.html LGPL
 */

/**
 * Require the HMAC/SHA-1 implementation for creating such hashes.
 */
require('HMACSHA1.php');

if (!defined('Net_OpenID_RAND_SOURCE')) {
    /**
     * The filename for a source of random bytes. Define this yourself
     * if you have a different source of randomness.
     */
    define('Net_OpenID_RAND_SOURCE', '/dev/urandom');
}

/**
 * An array of duplicate information for the randrange function.
 */
$Net_OpenID_CryptUtil_duplicate_cache = array();

/**
 * Net_OpenID_CryptUtil houses static utility functions.
 *
 * @package OpenID
 */
class Net_OpenID_CryptUtil {
    /** 
     * Get the specified number of random bytes.
     *
     * Attempts to use a cryptographically secure (not predictable)
     * source of randomness if available. If there is no high-entropy
     * randomness source available, it will fail. As a last resort,
     * for non-critical systems, define
     * <code>Net_OpenID_USE_INSECURE_RAND</code>, and the code will
     * fall back on a pseudo-random number generator.
     *
     * @static
     * @param int $num_bytes The length of the return value
     * @return string $num_bytes random bytes
     */
    function getBytes($num_bytes) {
        $f = @fopen("/dev/urandom", "r");
        if ($f === FALSE) {
            if (!defined(Net_OpenID_USE_INSECURE_RAND)) {
                trigger_error('Set Net_OpenID_USE_INSECURE_RAND to ' .
                              'continue with insecure random.',
                              E_USER_ERROR);
            }
            $bytes = '';
            for ($i = 0; $i < $num_bytes; $i += 4) {
                $bytes .= pack('L', mt_rand());
            }
            $bytes = substr($bytes, 0, $num_bytes);
        } else {
            $bytes = fread($f, $num_bytes);
            fclose($f);
        }
        return $bytes;
    }

    /**
     * Computes the SHA1 hash.
     *
     * @param string $str The input string.
     * @static
     * @return string The resulting SHA1 hash.
     */
    function sha1($str) {
        return sha1($str, true);
    }

    /**
     * Computes an HMAC-SHA1 digest.
     */
    function hmacSha1($key, $text) {
        return Net_OpenID_HMACSHA1($key, $text);
    }

    function fromBase64($str) {
        return base64_decode($str);
    }

    function toBase64($str) {
        return base64_encode($str);
    }

    function longToBinary($long) {
        return pack("L", $long);
    }

    function binaryToLong($str) {
        return unpack($str, "L");
    }

    function base64ToLong($str) {
        return Net_OpenID_CryptUtil::binaryToLong(Net_OpenID_CryptUtil::fromBase64($str));
    }

    function longToBase64($long) {
        return Net_OpenID_CryptUtil::toBase64(Net_OpenID_CryptUtil::longToBinary($long));
    }

    function strxor($x, $y) {
        if (strlen($x) != strlen($y)) {
            return null;
        }

        $str = "";
        for ($i = 0; $i < strlen($x); $i++) {
            $str .= chr(ord($x[$i]) ^ ord($y[$i]));
        }

        return $str;
    }

    function reversed($list) {
        if (is_string($list)) {
            return strrev($list);
        } else if (is_array($list)) {
            return array_reverse($list);
        } else {
            return null;
        }
    }

    function randrange($start, $stop = null, $step = 1) {

        global $Net_OpenID_CryptUtil_duplicate_cache;

        if ($stop == null) {
            $stop = $start;
            $start = 0;
        }

        $r = ($stop - $start);

        if (array_key_exists($r, $Net_OpenID_CryptUtil_duplicate_cache)) {
            list($duplicate, $nbytes) = $Net_OpenID_CryptUtil_duplicate_cache[$r];
        } else {
            $rbytes = Net_OpenID_CryptUtil::longToBinary($r);
            if ($rbytes[0] == '\x00') {
                $nbytes = strlen($rbytes) - 1;
            } else {
                $nbytes = strlen($rbytes);
            }

            $mxrand = (pow(256, $nbytes));

            // If we get a number less than this, then it is in the
            // duplicated range.
            $duplicate = $mxrand % $r;

            if (count($Net_OpenID_CryptUtil_duplicate_cache) > 10) {
                $Net_OpenID_CryptUtil_duplicate_cache = array();
            }

            $Net_OpenID_CryptUtil_duplicate_cache[$r] = array($duplicate, $nbytes);
        }

        while (1) {
            $bytes = '\x00' + Net_OpenID_CryptUtil::getBytes($nbytes);
            $n = Net_OpenID_CryptUtil::binaryToLong($bytes);
            // Keep looping if this value is in the low duplicated range
            if ($n >= $duplicate) {
                break;
            }
        }
        return $start + ($n % $r) * $step;
    }

    /**
     * Produce a string of length random bytes, chosen from chrs.
     */
    function randomString($length, $chrs = null) {
        if ($chrs == null) {
            return getBytes($length);
        } else {
            $n = strlen($chrs);
            $str = "";
            for ($i = 0; $i < $length; $i++) {
                $str .= $chrs[Net_OpenID_CryptUtil::randrange($n)];
            }
            return $str;
        }
    }
}

?>