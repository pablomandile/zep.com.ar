<?php

namespace Tests\Feature;

use App\Models\Link;
use App\Services\ShortCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShortCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_codes_with_the_minimum_length_and_the_configured_alphabet()
    {
        $code = app(ShortCodeGenerator::class)->generate();

        $this->assertSame(config('shortener.min_length'), strlen($code));
        $this->assertMatchesRegularExpression('/^['.preg_quote(config('shortener.alphabet'), '/').']+$/', $code);
    }

    public function test_grows_the_length_when_the_shortest_space_is_full()
    {
        // Con un alfabeto de una letra, solo existe un código de largo 1.
        config(['shortener.alphabet' => 'a', 'shortener.min_length' => 1]);
        Link::factory()->create(['code' => 'a']);

        $this->assertSame('aa', app(ShortCodeGenerator::class)->generate());
    }

    public function test_reserved_words_and_app_routes_are_not_available()
    {
        $generator = app(ShortCodeGenerator::class);

        $this->assertTrue($generator->isReserved('admin'));
        $this->assertTrue($generator->isReserved('LOGIN'));
        $this->assertTrue($generator->isReserved('dashboard'));
        $this->assertTrue($generator->isReserved('pendiente'));
        $this->assertFalse($generator->isReserved('promo'));
    }

    public function test_codes_of_deleted_links_are_not_reused()
    {
        $link = Link::factory()->create(['code' => 'viejo']);
        $link->delete();

        $this->assertFalse(app(ShortCodeGenerator::class)->isAvailable('viejo'));
    }

    public function test_codes_are_case_sensitive()
    {
        Link::factory()->create(['code' => 'abc', 'original_url' => 'https://uno.com']);
        Link::factory()->create(['code' => 'ABC', 'original_url' => 'https://dos.com']);

        $this->get('/abc')->assertRedirect('https://uno.com');
        $this->get('/ABC')->assertRedirect('https://dos.com');
    }
}
