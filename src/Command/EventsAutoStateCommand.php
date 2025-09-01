<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Helper\EventStateService;
use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:events:auto-state',
    description: 'Met à jour automatiquement les états des événements en fonction des dates et de la capacité'
)]
class EventsAutoStateCommand extends Command
{
    public function __construct(
        private readonly EventRepository $eventRepository,
        private readonly EventStateService $svc,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now = new DateTimeImmutable();
        $events = $this->eventRepository->findAll();
        $updated = 0;

        foreach ($events as $event) {
            $status = $event->getState(); //
            if (!$status) { continue; }

            $openAt  = $event->getRegistrationOpenAt();
            $closeAt = $event->getRegistrationCloseAt();

            // ------- Ouverture auto si publié et avant deadline ---
            if ($status === 'PUBLISHED'
                && $openAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($openAt)
                && (!$closeAt || $now < DateTimeImmutable::createFromMutable($closeAt))
            ) {
                if ($this->svc->apply($event, 'open_regs')) {
                    $updated++;
                    $status = $event->getState();
                }
            }

            // -------- Fermeture auto par deadline ---
            if ($status === 'OPEN'
                && $closeAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($closeAt)
            ) {
                if ($this->svc->apply($event, 'close_regs')) {
                    $updated++;
                    $status = $event->getState();
                }
            }

            // --- -------Démarrage auto ---
            $startAt = $event->getStartDateTime();
            if ($status === 'CLOSED'
                && $startAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($startAt)
            ) {
                if ($this->svc->apply($event, 'start')) {
                    $updated++;
                    $status = $event->getState();
                }
            }

            // --- Fin auto ---
            $endAt = $event->getEndDateTime();
            if ($status === 'ONGOING'
                && $endAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($endAt)
            ) {
                if ($this->svc->apply($event, 'finish')) {
                    $updated++;
                    $status = $event->getState();
                }
            }

            // --- Fermeture auto si capacité atteinte ---
            $max   = $event->getMaxParticipant();
            $count = \count($event->getRegistrations());
            if ($status === 'OPEN'
                && $max !== null && $max > 0
                && $count >= $max
            ) {
                if ($this->svc->apply($event, 'close_regs')) {
                    $updated++;
                }
            }
        }


        $archiveThreshold = $now->modify('-1 month'); // date il y a 1 mois
        if ($status === 'FINISHED'
            && $endAt instanceof \DateTimeInterface
            && DateTimeImmutable::createFromMutable($endAt) <= $archiveThreshold
        ) {
            if ($this->svc->apply($event, 'archive')) {
                $updated++;
                $status = $event->getState();
            }
        }
        $output->writeln("Mises à jour effectuées : $updated");
        return Command::SUCCESS;
    }
}
