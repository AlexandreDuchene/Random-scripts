/**
  * @param array     $existingAppointments
  * @param \DateTime $startTime Début de la journée, à récupérer en BDD (ou là où c'est configuré)
  * @param \DateTime $endTime Fin de la journée, à récupérer en BDD (ou là où c'est configuré)
  * @param int       $shiftBetweenAppointments La pause à laisser vide entre les RDV
  * @param           $serviceType Type de prestation s'il est connu
  *
  * @return array
**/
public function getAvailableAppointments(
    array $existingAppointments,
    \DateTime $startTime,
    \DateTime $endTime,
    $shiftBetweenAppointments = 15,
    $serviceType = null
) {
    // Si le type de prestation est fourni, on récupère la durée. Sinon on part sur la plus petite valeur possible.
    if (null !== $serviceType) {
        $appointmentDuration = $serviceType->getDuration();
    } else {
        $appointmentDuration = 5;
    }

    // Si il faut laisser une pause entre les RDV, on incrémente artificiellement la date de fin des RDV existants.
    // Attention aux effets de bord si $appointment->getEndDate() est utilisée ailleurs dans le code par la suite.
    // Dans ce cas, il faut cloner la \DateTime avant de la modifier.
    if ($shiftBetweenAppointments > 0) {
        foreach ($existingAppointments as $appointment) {
            $appointment->getEndDate()->modify("+$shiftBetweenAppointments minutes"):
         }
    }

    // Tableau à renvoyer, contenant les créneaux dispo
    $availableAppointments = [];

    // $i est la \DateTime de début du créneau potentiel. $j est la \DateTime de fin + la pause entre RDV
    for ($i = clone $startTime; $i->modify("+$appointmentDuration minutes"); $i < $endTime) {
        // Pas le choix malheureusement d'instancier une nouvelle \DateTime à chaque tour de boucle, à cause des mutations
        $j = (clone $i)->modify('+'.($appointmentDuration + $shiftBetweenAppointments).' minutes");

        foreach ($existingAppointments as $appointment) {
            // Si la date de début ou de fin du créneau potentiel empiète sur un RDV existant
            if ($i >= $appointment->getStartDate() && $i < $appointment->getEndDate()
                || $j > $appointment->getStartDate() && $j <= $appointment->getEndDate()
            ) {
                // On abandonne ce créneau
                continue 2;
            }
        }

        // Ici, stocker ce dont il y a besoin dans le tableau.
        // Dans l'exemple on stocke juste la date de début du créneau.
        $availableAppointments[] = clone $i;
    }

    return $availableAppointments;
}
