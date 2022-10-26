<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Services\Admin\AdminService;
use App\ViewModels\JsonReturnViewModel;
use Elliptic\EC;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use kornrunner\Keccak;
use JWTAuth;

/**
 * Created by PhpStorm.
 * Filename: Web3AuthService.php
 * Project Name: questa-backend.loc
 * Author: Akbarali
 * Date: 13/04/2022
 * Time: 6:23 PM
 * Github: https://github.com/akbarali1
 * Telegram: @akbar_aka
 * E-mail: me@akbarali.uz
 */
class Web3AuthService
{
    public function verify(string $signature, string $address, string $sign_message): JsonResponse
    {
        $result = $this->verifySignature($sign_message, $signature, $address);
        // If $result is true, perform additional logic like logging the user in, or by creating an account if one doesn't exist based on the Ethereum address
        if ($result) {
            $user = User::query()->where('eth_address', $address)->first();
            if (!$user) {
                return JsonReturnViewModel::toJsonBeautify(AdminService::returnError('Bunday Foydalanuvchi topilmadi!'));
            }

            return AuthNormalService::respondWithToken($this->getJWTToken($user), 'Metamask Auth');
        }

        return JsonReturnViewModel::toJsonBeautify(
            AdminService::returnError(
                [
                    'message' => 'Failed to verify signature',
                ]
            )
        );
    }

    protected function verifySignature(string $message, string $signature, string $address): bool
    {
        $hash  = Keccak::hash(sprintf("\x19Ethereum Signed Message:\n%s%s", strlen($message), $message), 256);
        $sign  = [
            'r' => substr($signature, 2, 64),
            's' => substr($signature, 66, 64),
        ];
        $recid = ord(hex2bin(substr($signature, 130, 2))) - 27;

        if ($recid != ($recid & 1)) {
            return false;
        }

        $pubkey          = (new EC('secp256k1'))->recoverPubKey($hash, $sign, $recid);
        $derived_address = '0x'.substr(Keccak::hash(substr(hex2bin($pubkey->encode('hex')), 1), 256), 24);

        return (Str::lower($address) === $derived_address);
    }


    public function getJWTToken($user)
    {
        if (!$userToken = JWTAuth::fromUser($user)) {
            return AdminService::returnError([
                'message' => 'Failed to create token',
            ]);
        }

        return $userToken;
    }
}
