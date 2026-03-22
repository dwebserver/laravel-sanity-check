<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Services;

use DynamicWeb\SanityCheck\DTOs\RouteExecutionResult;
use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use DynamicWeb\SanityCheck\Enums\RedirectTreatment;

/**
 * Maps HTTP status codes (and transport failures) to semantic outcomes.
 */
final class ResponseClassifier
{
    /**
     * @param  list<int>  $ignoredStatusCodes
     */
    public function __construct(
        private readonly RedirectTreatment $redirectTreatment,
        private readonly array $ignoredStatusCodes,
    ) {
    }

    public function classify(RouteExecutionResult $execution): OutcomeClassification
    {
        if ($execution->rawErrorMessage !== null && $execution->rawErrorMessage !== '') {
            return OutcomeClassification::Ignored;
        }

        if ($execution->statusCode === null) {
            return OutcomeClassification::Ignored;
        }

        $status = $execution->statusCode;

        foreach ($this->ignoredStatusCodes as $code) {
            if ($status === (int) $code) {
                return OutcomeClassification::Ignored;
            }
        }

        if ($status >= 300 && $status < 400) {
            return match ($this->redirectTreatment) {
                RedirectTreatment::Success => OutcomeClassification::Success,
                RedirectTreatment::Ignored => OutcomeClassification::Ignored,
                RedirectTreatment::Reported => OutcomeClassification::Redirect,
            };
        }

        if ($status >= 200 && $status < 300) {
            return OutcomeClassification::Success;
        }

        if ($status >= 400 && $status < 500) {
            return OutcomeClassification::ClientError;
        }

        if ($status >= 500) {
            return OutcomeClassification::ServerError;
        }

        return OutcomeClassification::Ignored;
    }

    public function describeIgnoredReason(
        RouteExecutionResult $execution,
        OutcomeClassification $classification,
    ): ?string {
        if ($execution->rawErrorMessage !== null && $execution->rawErrorMessage !== '') {
            return 'transport_error';
        }

        if ($classification !== OutcomeClassification::Ignored) {
            return null;
        }

        $status = $execution->statusCode;
        if ($status !== null && $status >= 300 && $status < 400 && $this->redirectTreatment === RedirectTreatment::Ignored) {
            return 'redirect';
        }

        if ($status !== null) {
            foreach ($this->ignoredStatusCodes as $code) {
                if ($status === (int) $code) {
                    return 'ignored_status_code';
                }
            }

            if ($status < 100 || $status > 599) {
                return 'non_standard_status';
            }
        }

        return null;
    }
}
