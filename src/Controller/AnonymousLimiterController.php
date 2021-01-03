<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

class AnonymousLimiterController
{
    public function __construct(
        private RateLimiterFactory $anonymousLimiter
    ) {}

    #[Route('/anonymous', name: 'app_anonymous_limiter')]
    public function __invoke(Request $request): Response
    {
        $limiter = $this->anonymousLimiter->create($request->getClientIp());

        $rateLimit = $limiter->consume();

        $headers = [
            'X-RateLimit-Remaining' => $rateLimit->getRemainingTokens(),
            'X-RateLimit-Retry-After' => $rateLimit->getRetryAfter()->getTimestamp(),
            'X-RateLimit-Limit' => $rateLimit->getLimit(),
        ];

        if (!$rateLimit->isAccepted()) { // Or use "ensureAccepted()" which throws a RateLimitExceededException if the limit has been reached
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Too many requests.',
                'retry_after' => $rateLimit->getRetryAfter(),
            ], Response::HTTP_TOO_MANY_REQUESTS, $headers);
        }

        return new JsonResponse(
            [
                'status' => 'ok',
                'remaining_tokens' => $rateLimit->getRemainingTokens(),
                'max_tokens' => $rateLimit->getLimit(),
            ],
            headers: $headers
        );
    }
}
