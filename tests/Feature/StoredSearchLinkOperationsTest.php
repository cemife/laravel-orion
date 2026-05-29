<?php

namespace Orion\Tests\Feature;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Orion\Tests\Fixtures\App\Models\Post;
use Orion\Tests\Fixtures\App\Policies\GreenPolicy;

class StoredSearchLinkOperationsTest extends TestCase
{
    /** @test */
    public function test_storing_and_fetching_a_search_by_sid(): void
    {
        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $linkResponse = $this->post('/api/v1/posts/search-links', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match'],
            ],
            'sort' => [
                ['field' => 'title', 'direction' => 'asc'],
            ],
        ]);

        $linkResponse->assertStatus(201);
        $linkResponse->assertJsonStructure(['id', 'url']);
        $this->assertStringStartsWith('srch_', $linkResponse->json('id'));
        $this->assertSame('/api/v1/posts/search?sid='.$linkResponse->json('id'), $linkResponse->json('url'));

        $searchResponse = $this->get($linkResponse->json('url'));

        $this->assertResourcesPaginated(
            $searchResponse,
            $this->makePaginator([$matchingPost], 'v1/posts/search'),
            [],
            false
        );
    }

    /** @test */
    public function test_storing_and_fetching_a_search_by_sid_with_database_driver(): void
    {
        config([
            'orion.search_links.driver' => 'database',
            'orion.search_links.database.table' => 'orion_search_links_test',
        ]);

        Schema::create('orion_search_links_test', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->json('payload');
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        $matchingPost = factory(Post::class)->create(['title' => 'match'])->fresh();
        factory(Post::class)->create(['title' => 'different'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $linkResponse = $this->post('/api/posts/search-links', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match'],
            ],
        ]);

        $linkResponse->assertStatus(201);
        $this->assertDatabaseHas('orion_search_links_test', ['id' => $linkResponse->json('id')]);

        $searchResponse = $this->get($linkResponse->json('url'));

        $this->assertResourcesPaginated(
            $searchResponse,
            $this->makePaginator([$matchingPost], 'posts/search'),
            [],
            false
        );
    }

    /** @test */
    public function test_stored_search_links_are_scoped_to_the_registered_route_context(): void
    {
        factory(Post::class)->create(['title' => 'match'])->fresh();

        Gate::policy(Post::class, GreenPolicy::class);

        $linkResponse = $this->post('/api/v1/posts/search-links', [
            'filters' => [
                ['field' => 'title', 'operator' => '=', 'value' => 'match'],
            ],
        ]);

        $this->get('/api/posts/search?sid='.$linkResponse->json('id'))->assertStatus(404);
    }

    /** @test */
    public function test_fetching_a_search_without_sid_is_rejected(): void
    {
        $this->get('/api/posts/search')
            ->assertStatus(422)
            ->assertJsonStructure(['message', 'errors' => ['sid']]);
    }

    /** @test */
    public function test_fetching_a_search_with_unknown_sid_is_rejected(): void
    {
        $this->get('/api/posts/search?sid=srch_missing')->assertStatus(404);
    }

    /** @test */
    public function test_storing_a_search_link_validates_the_payload(): void
    {
        Gate::policy(Post::class, GreenPolicy::class);

        $response = $this->post('/api/posts/search-links', [
            'filters' => [
                ['field' => 'body', 'operator' => '=', 'value' => 'match'],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors' => ['filters.0.field']]);
    }
}
