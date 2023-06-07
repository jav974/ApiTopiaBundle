<?php

namespace Jav\ApiTopiaBundle\GraphQL;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\HubRegistry;

class MercureUrlGenerator
{
    public function __construct(
        private readonly HubRegistry $hubRegistry,
        private readonly ?Authorization $authorization = null,
        private readonly ?RequestStack $requestStack = null
    ) {
    }

    /**
     * @param array{hub?: string, subscribe?: string[]|string, publish?: string[]|string, additionalClaims?: array<string, mixed>, lastEventId?: string} $options The options to pass to the JWT factory
     */
    public function generate(string $topic, array $options): string
    {
        $hub = $options['hub'] ?? null;
        $url = $this->hubRegistry->getHub($hub)->getPublicUrl();
        $url .= '?topic='.rawurlencode($topic);

        if ('' !== ($options['lastEventId'] ?? '')) {
            $url .= '&Last-Event-ID='.rawurlencode($options['lastEventId']);
        }

        if (
            null === $this->authorization ||
            null === $this->requestStack ||
            (!isset($options['subscribe']) && !isset($options['publish']) && !isset($options['additionalClaims'])) ||
            /* @phpstan-ignore-next-line */
            null === $request = method_exists($this->requestStack, 'getMainRequest') ? $this->requestStack->getMainRequest() : $this->requestStack->getMasterRequest()
        ) {
            return $url;
        }

        $this->authorization->setCookie($request, $options['subscribe'] ?? [], $options['publish'] ?? [], $options['additionalClaims'] ?? [], $hub);

        return $url;
    }
}