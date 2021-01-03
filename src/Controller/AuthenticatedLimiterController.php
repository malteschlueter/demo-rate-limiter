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

class AuthenticatedLimiterController extends AbstractController
{
    public function __construct(
        private RateLimiterFactory $authenticatedLimiter
    ) {}

    #[Route('/authenticated', name: 'app_authenticated_limiter')]
    public function __invoke(Request $request): Response
    {
        if (!$request->headers->has('application-key')) {
            throw new BadRequestHttpException('"application_key" is missing.');
        }

        $applicationKey = $request->headers->get('application_key');

        $limiter = $this->authenticatedLimiter->create($applicationKey);

        $requiredTokens = 1;
        if ($request->query->getBoolean('do_high_usage_stuff')) {
            $requiredTokens = 7;
        }

        $rateLimit = $limiter->consume($requiredTokens);

        if (!$rateLimit->isAccepted()) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Too many requests.',
                'retry_after' => $rateLimit->getRetryAfter(),
            ], Response::HTTP_TOO_MANY_REQUESTS);
        }

        return new JsonResponse(
            [
                'status' => 'ok',
                'remaining_tokens' => $rateLimit->getRemainingTokens(),
                'max_tokens' => $rateLimit->getLimit(),
            ]
        );
    }
}
