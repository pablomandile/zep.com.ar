<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * En Hostinger el CDN borra el Vary: X-Inertia, y una pestaña restaurada puede
 * mostrar el JSON crudo de Inertia. La respuesta XHR no se tiene que guardar;
 * el HTML sí puede (sino se pierde el back/forward cache).
 */
class InertiaCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_inertia_xhr_response_is_not_stored()
    {
        $version = (string) app(HandleInertiaRequests::class)->version(request());

        $response = $this->get('/login', ['X-Inertia' => 'true', 'X-Inertia-Version' => $version]);

        $response->assertOk();
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
    }

    public function test_the_html_document_stays_cacheable_for_bfcache()
    {
        $response = $this->get('/login');

        $this->assertStringContainsString('text/html', $response->headers->get('Content-Type'));
        $this->assertStringNotContainsString('no-store', $response->headers->get('Cache-Control'));
    }
}
