<?php

namespace Tests\Feature;

use App\Http\Middleware\AcceptDbConnParam;
use App\Http\Middleware\EnsureClientConnection;
use App\Models\ClaimConfirmation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClaimConfirmationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_the_claim_confirmations_page(): void
    {
        ClaimConfirmation::query()->create([
            'claim' => 3790558,
            'changestamp' => 2505409404,
            'status' => 'CONFIRMED',
            'flag' => '0',
            'comment' => 'OUR REF: I028BQK',
            'cost' => 0,
        ]);

        $response = $this
            ->withoutMiddleware([AcceptDbConnParam::class, EnsureClientConnection::class])
            ->actingAs(User::factory()->make())
            ->get(route('claim-confirmations.index'));

        $response->assertOk();
        $response->assertSee('Listado de confirmaciones SAMO');
        $response->assertSee('3790558');
        $response->assertSee('2505409404');
        $response->assertSee('OUR REF: I028BQK');
    }

    public function test_sync_route_fetches_new_confirmations_and_redirects_back_with_a_success_message(): void
    {
        config()->set('services.samo.base_url', 'http://samo.test/service');
        config()->set('services.samo.username', 'demo_user');
        config()->set('services.samo.password', 'demo_pass');
        config()->set('services.samo.claim_number', 1510263);

        Http::fakeSequence()
            ->push($this->xmlResponse(
                '{CM|3790558|CONFIRMED|0|OUR REF: I028BQK|0.0000;}' .
                '{CM|3790572|CONFIRMED|0|OUR REF: I028BQL|0.0000;}',
                2505409404
            ), 200)
            ->push($this->emptyXmlResponse(), 200);

        $response = $this
            ->withoutMiddleware([AcceptDbConnParam::class, EnsureClientConnection::class])
            ->from(route('claim-confirmations.index'))
            ->actingAs(User::factory()->make())
            ->post(route('claim-confirmations.sync'));

        $response->assertRedirect(route('claim-confirmations.index'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('claim_confirmations', [
            'claim' => 3790558,
            'changestamp' => 2505409404,
        ]);

        $this->assertDatabaseHas('claim_confirmations', [
            'claim' => 3790572,
            'changestamp' => 2505409404,
        ]);
    }

    public function test_authenticated_users_can_export_the_latest_claim_confirmations_to_csv(): void
    {
        ClaimConfirmation::query()->create([
            'claim' => 1001,
            'changestamp' => 10,
            'status' => 'PENDING',
            'flag' => '0',
            'comment' => 'older row',
            'cost' => 1.25,
        ]);

        ClaimConfirmation::query()->create([
            'claim' => 1002,
            'changestamp' => 20,
            'status' => 'CONFIRMED',
            'flag' => '1',
            'comment' => 'newest row',
            'cost' => 2.50,
        ]);

        ClaimConfirmation::query()->create([
            'claim' => 1000,
            'changestamp' => 20,
            'status' => 'CONFIRMED',
            'flag' => '0',
            'comment' => 'same changestamp lower claim',
            'cost' => 3.75,
        ]);

        $response = $this
            ->withoutMiddleware([AcceptDbConnParam::class, EnsureClientConnection::class])
            ->actingAs(User::factory()->make())
            ->get(route('claim-confirmations.export', ['limit' => 2]));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString(
            "id,claim,changestamp,status,flag,comment,cost,created_at,updated_at",
            $csv
        );
        $this->assertStringContainsString('1002,20,CONFIRMED,1,"newest row",2.5000', $csv);
        $this->assertStringContainsString('1000,20,CONFIRMED,0,"same changestamp lower claim",3.7500', $csv);
        $this->assertStringNotContainsString('1001,10,PENDING,0,"older row",1.2500', $csv);
    }

    private function xmlResponse(string $payload, int $newChangestamp): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <response>
    <data new_changestamp="{$newChangestamp}">{$payload}</data>
  </response>
</root>
XML;
    }

    private function emptyXmlResponse(): string
    {
        return <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<root>
  <response></response>
</root>
XML;
    }
}
