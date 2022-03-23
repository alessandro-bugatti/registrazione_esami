<?php

namespace Model;

use DateTimeImmutable;
use Firebase\JWT\JWT;
use Util\Connection;

class ProfessoreRepository
{

    private function __construct()
    {
    }

    public static function verificaAutenticazione(string $username,
                                                  string $password):?string{
        $connessione = Connection::getInstance();
        $sql = 'SELECT id, password FROM professore WHERE username = :username';
        $stmt = $connessione->prepare($sql);
        $stmt->execute([
            'username' => $username
        ]);
        $user = $stmt->fetch();
        //Controllo se esiste un utente con quello username
        if ($user === false)
            return null;
        //Se esiste controllo se la password è corretta
        if (!password_verify($password, $user['password']))
            return null;
        //Creazione del token JWT, alcuni parametri sono inseriti per completezza
        //ma non verranno usati nell'applicazione
        $secretKey  = JWT_SECRET;
        $tokenId    = base64_encode(random_bytes(16));
        $issuedAt   = new DateTimeImmutable();
        //Questo serve a far scadere i token secondo una policy gestita da chi
        //serve il token. Per motivi di sicurezza dovrebbe essere un intervallo breve
        //tipo 15 minuti, per comodità di scrittura verrà invece messo in questo
        //esempio a 2 settimane
        $expire     = $issuedAt->modify('+14 day')->getTimestamp();      // Add 60 seconds
        $serverName = 'imparando.net';

        // Create the token as an array
        $data = [
            'iat'  => $issuedAt->getTimestamp(),    // Issued at: time when the token was generated
            'jti'  => $tokenId,                     // Json Token Id: an unique identifier for the token
            'iss'  => $serverName,                  // Issuer
            'nbf'  => $issuedAt->getTimestamp(),    // Not before
            'exp'  => $expire,                      // Expire
            // Dati che interessano l'applicazione, in questo caso l'id e lo username
            // ma può essere qualsiasi cosa si ritenga importante
            'data' => [
                'id' => $user['id'],
                'userName' => $username,            // User name
            ]
        ];

        // Encode the array to a JWT string.
        return JWT::encode(
            $data,      //Data to be encoded in the JWT
            $secretKey, // The signing key
            'HS512'     // Algorithm used to sign the token, see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40#section-3
        );
    }
}