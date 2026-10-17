<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Article management must work end to end and never touch rows it should not.
 * Runs against an in-memory sqlite database, so real data is never involved.
 */
class ArticleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function category(): string
    {
        $id = 'cat_' . uniqid();
        DB::table('categories')->insert([
            'id' => $id, 'name' => 'سیاسی', 'slug' => 'siyasi-' . uniqid(),
            'icon' => null, 'color' => null, 'order' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        return $id;
    }

    public function test_guest_is_redirected_away_from_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_non_admin_user_gets_403(): void
    {
        $user = User::factory()->create();          // role = USER
        $this->actingAs($user)->get('/admin/dashboard')->assertForbidden();
    }

    public function test_inactive_admin_is_logged_out(): void
    {
        $admin = User::factory()->admin()->inactive()->create();
        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertRedirect('/login');
    }

    public function test_admin_can_open_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_admin_can_create_article_with_author_attached(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        $response = $this->actingAs($admin)->post('/admin/articles', [
            'title'      => 'خبر آزمایشی',
            'content'    => 'متن کامل خبر',
            'excerpt'    => 'چکیده',
            'categoryId' => $categoryId,
        ]);

        $response->assertRedirect();

        $article = DB::table('articles')->where('title', 'خبر آزمایشی')->first();

        $this->assertNotNull($article, 'article row was not inserted');
        $this->assertSame($admin->id, $article->authorId, 'authorId must be the logged-in admin');
    }

    public function test_two_articles_get_distinct_ids(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        foreach (['خبر یکم', 'خبر دوم', 'خبر سوم'] as $title) {
            $this->actingAs($admin)->post('/admin/articles', [
                'title' => $title, 'content' => 'متن', 'categoryId' => $categoryId,
            ])->assertRedirect();
        }

        $ids = DB::table('articles')->pluck('id')->all();
        $this->assertCount(3, $ids);
        $this->assertCount(3, array_unique($ids), 'ids must be unique');
    }

    public function test_persian_title_produces_usable_slug(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        $this->actingAs($admin)->post('/admin/articles', [
            'title' => 'شهردار ساری برکنار شد', 'content' => 'متن', 'categoryId' => $categoryId,
        ])->assertRedirect();

        $slug = DB::table('articles')->where('title', 'شهردار ساری برکنار شد')->value('slug');

        $this->assertNotSame('', $slug, 'slug must not be empty for Persian titles');
        $this->assertNotNull($slug);
        $this->assertSame($slug, trim((string) $slug, '-'), 'slug must not be only dashes');
    }

    public function test_duplicate_titles_get_distinct_slugs(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        foreach (range(1, 2) as $i) {
            $this->actingAs($admin)->post('/admin/articles', [
                'title' => 'عنوان یکسان', 'content' => 'متن', 'categoryId' => $categoryId,
            ])->assertRedirect();
        }

        $slugs = DB::table('articles')->pluck('slug')->all();
        $this->assertCount(2, array_unique($slugs));
    }

    public function test_admin_can_update_article(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        $this->actingAs($admin)->post('/admin/articles', [
            'title' => 'قبل از ویرایش', 'content' => 'متن', 'categoryId' => $categoryId,
        ])->assertRedirect();

        $id = DB::table('articles')->where('title', 'قبل از ویرایش')->value('id');

        $this->actingAs($admin)->put("/admin/articles/{$id}", [
            'title' => 'بعد از ویرایش', 'content' => 'متن تازه', 'categoryId' => $categoryId,
        ])->assertRedirect();

        $this->assertSame('بعد از ویرایش', DB::table('articles')->where('id', $id)->value('title'));
    }

    public function test_updating_missing_article_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)
            ->put('/admin/articles/nope', ['title' => 'x', 'content' => 'y', 'categoryId' => 'z'])
            ->assertNotFound();
    }

    public function test_admin_can_delete_article(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        $this->actingAs($admin)->post('/admin/articles', [
            'title' => 'برای حذف', 'content' => 'متن', 'categoryId' => $categoryId,
        ])->assertRedirect();

        $id = DB::table('articles')->where('title', 'برای حذف')->value('id');

        $this->actingAs($admin)->delete("/admin/articles/{$id}")->assertRedirect();

        $this->assertNull(DB::table('articles')->where('id', $id)->first());
    }

    public function test_deleting_missing_article_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->delete('/admin/articles/nope')->assertNotFound();
    }

    public function test_guest_cannot_create_or_delete(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        $this->actingAs($admin)->post('/admin/articles', [
            'title' => 'برای امنیت', 'content' => 'متن', 'categoryId' => $categoryId,
        ])->assertRedirect();
        $id = DB::table('articles')->where('title', 'برای امنیت')->value('id');

        $this->post('/admin/articles', ['title' => 'تزریق', 'content' => 'x', 'categoryId' => 'y'])->assertRedirect('/login');
        $this->delete("/admin/articles/{$id}")->assertRedirect('/login');

        $this->assertNotNull(DB::table('articles')->where('id', $id)->first(), 'guest must not delete');
        $this->assertNull(DB::table('articles')->where('title', 'تزریق')->first(), 'guest must not create');
    }

    public function test_created_article_appears_on_public_homepage(): void
    {
        $admin = User::factory()->admin()->create();
        $categoryId = $this->category();

        $this->actingAs($admin)->post('/admin/articles', [
            'title' => 'خبر قابل مشاهده در صفحه اصلی', 'content' => 'متن', 'categoryId' => $categoryId,
        ])->assertRedirect();

        $this->get('/')
            ->assertOk()
            ->assertSee('خبر قابل مشاهده در صفحه اصلی');
    }
}
