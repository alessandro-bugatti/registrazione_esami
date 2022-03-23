<?php

namespace Model;

use Util\Connection;

class ProfessoreRepository
{

    private function __construct()
    {
    }

    public static function verificaAutenticazione(string $username,
                                                  string $password):int{
        $connessione = Connection::getInstance();
        $sql = 'SELECT id, password FROM professore WHERE username = :username';
        $stmt = $connessione->prepare($sql);
        $stmt->execute([
            'username' => $username
        ]);
        $user = $stmt->fetch();
        //Controllo se esiste un utente con quello username
        if ($user === false)
            return -1;
        //Se esiste controllo se la password è corretta
        if (!password_verify($password, $user['password']))
            return -1;
        return $user['id'];

    }
}