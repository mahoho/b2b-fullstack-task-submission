// app/Http/RouteBindings.php
<?php

namespace App\Http;

use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Repositories\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RouteBindings
{
    // Registered at boot (not in routes/api.php) so the bindings survive route:cache.
    // A malformed id, a missing row and another partner's row all give the same 404.
    public static function register(): void
    {
        Route::bind('campaign', function (string $id): Campaign {
            $partner = request()->attributes->get('partner');

            if (! Str::isUuid($id)) {
                throw new NotFoundHttpException('Campaign not found.');
            }

            return app(EntityManagerInterface::class)->getRepository(Campaign::class)
                ->findOneBy(['id' => strtolower($id), 'partner' => $partner])
                ?? throw new NotFoundHttpException('Campaign not found.');
        });

        Route::bind('applicant', function (string $id): Applicant {
            $partner = request()->attributes->get('partner');

            if (! Str::isUuid($id)) {
                throw new NotFoundHttpException('Applicant not found.');
            }

            /** @var ApplicantRepository $applicants */
            $applicants = app(EntityManagerInterface::class)->getRepository(Applicant::class);

            return $applicants->findForPartner($id, $partner)
                ?? throw new NotFoundHttpException('Applicant not found.');
        });
    }
}
