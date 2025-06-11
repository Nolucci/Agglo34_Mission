<?php

namespace App\Command;

use App\Entity\Box;
use App\Entity\Equipment;
use App\Entity\Log;
use App\Entity\PhoneLine;
use App\Entity\Settings;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Municipality;
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

        // Création des box
        $this->createBoxes($io);

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

        $phoneBrands = ['Samsung', 'Apple', 'Xiaomi', 'Huawei', 'Nokia'];
        $models = ['Galaxy S21', 'iPhone 13', 'Redmi Note 10', 'P40 Pro', 'Lumia 950'];

        for ($i = 0; $i < 20; $i++) {
            $phoneLine = new PhoneLine();
            $phoneLine->setMunicipality($municipalityEntities[array_rand($municipalityEntities)]);
            $phoneLine->setOperator($operators[array_rand($operators)]);
            $phoneLine->setService($services[array_rand($services)]);
            $phoneLine->setLineType($lineTypes[array_rand($lineTypes)]);
            $phoneLine->setLocation('Bureau ' . rand(1, 10));
            $phoneLine->setAssignedTo('Agent ' . rand(1, 5));
            $phoneLine->setIsWorking(rand(0, 1) === 1);
            $phoneLine->setPhoneBrand($phoneBrands[array_rand($phoneBrands)]);
            $phoneLine->setModel($models[array_rand($models)]);

            $this->entityManager->persist($phoneLine);
        }

        $this->entityManager->flush();
        $io->text('20 lignes téléphoniques créées.');
    }

    private function createBoxes(SymfonyStyle $io): void
    {
        $boxRepository = $this->entityManager->getRepository(Box::class);
        $count = count($boxRepository->findAll());

        if ($count > 0) {
            $io->text('Des box existent déjà.');
            return;
        }

        // Récupérer les municipalités existantes
        $municipalityRepository = $this->entityManager->getRepository(Municipality::class);
        $municipalityEntities = $municipalityRepository->findAll();

        if (empty($municipalityEntities)) {
            $io->error('Aucune municipalité trouvée. Veuillez d\'abord créer des municipalités.');
            return;
        }

        $types = ['Box Fibre', 'Box ADSL', 'Box 4G'];
        $services = ['Mairie', 'Police Municipale', 'Services Techniques', 'Médiathèque', 'CCAS'];
        $statuts = ['Actif', 'Inactif'];

        for ($i = 0; $i < 15; $i++) {
            $box = new Box();
            $box->setCommune($municipalityEntities[array_rand($municipalityEntities)]);
            $box->setService($services[array_rand($services)]);
            $box->setAdresse('Adresse ' . rand(1, 50));
            $box->setLigneSupport('04' . rand(10000000, 99999999));
            $box->setType($types[array_rand($types)]);
            $box->setAttribueA('Agent ' . rand(1, 5));
            $box->setStatut($statuts[array_rand($statuts)]);

            $this->entityManager->persist($box);
        }

        $this->entityManager->flush();
        $io->text('15 box créées.');
    }

    private function createEquipments(SymfonyStyle $io): void
    {
        $equipmentRepository = $this->entityManager->getRepository(Equipment::class);
        $count = count($equipmentRepository->findAll());

        if ($count > 0) {
            $io->text('Des équipements existent déjà.');
            return;
        }

        // Récupérer les municipalités existantes
        $municipalityRepository = $this->entityManager->getRepository(Municipality::class);
        $municipalityEntities = $municipalityRepository->findAll();

        if (empty($municipalityEntities)) {
            $io->error('Aucune municipalité trouvée. Veuillez d\'abord créer des municipalités.');
            return;
        }

        $modeles = ['PC Portable', 'PC Fixe', 'Imprimante Laser', 'Serveur Rack', 'Switch manageable'];
        $services = ['Mairie', 'Police Municipale', 'Services Techniques', 'Médiathèque', 'CCAS'];
        $os = ['Windows 10', 'Windows 11', 'Ubuntu', 'macOS'];
        $statuts = ['Actif', 'Inactif', 'Panne'];

        for ($i = 0; $i < 30; $i++) {
            $equipment = new Equipment();
            $equipment->setCommune($municipalityEntities[array_rand($municipalityEntities)]);
            $equipment->setEtiquetage('ETQ-' . rand(1000, 9999));
            $equipment->setModele($modeles[array_rand($modeles)]);
            $equipment->setNumeroSerie('SN-' . uniqid());
            $equipment->setService($services[array_rand($services)]);
            $equipment->setUtilisateur('Utilisateur ' . rand(1, 10));

            // Date de garantie aléatoire dans les 3 prochaines années
            $dateGarantie = new \DateTimeImmutable();
            $dateGarantie = $dateGarantie->add(new \DateInterval('P' . rand(0, 3) . 'Y'));
            $equipment->setDateGarantie($dateGarantie);

            $equipment->setOs($os[array_rand($os)]);
            $equipment->setVersion(rand(1, 10) . '.' . rand(0, 9));
            $equipment->setStatut($statuts[array_rand($statuts)]);

            $this->entityManager->persist($equipment);
        }

        $this->entityManager->flush();
        $io->text('30 équipements créés.');
    }
}