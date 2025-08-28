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
            $status = $event->getStatus();
            if (!$status) { continue; }

            // --- Ouverture auto (si publié et avant deadline) ---
            $openAt  = $event->getRegistrationOpenAt();   // helper ajouté
            $closeAt = $event->getRegistrationCloseAt();  // = registrationDeadline

            if ($status === 'published'
                && $openAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($openAt)
                && (!$closeAt || $now < DateTimeImmutable::createFromMutable($closeAt))
            ) {
                if ($this->svc->apply($event, 'open_regs')) {
                    $updated++; $status = $event->getStatus();
                }
            }

            // --- Fermeture auto par deadline ---
            if ($status === 'registration_open'
                && $closeAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($closeAt)
            ) {
                if ($this->svc->apply($event, 'close_regs')) {
                    $updated++; $status = $event->getStatus();
                }
            }

            // --- Démarrage auto ---
            $startAt = $event->getStartDateTime();
            if ($status === 'registration_closed'
                && $startAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($startAt)
            ) {
                if ($this->svc->apply($event, 'start')) {
                    $updated++; $status = $event->getStatus();
                }
            }

            // --- Fin auto ---
            $endAt = $event->getEndDateTime(); // helper ajouté (start + duration)
            if ($status === 'ongoing'
                && $endAt instanceof \DateTimeInterface
                && $now >= DateTimeImmutable::createFromMutable($endAt)
            ) {
                if ($this->svc->apply($event, 'finish')) {
                    $updated++;
                }
            }

            // --- Fermeture auto si capacité atteinte (optionnel) ---
            $max   = $event->getMaxParticipant();
            $count = \count($event->getRegistrations());
            if ($status === 'registration_open'
                && $max !== null && $max > 0
                && $count >= $max
            ) {
                if ($this->svc->apply($event, 'close_regs')) {
                    $updated++;
                }
            }
        }

        $output->writeln("Mises à jour effectuées : $updated");
        return Command::SUCCESS;
    }
}
