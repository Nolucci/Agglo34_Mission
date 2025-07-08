<?php

namespace App\Command;

use App\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:maintenance',
    description: 'Active ou désactive le mode maintenance'
)]
class MaintenanceCommand extends Command
{
    private SettingsRepository $settingsRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SettingsRepository $settingsRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->settingsRepository = $settingsRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::REQUIRED, 'Action à effectuer (on/off/status)')
            ->addArgument('message', InputArgument::OPTIONAL, 'Message de maintenance (optionnel)')
            ->setHelp('Cette commande permet de gérer le mode maintenance de l\'application.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = $input->getArgument('action');
        $message = $input->getArgument('message');

        $settings = $this->settingsRepository->findOneBy([]);
        if (!$settings) {
            $settings = new \App\Entity\Settings();
            $settings->setDarkTheme(false);
            $settings->setCrudEnabled(false);
            $settings->setDisplayMode('liste');
            $settings->setItemsPerPage(10);
            $settings->setAppName('Agglo34 Mission');
            $settings->setWelcomeMessage('Bienvenue sur l\'application Agglo34 Mission');
            $settings->setAlertThreshold(5);
            $settings->setFeatureEnabled(false);
            $settings->setMaintenanceMode(false);
            $settings->setMaintenanceMessage('Application en maintenance. Veuillez réessayer plus tard.');
        }

        switch (strtolower($action)) {
            case 'on':
            case 'enable':
                $settings->setMaintenanceMode(true);
                if ($message) {
                    $settings->setMaintenanceMessage($message);
                }
                $this->entityManager->persist($settings);
                $this->entityManager->flush();
                $io->success('Mode maintenance activé.');
                if ($message) {
                    $io->note('Message: ' . $message);
                }
                break;

            case 'off':
            case 'disable':
                $settings->setMaintenanceMode(false);
                $this->entityManager->persist($settings);
                $this->entityManager->flush();
                $io->success('Mode maintenance désactivé.');
                break;

            case 'status':
                if ($settings->isMaintenanceMode()) {
                    $io->warning('Mode maintenance ACTIVÉ');
                    $io->note('Message: ' . $settings->getMaintenanceMessage());
                } else {
                    $io->success('Mode maintenance DÉSACTIVÉ');
                }
                break;

            default:
                $io->error('Action non reconnue. Utilisez: on, off ou status');
                return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}