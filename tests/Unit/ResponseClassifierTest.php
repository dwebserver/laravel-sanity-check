<?php

declare(strict_types=1);

namespace DynamicWeb\SanityCheck\Tests\Unit;

use DynamicWeb\SanityCheck\DTOs\RouteExecutionResult;
use DynamicWeb\SanityCheck\Enums\OutcomeClassification;
use DynamicWeb\SanityCheck\Enums\RedirectTreatment;
use DynamicWeb\SanityCheck\Services\ResponseClassifier;
use DynamicWeb\SanityCheck\Tests\TestCase;

final class ResponseClassifierTest extends TestCase
{
    public function test_classifies_status_buckets(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Reported, []);

        $this->assertSame(OutcomeClassification::Success, $c->classify($this->exec(200)));
        $this->assertSame(OutcomeClassification::Redirect, $c->classify($this->exec(302)));
        $this->assertSame(OutcomeClassification::ClientError, $c->classify($this->exec(404)));
        $this->assertSame(OutcomeClassification::ServerError, $c->classify($this->exec(500)));
    }

    public function test_redirect_treatment_success_maps_to_success_outcome(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Success, []);

        $this->assertSame(OutcomeClassification::Success, $c->classify($this->exec(302)));
    }

    public function test_redirect_treatment_ignored(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Ignored, []);

        $this->assertSame(OutcomeClassification::Ignored, $c->classify($this->exec(302)));
    }

    public function test_ignored_status_codes(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Reported, [404]);

        $this->assertSame(OutcomeClassification::Ignored, $c->classify($this->exec(404)));
    }

    public function test_transport_errors_are_ignored(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Reported, []);

        $this->assertSame(OutcomeClassification::Ignored, $c->classify($this->exec(null, 'boom')));
    }

    public function test_null_status_without_error_is_ignored(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Reported, []);

        $this->assertSame(OutcomeClassification::Ignored, $c->classify($this->exec(null, null)));
    }

    public function test_non_standard_http_status_is_ignored(): void
    {
        $c = new ResponseClassifier(RedirectTreatment::Reported, []);

        $this->assertSame(OutcomeClassification::Ignored, $c->classify($this->exec(102)));
    }

    private function exec(?int $statusCode, ?string $rawError = null): RouteExecutionResult
    {
        return new RouteExecutionResult('/', $statusCode, null, $rawError);
    }
}
