<?php

declare(strict_types=1);

namespace Tests\Feature\Quote;

use App\Actions\Quote\CreateQuote;
use App\Enums\QuoteStatus;
use App\Models\Company;
use App\Models\Deal;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Smalot\PdfParser\Parser;
use Tests\TestCase;

/**
 * Load-bearing (T10): the PDF's amounts, parsed back out of the generated
 * file, must equal the quote row that produced it — the only guard against a
 * document that silently disagrees with the database it was rendered from.
 */
class QuotePdfExportTest extends TestCase
{
    use RefreshDatabase;

    private function extractText(string $pdfBytes): string
    {
        return (new Parser)->parseContent($pdfBytes)->getText();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $quote = Quote::factory()->create();

        $this->get(route('quotes.pdf', $quote))
            ->assertRedirect(route('login'));
    }

    public function test_a_user_without_the_view_ability_receives_403(): void
    {
        // QuotePolicy::view() is unconditionally true today — there is no
        // real user for whom this route is currently forbidden. Swapping in
        // a deny-everything policy exercises the authorization branch the
        // controller really has, the same way DealQuoteShortcutTest does for
        // QuotePolicy::create.
        $denyAll = new class
        {
            public function view(User $user): bool
            {
                return false;
            }
        };

        Gate::policy(Quote::class, $denyAll::class);

        $user = User::factory()->create();
        $quote = Quote::factory()->create();

        $this->actingAs($user)
            ->get(route('quotes.pdf', $quote))
            ->assertForbidden();
    }

    public function test_the_response_has_the_correct_content_type_and_filename(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create(['number' => 'Q-2026-0042']);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString(
            'filename=quote-Q-2026-0042.pdf',
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertStringStartsWith('%PDF-', $response->streamedContent());
    }

    public function test_the_pdf_content_matches_the_stored_quote(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $quote = app(CreateQuote::class)->handle([
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => [
                ['description' => 'Consulting hours', 'quantity' => 4, 'unit_price' => '125.00'],
                ['description' => 'Design review', 'quantity' => 1, 'unit_price' => '340.50'],
            ],
        ], $user);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));
        $response->assertOk();

        $text = $this->extractText($response->streamedContent());

        $fresh = $quote->fresh();

        $this->assertStringContainsString($fresh->number, $text);
        $this->assertStringContainsString('Consulting hours', $text);
        $this->assertStringContainsString('Design review', $text);

        foreach ($fresh->items as $item) {
            $this->assertStringContainsString($item->unit_price_minor->toDecimalString(), $text);
            $this->assertStringContainsString($item->line_total_minor->toDecimalString(), $text);
        }

        $this->assertStringContainsString($fresh->subtotal_minor->toDecimalString(), $text);
        $this->assertStringContainsString($fresh->tax_minor->toDecimalString(), $text);
        $this->assertStringContainsString($fresh->total_minor->toDecimalString(), $text);
    }

    public function test_croatian_diacritics_round_trip_through_the_pdf_intact(): void
    {
        $user = User::factory()->create();
        $quote = Quote::factory()->create([
            'bill_to_company_name' => 'Đurđević i Žgombić d.o.o.',
            'bill_to_contact_name' => 'Šime Vučković',
            'bill_to_address' => 'Ulica Ćirila i Metoda 5, Čakovec',
        ]);
        QuoteItem::factory()->for($quote)->create(['description' => 'Održavanje računalne mreže']);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));
        $response->assertOk();

        $text = $this->extractText($response->streamedContent());

        $this->assertStringContainsString('Đurđević i Žgombić d.o.o.', $text);
        $this->assertStringContainsString('Šime Vučković', $text);
        $this->assertStringContainsString('Ulica Ćirila i Metoda 5, Čakovec', $text);
        $this->assertStringContainsString('Održavanje računalne mreže', $text);
    }

    public function test_renaming_the_company_after_sending_does_not_change_the_pdf(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create(['name' => 'Original Name d.o.o.']);
        $deal = Deal::factory()->for($company)->create();

        $quote = app(CreateQuote::class)->handle([
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => [
                ['description' => 'Retainer', 'quantity' => 1, 'unit_price' => '500.00'],
            ],
        ], $user);

        $quote->update(['status' => QuoteStatus::Sent]);

        $company->update(['name' => 'Renamed Company d.o.o.']);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));
        $response->assertOk();

        $text = $this->extractText($response->streamedContent());

        $this->assertStringContainsString('Original Name d.o.o.', $text);
        $this->assertStringNotContainsString('Renamed Company d.o.o.', $text);
    }

    public function test_a_fifty_item_quote_paginates_without_dropping_rows(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $items = [];
        for ($i = 0; $i < 50; $i++) {
            $items[] = ['description' => "Line item number {$i}", 'quantity' => 1, 'unit_price' => '10.00'];
        }

        $quote = app(CreateQuote::class)->handle([
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => $items,
        ], $user);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));
        $response->assertOk();

        $text = $this->extractText($response->streamedContent());

        for ($i = 0; $i < 50; $i++) {
            $this->assertStringContainsString("Line item number {$i}", $text);
        }
    }

    public function test_a_zero_item_quote_renders(): void
    {
        $user = User::factory()->create();
        $deal = Deal::factory()->create();

        $quote = app(CreateQuote::class)->handle([
            'deal_id' => $deal->id,
            'issue_date' => '2026-01-01',
            'valid_until' => '2026-01-31',
            'tax_rate' => '0.2500',
            'items' => [],
        ], $user);

        $response = $this->actingAs($user)->get(route('quotes.pdf', $quote));
        $response->assertOk();

        $text = $this->extractText($response->streamedContent());

        $this->assertStringContainsString($quote->number, $text);
        $this->assertStringContainsString('0.00', $text);
    }
}
