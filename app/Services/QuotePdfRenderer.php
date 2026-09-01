<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Quote;
use Dompdf\Dompdf;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Renders a quote to a PDF byte string. Nothing here resolves a value live —
 * every amount, date and customer-block field comes off the `Quote` row it
 * is handed, which is itself already a snapshot (see `Quote::booted()`).
 *
 *   Quote (with `items` eager-loaded by the caller)
 *        │
 *        ▼  registerDejaVuSans() — idempotent; embeds resources/fonts/
 *           dejavu-sans/*.ttf into dompdf's font cache the first time this
 *           runs on a given `storage/fonts/` and is then a no-op lookup
 *        │
 *        ▼  resources/views/pdf/quote.blade.php — CSS 2.1 only, no
 *           arithmetic, every amount already a formatted Money string
 *   HTML string
 *        │
 *        ▼  Dompdf::loadHtml() + render(), memory_limit raised for the
 *           duration (dompdf holds the whole render tree in memory; a
 *           50-item quote's table comfortably exceeds the default 128M)
 *   PDF bytes
 */
final class QuotePdfRenderer
{
    private const string FONT_FAMILY = 'DejaVu Sans';

    private const string RENDER_MEMORY_LIMIT = '256M';

    public function __construct(
        private readonly Dompdf $dompdf,
        private readonly ViewFactory $view,
    ) {}

    public function render(Quote $quote): string
    {
        $this->registerDejaVuSans();

        $previousLimit = ini_set('memory_limit', self::RENDER_MEMORY_LIMIT);

        try {
            $html = $this->view->make('pdf.quote', ['quote' => $quote])->render();

            $this->dompdf->loadHtml($html);
            $this->dompdf->setPaper('a4', 'portrait');
            $this->dompdf->render();

            return (string) $this->dompdf->output();
        } finally {
            if ($previousLimit !== false) {
                ini_set('memory_limit', $previousLimit);
            }
        }
    }

    /**
     * Registers the four DejaVu Sans variants embedded under
     * `resources/fonts/dejavu-sans/`, guarded by a lookup so a second render
     * — in this request or any later one sharing the same `storage/fonts/`
     * cache — never re-copies them. Done before the template is ever loaded,
     * since dompdf resolves `font-family` at HTML-parse time.
     */
    private function registerDejaVuSans(): void
    {
        $fontMetrics = $this->dompdf->getFontMetrics();

        if ($fontMetrics->getFont(self::FONT_FAMILY, 'normal') !== null) {
            return;
        }

        $variants = [
            ['weight' => 'normal', 'style' => 'normal', 'file' => 'DejaVuSans.ttf'],
            ['weight' => 'bold', 'style' => 'normal', 'file' => 'DejaVuSans-Bold.ttf'],
            ['weight' => 'normal', 'style' => 'italic', 'file' => 'DejaVuSans-Oblique.ttf'],
            ['weight' => 'bold', 'style' => 'italic', 'file' => 'DejaVuSans-BoldOblique.ttf'],
        ];

        foreach ($variants as $variant) {
            $fontMetrics->registerFont(
                ['family' => self::FONT_FAMILY, 'weight' => $variant['weight'], 'style' => $variant['style']],
                resource_path('fonts/dejavu-sans/'.$variant['file']),
            );
        }
    }
}
