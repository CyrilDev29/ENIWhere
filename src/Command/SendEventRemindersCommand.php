<?php

namespace App\Command;

use App\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Envoie un email de rappel 48h avant les événements.'
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private RegistrationRepository                                                $registrationRepository,
        #[Autowire(service: 'state_machine.participation')] private WorkflowInterface $participationWorkflow,
        private EntityManagerInterface                                                $em
    )
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $targetDateTime = new \DateTime('+48 hours');
        $output->writeln('Target date for reminders: ' . $targetDateTime->format('Y-m-d H:i:s'));

        // --- Log que la commande démarre ---
        file_put_contents(__DIR__ . '/../../var/log/reminder.log',
            date('Y-m-d H:i:s') . " - Command started\n", FILE_APPEND);

        $registrations = $this->registrationRepository->findRegistrationsForNotification($targetDateTime);
        $count = count($registrations);
        $output->writeln("Registrations found: $count");

        file_put_contents(__DIR__ . '/../../var/log/reminder.log',
            date('Y-m-d H:i:s') . " - Registrations found: $count\n", FILE_APPEND);

        foreach ($registrations as $registration) {
            $output->writeln('Registration ID: ' . $registration->getId() . ' | State: ' . $registration->getWorkflowState());

            file_put_contents(__DIR__ . '/../../var/log/reminder.log',
                date('Y-m-d H:i:s') . " - Processing registration ID: {$registration->getId()}\n", FILE_APPEND);

            if ($this->participationWorkflow->can($registration, 'notify')) {
                $this->participationWorkflow->apply($registration, 'notify');
                $this->em->flush();

                file_put_contents(__DIR__ . '/../../var/log/reminder.log',
                    date('Y-m-d H:i:s') . " - Workflow applied for registration ID: {$registration->getId()}\n", FILE_APPEND);
            } else {
                file_put_contents(__DIR__ . '/../../var/log/reminder.log',
                    date('Y-m-d H:i:s') . " - Cannot notify registration ID: {$registration->getId()}\n", FILE_APPEND);
            }
        }

        file_put_contents(__DIR__ . '/../../var/log/reminder.log',
            date('Y-m-d H:i:s') . " - Command finished\n\n", FILE_APPEND);

        $output->writeln('Rappels traités !');

        return Command::SUCCESS;
    }
}
