<?php

namespace Tests\Unit;

use App\Models\ClaimConfirmation;
use App\Services\ClaimConfirmationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class ClaimConfirmationSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_syncs_claim_confirmations_and_keeps_the_last_update_for_each_claim(): void
    {
        config()->set('services.samo.base_url', 'http://samo.test/service');
        config()->set('services.samo.username', 'demo_user');
        config()->set('services.samo.password', 'demo_pass');
        config()->set('services.samo.claim_number', 1510263);

        Http::fakeSequence()
            ->push($this->xmlResponse(
                '{CM|3790558|CONFIRMED|0|OUR REF: I028BQK|0.0000;}' .
                '{CM|3790572|CONFIRMED|0|OUR REF: I028BQL|0.0000;}' .
                '{CM|3790558|CANCELLED|1|UPDATED COMMENT|99.5000;}',
                2505409404
            ), 200)
            ->push($this->emptyXmlResponse(), 200);

        $result = app(ClaimConfirmationSyncService::class)->sync();

        $this->assertSame(2505409404, $result['last_changestamp']);
        $this->assertSame(2, $result['rows_upserted']);
        $this->assertDatabaseCount('claim_confirmations', 2);

        $this->assertDatabaseHas('claim_confirmations', [
            'claim' => 3790558,
            'changestamp' => 2505409404,
            'status' => 'CANCELLED',
            'flag' => '1',
            'comment' => 'UPDATED COMMENT',
            'cost' => '99.5000',
        ]);

        $this->assertDatabaseHas('claim_confirmations', [
            'claim' => 3790572,
            'changestamp' => 2505409404,
            'status' => 'CONFIRMED',
            'flag' => '0',
            'comment' => 'OUR REF: I028BQL',
            'cost' => '0.0000',
        ]);
    }

    public function test_it_uses_the_last_changestamp_stored_in_the_database(): void
    {
        ClaimConfirmation::query()->create([
            'claim' => 1111111,
            'changestamp' => 2505409404,
            'status' => 'CONFIRMED',
            'flag' => '0',
            'comment' => 'Existing row',
            'cost' => 0,
        ]);

        config()->set('services.samo.base_url', 'http://samo.test/service');
        config()->set('services.samo.username', 'demo_user');
        config()->set('services.samo.password', 'demo_pass');
        config()->set('services.samo.claim_number', 1510263);

        Http::fake(function (Request $request) {
            $this->assertSame(2505409404, $request->data()['Last_ChangeStamp']);
            $this->assertSame(1510263, $request->data()['claim_id']);

            return Http::response($this->emptyXmlResponse(), 200);
        });

        $result = app(ClaimConfirmationSyncService::class)->sync();

        $this->assertSame(2505409404, $result['started_from']);
        $this->assertSame(0, $result['rows_upserted']);
    }

    public function test_it_throws_an_exception_when_samo_returns_invalid_xml(): void
    {
        config()->set('services.samo.base_url', 'http://samo.test/service');
        config()->set('services.samo.username', 'demo_user');
        config()->set('services.samo.password', 'demo_pass');
        config()->set('services.samo.claim_number', 1510263);

        Http::fake([
            'http://samo.test/*' => Http::response('not-xml', 200),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('La respuesta de SAMO no es un XML valido.');

        app(ClaimConfirmationSyncService::class)->sync();
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
