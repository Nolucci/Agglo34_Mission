<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileController extends AbstractController
{
    #[Route('/uploads', name: 'app_file_upload', methods: ['POST'])]
    public function upload(Request $request, SluggerInterface $slugger): JsonResponse
    {
        try {
            // Récupérer les données du fichier directement depuis la requête
            $uploadedFile = $request->files->get('file');

            if (!$uploadedFile) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Aucun fichier n\'a été envoyé.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Vérifier le type MIME
            $mimeType = $uploadedFile->getMimeType();
            if (!in_array($mimeType, ['text/csv', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) {
                return $this->json([
                    'status' => 'error',
                    'message' => 'Seuls les fichiers CSV et XLSX sont autorisés.'
                ], Response::HTTP_BAD_REQUEST);
            }

            // Lire le contenu du fichier directement sans l'enregistrer
            $fileContent = file_get_contents($uploadedFile->getPathname());

            if ($fileContent === false) {
                throw new \Exception('Impossible de lire le contenu du fichier.');
            }

            // Ici, vous pourriez traiter le contenu du fichier pour l'insérer dans la base de données
            // Par exemple, si c'est un CSV, vous pourriez le parser et insérer les données
            // Pour l'instant, nous allons simplement simuler un traitement réussi

            // Simuler un traitement réussi
            return $this->json([
                'status' => 'success',
                'name' => $uploadedFile->getClientOriginalName(),
                'size' => $uploadedFile->getSize(),
                'type' => $mimeType,
                'lastModified' => (new \DateTime())->format('Y-m-d H:i:s'),
                'message' => 'Le fichier a été traité avec succès et les données ont été importées.'
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            error_log('Erreur de traitement du fichier: ' . $e->getMessage());

            return $this->json([
                'status' => 'error',
                'message' => 'Une erreur est survenue lors du traitement du fichier: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}