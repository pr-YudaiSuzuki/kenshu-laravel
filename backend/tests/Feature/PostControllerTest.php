<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

use App\Post;
use App\Tag;
use App\Image;
use App\Thumbnail;

class PostControllerTest extends TestCase
{
    use DatabaseMigrations;

    public function testSeeShowPage()
    {
        $post = $this->createValidPost();
        $response = $this->get(route('post', ['user' => $post->user, 'post' => $post]));
        $response->assertStatus(200)
                 ->assertViewIs('post')
                 ->assertSee($post->title)
                 ->assertSee($post->body)
                 ->assertSee($post->thumbnail->getUrl());

        foreach ($post->tags as $tag) {
            $response->assertSee($tag->name);
        }

        foreach ($post->images as $image) {
            $response->assertSee($image->url);
        }
    }

    public function testDontSeeShowPageIsPrivate()
    {
        $post = factory(Post::class)->states('future', 'close')->create();
        $response = $this->get(route('post', ['user' => $post->user, 'post' => $post]));
        $response->assertStatus(404);
    }

    public function testSeeShowPageIsPrivateAndOwn()
    {
        $post = factory(Post::class)->states('future', 'close')->create();
        $response = $this->actingAs($post->user)->get(route('post', ['user' => $post->user, 'post' => $post]));
        $response->assertRedirect(route('post.edit', ['user' => $post->user, 'post' => $post]));
    }

    public function testSeeEditBtnFromOwner()
    {
        $post = $this->createValidPost();
        $response = $this->actingAs($post->user)
                         ->get(route('post', ['user' => $post->user, 'post' => $post]));
        $response->assertStatus(200)
                 ->assertViewIs('post')
                 ->assertSee("編集");
    }

    public function testDontSeeEditBtn()
    {
        $post = $this->createValidPost();
        $response = $this->get(route('post', ['user' => $post->user, 'post' => $post]));
        $response->assertStatus(200)
                 ->assertViewIs('post')
                 ->assertDontSee("編集");
    }

    public function testSeeCreatePage()
    {
        $post = $this->createValidPost();
        $response = $this->actingAs($post->user)
                         ->get(route('post.create', ['user' => $post->user]));
        $response->assertStatus(200)
                 ->assertViewIs('edit');
    }

    public function testDontSeeCreatePage()
    {
        $post = $this->createValidPost();
        $response = $this->get(route('post.create', ['user' => $post->user]));
        $response->assertRedirect(route('login'));
    }

    public function testSeeEditPage()
    {
        $post = $this->createValidPost();
        $response = $this->actingAs($post->user)
                         ->get(route('post.edit', ['user' => $post->user, 'post' => $post]));
        $response->assertStatus(200)
                 ->assertViewIs('edit');
    }

    public function testDontSeeEditPage()
    {
        $post = $this->createValidPost();
        $response = $this->get(route('post.edit', ['user' => $post->user, 'post' => $post]));
        $response->assertRedirect(route('login'));
    }

    private function createValidPost()
    {
        $post = factory(Post::class)->states('open', 'past')->create(['title' => 'Test title', 'body' => 'Test body']);
        $post->thumbnail()->save(factory(Thumbnail::class)->make());
        $post->tags()->saveMany(factory(Tag::class, 3)->make());
        $post->images()->saveMany(factory(Image::class, 3)->make());
        return $post;
    }
}
