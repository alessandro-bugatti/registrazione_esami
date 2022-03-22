<?php
namespace Model;

use Util\Connection;

class VotoRepository{

    private function __construct()
    {
    }

    public static function inserisciVoto(float $voto,
                                         int $idStudente,
                                         int $idEsame,
                                         int $idProfessore):bool {
        $connection = Connection::getInstance();
        //TODO da gestire successivamente
        $tipologia = "teoria";
        $stato = "accettato";
        $idStudente = 1; //Sarebbe da recuperare dalla matricola
        $sql = 'INSERT INTO prova (valutazione, tipologia, stato, id_studente, id_professore,id_esame) '.
            'VALUES(:voto, :tipologia,:stato,:id_studente,:id_professore,:id_esame)';
        $stmt = $connection->prepare($sql);
        return $stmt->execute([
            'voto' => $voto,
            'tipologia' => $tipologia,
            'stato' => $stato,
            'id_studente' => $idStudente,
            'id_professore' => $idProfessore,
            'id_esame' => $idEsame
        ]);
    }

}