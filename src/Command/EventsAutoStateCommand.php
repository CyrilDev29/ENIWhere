<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Helper\EventStateService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'app:events:auto-state',
    description: 'Met à jour automatiquement les états des événements avec log fichier'
)]
class EventsAutoStateCommand extends Command
{
    private string $logFile;

    public function __construct(
        private EventRepository $eventRepository,
        private EventStateService $eventStateService,
    ) {
        parent::__construct();
        $this->logFile = __DIR__ . '/../../var/log/events-auto-state.log';
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new \DateTimeImmutable();
        $updated = 0;

        file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Command started\n", FILE_APPEND);

        foreach ($this->eventRepository->findAll() as $event) {
            $status = $event->getState();
            if (!$status) continue;

            //$openAt  = $event->getRegistrationOpenAt()?->format('Y-m-d H:i:s') ? \DateTimeImmutable::createFromMutable($event->getRegistrationOpenAt()) : null;
            $closeAt = $event->getRegistrationCloseAt()?->format('Y-m-d H:i:s') ? \DateTimeImmutable::createFromMutable($event->getRegistrationCloseAt()) : null;
            $startAt = $event->getStartDateTime() ? \DateTimeImmutable::createFromMutable($event->getStartDateTime()) : null;
            $endAt   = $event->getEndDateTime() ? \DateTimeImmutable::createFromMutable($event->getEndDateTime()) : null;
            $max     = $event->getMaxParticipant();
            $count   = \count($event->getRegistrations());

            //-------------- Ouverture automatique   on reserve à l'utilisateur uniquement la publication------ pas d'automatisation
          /*  if ($status === 'PUBLISHED' && $openAt && $now >= $openAt && (!$closeAt || $now < $closeAt)) {
                if ($this->eventStateService->apply($event, 'open_regs')) {
                    $updated++;
                    file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Event {$event->getId()} opened\n", FILE_APPEND);
                }
            }*/

            // -----------Fermeture par date----OK
            if ($status === 'OPEN' && $closeAt && $now >= $closeAt) {
                if ($this->eventStateService->apply($event, 'close_regs')) {
                    $updated++;
                    file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Event {$event->getId()} closed by date\n", FILE_APPEND);
                }
            }

            // -------Fermeture par capacité------- OK
            if ($status === 'OPEN' && $max && $count >= $max) {
                if ($this->eventStateService->apply($event, 'close_regs')) {
                    $updated++;
                    file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Event {$event->getId()} closed by capacity\n", FILE_APPEND);
                }
            }

            //------------ Démarrage------------OK
            if ($status === 'CLOSED' && $startAt && $now >= $startAt) {
                if ($this->eventStateService->apply($event, 'start')) {
                    $updated++;
                    file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Event {$event->getId()} started\n", FILE_APPEND);
                }
            }

            // -------Fin--------OK
            if ($status === 'ONGOING' && $endAt && $now >= $endAt) {
                if ($this->eventStateService->apply($event, 'finish')) {
                    $updated++;
                    file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Event {$event->getId()} finished\n", FILE_APPEND);
                }
            }

            // -------Archivage après 1 mois------- OK
            $archiveThreshold = $now->modify('-1 month');
            if (in_array($status, ['FINISHED', 'CANCELED'], true) && $endAt && $endAt <= $archiveThreshold) {
                if ($this->eventStateService->apply($event, 'archive')) {
                    $updated++;
                    file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Event {$event->getId()} archived\n", FILE_APPEND);
                }
            }
        }

        file_put_contents($this->logFile, date('Y-m-d H:i:s') . " - Command finished, total updates: $updated\n\n", FILE_APPEND);
        $output->writeln("Mises à jour effectuées : $updated");

        return Command::SUCCESS;
    }
}
