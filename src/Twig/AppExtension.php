<?php
namespace App\Twig;

use App\Service\EntrepriseActiveService;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private EntrepriseActiveService $entrepriseService
    ) {}

    public function getGlobals(): array
    {
        try {
            return [
                'entrepriseActiveHeader' => $this->entrepriseService->getEntrepriseActive(),
                'entreprisesHeader'      => $this->entrepriseService->getEntreprises(),
            ];
        } catch (\Exception $e) {
            return [
                'entrepriseActiveHeader' => null,
                'entreprisesHeader'      => [],
            ];
        }
    }
}