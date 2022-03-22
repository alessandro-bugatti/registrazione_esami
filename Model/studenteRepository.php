<?php

namespace Model;

use Util\Connection;

class StudenteRepository{

    private function __construct()
    {
    }

    public static function getIdFromMatricola(string $matricola):int{
        $connection = Connection::getInstance();
        $sql = 'SELECT id FROM studente WHERE matricola = :matricola';
        $stmt = $connection->prepare($sql);
        $stmt->execute([
                'matricola' => $matricola
            ]);
        $data = $stmt->fetch();
        return $data['id'];
    }
}
