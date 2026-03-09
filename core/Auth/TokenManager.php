<?php

declare(strict_types=1);

namespace Core\Auth;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class TokenManager
{
    private string $secret;
    private string $algorithm = 'HS256';

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'change-me');
    }

    /**
     * Gera um novo JWT para um usuário.
     * 
     * @param int|string $userId ID do usuário
     * @param array $extraClaims Dados adicionais (opcional)
     * @return string Token gerado
     */
    public function generateToken(int|string $userId, array $extraClaims = []): string
    {
        $payload = array_merge([
            'iss' => env('APP_URL', 'localhost'),
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + (int)env('JWT_EXPIRATION', 3600)
        ], $extraClaims);

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * Valida um token e retorna o payload.
     * 
     * @param string $token
     * @return object|null Payload decodificado ou null se inválido
     */
    public function validateToken(string $token): ?object
    {
        try {
            return JWT::decode($token, new Key($this->secret, $this->algorithm));
        } catch (Exception $e) {
            logger()->error("Erro ao validar JWT: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Tenta extrair o ID do usuário do token (subject).
     */
    public function getUserIdFromToken(string $token): mixed
    {
        $payload = $this->validateToken($token);
        return $payload ? $payload->sub : null;
    }
}
