<?php

namespace App\Providers;

use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Entities\Partner;
use App\Repositories\ApplicantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // {campaign} and {applicant} resolve within the authenticated partner's own records, so a malformed id,
        // an unknown one and another partner's all give the same 404. The partner middleware runs first (bootstrap/app.php).
        Route::bind('campaign', function (string $id): Campaign {
            $campaign = Str::isUuid($id)
                ? $this->app->make(EntityManagerInterface::class)->getRepository(Campaign::class)
                    ->findOneBy(['id' => strtolower($id), 'partner' => $this->partner()])
                : null;

            return $campaign ?? throw new NotFoundHttpException('Campaign not found.');
        });

        Route::bind('applicant', function (string $id): Applicant {
            /** @var ApplicantRepository $applicants */
            $applicants = $this->app->make(EntityManagerInterface::class)->getRepository(Applicant::class);
            $applicant = Str::isUuid($id) ? $applicants->findForPartner($id, $this->partner()) : null;

            return $applicant ?? throw new NotFoundHttpException('Applicant not found.');
        });
    }

    private function partner(): Partner
    {
        return $this->app->make('request')->attributes->get('partner');
    }
}
