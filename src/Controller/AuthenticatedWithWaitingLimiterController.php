<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

class AuthenticatedWithWaitingLimiterController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $authenticatedLimiter
    ) {}

    #[Route('/authenticated/wait', name: 'app_authenticated_limiter_wait')]
    public function __invoke(Request $request): Response
    {
        if (!$request->headers->has('application-key')) {
            throw new BadRequestHttpException('"application_key" is missing.');
        }

        $applicationKey = $request->headers->get('application_key');

        $limiter = $this->authenticatedLimiter->create($applicationKey);

        $reservation = $limiter->reserve(7);
        $reservation->wait();

        $rateLimit = $reservation->getRateLimit();

        return new JsonResponse([
            'status' => 'ok',
            'remaining_tokens' => $rateLimit->getRemainingTokens(),
            'max_tokens' => $rateLimit->getLimit(),
        ]);
    }
}
