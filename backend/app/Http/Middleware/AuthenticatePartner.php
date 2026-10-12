<?php

namespace App\Http\Middleware;

use App\Entities\Partner;
use Closure;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticatePartner
{
    public function __construct(private EntityManagerInterface $em) {}

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();
        $partner = $token === null
            ? null
            : $this->em->getRepository(Partner::class)->findOneBy(['apiTokenHash' => hash('sha256', $token)]);

        if ($partner === null) {
            throw new AuthenticationException();
        }

        $request->attributes->set('partner', $partner);

        return $next($request);
    }
}
