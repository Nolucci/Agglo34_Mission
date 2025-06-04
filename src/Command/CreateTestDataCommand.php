<?php

namespace App\Command;

use App\Entity\Box;
use App\Entity\PhoneLine;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-test-data',
    description: 'Crée des données de test pour l\'application',
)]
class CreateTestDataCommand extends Command
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Création des paramètres par défaut
        $this->createSettings($io);
        
        // Création des lignes téléphoniques
        $this->createPhoneLines($io);
        
        // Création des équipements
        $this->createEquipments($io);

        $io->success('Données de test créées avec succès.');

        return Command::SUCCESS;
    }

    private function createSettings(SymfonyStyle $io): void
    {
        $settingsRepository = $this->entityManager->getRepository(Settings::class);
        $settings = $settingsRepository->findOneBy([]);

        if (!$settings) {
            $settings = new Settings();
            $settings->setDarkTheme(false);
            $settings->setCrudEnabled(true);
            $settings->setDisplayMode('liste');
            $settings->setItemsPerPage(10);
            $settings->setAppName('Agglo34 Mission');
            $settings->setWelcomeMessage('Bienvenue sur l\'application Agglo34 Mission');
            $settings->setAlertThreshold(5);
            $settings->setFeatureEnabled(true);

            $this->entityManager->persist($settings);
            $this->entityManager->flush();

            $io->text('Paramètres par défaut créés.');
        } else {
            $io->text('Les paramètres existent déjà.');
        }
    }

    private function createPhoneLines(SymfonyStyle $io): void
    {
        $phoneLineRepository = $this->entityManager->getRepository(PhoneLine::class);
        $count = count($phoneLineRepository->findAll());

        if ($count > 0) {
            $io->text('Des lignes téléphoniques existent déjà.');
            return;
        }

        // Créer d'abord les municipalités si elles n'existent pas
        $municipalityNames = ['Béziers', 'Sérignan', 'Valras-Plage', 'Villeneuve-lès-Béziers', 'Sauvian'];
        $municipalityEntities = [];
        
        $municipalityRepository = $this->entityManager->getRepository(\App\Entity\Municipality::class);
        
        foreach ($municipalityNames as $name) {
            $municipality = $municipalityRepository->findOneBy(['name' => $name]);
            
            if (!$municipality) {
                $municipality = new \App\Entity\Municipality();
                $municipality->setName($name);
                $municipality->setAddress($name . ', 34500');
                $municipality->setContactName('Contact de ' . $name);
                $municipality->setContactPhone('04' . rand(10000000, 99999999));
                
                $this->entityManager->persist($municipality);
                $io->text('Municipalité créée : ' . $name);
            }
            
            $municipalityEntities[] = $municipality;
        }
        
        $this->entityManager->flush();
        
        // Maintenant créer les lignes téléphoniques
        $operators = ['Orange', 'SFR', 'Bouygues Telecom', 'Free'];
        $services = ['Mairie', 'Police Municipale', 'Services Techniques', 'Médiathèque', 'CCAS'];
        $lineTypes = ['Fixe', 'Mobile', 'Internet', 'Fax'];

        for ($i = 0; $i < 20; $i++) {
            $phoneLine = new PhoneLine();
            $phoneLine->setMunicipality($municipalityEntities[array_rand($municipalityEntities)]);
            $phoneLine->setOperator($operators[array_rand($operators)]);
            $phoneLine->setService($services[array_rand($services)]);
            $phoneLine->setLineType($lineTypes[array_rand($lineTypes)]);
            $phoneLine->setLocation('Bureau ' . rand(1, 10));
            $phoneLine->setAssignedTo('Agent ' . rand(1, 5));
            $phoneLine->setIsGlobal(rand(0, 1) === 1);

            $this->entityManager->persist($phoneLine);
        }

        $this->entityManager->flush();
        $io->text('20 lignes téléphoniques créées.');
    }

    private function createEquipments(SymfonyStyle $io): void
    {
        $boxRepository = $this->entityManager->getRepository(Box::class);
        $count = count($boxRepository->findAll());

        if ($count > 0) {
            $io->text('Des équipements existent déjà.');
            return;
        }

        // Récupérer les municipalités existantes
        $municipalityRepository = $this->entityManager->getRepository(\App\Entity\Municipality::class);
        $municipalityEntities = $municipalityRepository->findAll();
        
        if (empty($municipalityEntities)) {
            $io->error('Aucune municipalité trouvée. Veuillez d\'abord créer des municipalités.');
            return;
        }
        
        $types = ['Ordinateur', 'Imprimante', 'Serveur', 'Switch', 'Routeur'];
        $brands = ['Dell', 'HP', 'Lenovo', 'Cisco', 'Brother'];
        $locations = ['Mairie', 'Police Municipale', 'Services Techniques', 'Médiathèque', 'CCAS'];

        for ($i = 0; $i < 30; $i++) {
            $type = $types[array_rand($types)];
            $brand = $brands[array_rand($brands)];
            $municipality = $municipalityEntities[array_rand($municipalityEntities)];
            
            $box = new Box();
            $box->setName($type . ' ' . $brand . ' ' . rand(100, 999));
            $box->setDescription('Description de l\'équipement ' . ($i + 1));
            $box->setType($type);
            $box->setBrand($brand);
            $box->setModel('Modèle ' . rand(1000, 9999));
            $box->setMunicipality($municipality->getName());
            $box->setLocation($locations[array_rand($locations)]);
            $box->setAssignedTo('Agent ' . rand(1, 5));
            $box->setIsActive(rand(0, 5) > 0); 

            $this->entityManager->persist($box);
        }

        $this->entityManager->flush();
        $io->text('30 équipements créés.');
    }
}