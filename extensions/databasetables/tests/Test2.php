<?php
/*
class BIP32 {
    public static function HMACSHA512($key, $data) {
        return hash_hmac("sha512", $data, $key, true);
    }

    public static function getPublicKey($privateKey, $publicKeyCompressed = true) {
        $secp256k1 = secp256k1_start();
        $publicKey = "";
        secp256k1_ec_pubkey_create($secp256k1, $publicKey, $privateKey);
        if ($publicKeyCompressed) {
            secp256k1_ec_pubkey_compress($secp256k1, $publicKey, $publicKey);
        }
        secp256k1_stop($secp256k1);
        return $publicKey;
    }

    public static function deriveChild($key, $chainCode, $index, $hardened = false) {
        $data = "";
        if ($hardened) {
            $data .= chr(0);
            $data .= $key;
        } else {
            $data .= self::getPublicKey($key);
        }
        $data .= pack("N", $index);
        $I = self::HMACSHA512($chainCode, $data);
        $Il = substr($I, 0, 32);
        $Ir = substr($I, 32);
        $privateKey = gmp_strval(gmp_add(gmp_init(bin2hex($key), 16), gmp_init(bin2hex($Il), 16)), 16);
        $privateKey = str_pad(substr(hex2bin($privateKey), -32), 32, chr(0), STR_PAD_LEFT);
        $publicKey = self::getPublicKey($privateKey);
        return array(
            "privateKey" => $privateKey,
            "publicKey" => $publicKey,
            "chainCode" => $Ir
        );
    }

    public static function derivePath($key, $chainCode, $path) {
        $components = explode("/", $path);
        foreach ($components as $component) {
            if ($component === "") {
                continue;
            }
            $hardened = false;
            if (strpos($component, "'") !== false) {
                $hardened = true;
                $component = substr($component, 0, strlen($component) - 1);
            }
            $index = intval($component);
            $result = self::deriveChild($key, $chainCode, $index, $hardened);
            $key = $result["privateKey"];
            $chainCode = $result["chainCode"];
        }
        return array(
            "privateKey" => $key,
            "publicKey" => self::getPublicKey($key),
            "chainCode" => $chainCode
        );
    }
}

// Example
*/


?>