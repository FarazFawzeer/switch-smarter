{{-- Single-line SVG icon markup, safe to embed inside a JavaScript template string --}}
{!! trim(preg_replace('/\s+/', ' ', view('components.ui-icon', ['name' => $name, 'size' => $size ?? 20, 'stroke' => $stroke ?? 1.8, 'attributes' => new \Illuminate\View\ComponentAttributeBag()])->render())) !!}
