<?php

use Brain\Monkey;
use Brain\Monkey\Functions;
use Engage\Managers\Globals;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Run without WordPress/database bootstrap:
 * php vendor/bin/phpunit --no-configuration --bootstrap vendor/autoload.php tests/FilterMenuOutputTest.php
 */
class FilterMenuOutputTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\when('get_transient')->justReturn(false);
        Functions\when('set_transient')->justReturn(true);
        Functions\when('get_site_url')->justReturn('https://example.org');
        Functions\when('get_field')->justReturn(false);
        Functions\when('is_tax')->justReturn(false);
        Functions\when('get_term_by')->justReturn(false);
        Functions\when('is_wp_error')->justReturn(false);
        Functions\when('get_option')->justReturn([]);
        Functions\when('update_option')->justReturn(true);
        Mockery::mock('alias:Timber')->shouldReceive('get_posts')->andReturn([]);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public static function menus(): array
    {
        return [
            'team' => ['getTeamMenu', 'team_category', 'team', 'staff'],
            'research' => ['getResearchMenu', 'research-categories', 'research', 'journalism'],
            'announcement' => ['getAnnouncementMenu', 'announcement-category', 'announcement', 'news'],
            'blogs' => ['getBlogMenu', 'blogs-category', 'blogs', 'updates'],
        ];
    }

    /** @dataProvider menus */
    public function testMenusRenderOnlyTheirCategoryLinks($getter, $taxonomy, $postType, $slug): void
    {
        $term = (object) [
            'term_id' => 42,
            'slug' => $slug,
            'name' => 'Example category',
            'taxonomy' => $taxonomy,
            'description' => '',
            'count' => 1,
        ];
        Functions\expect('get_terms')->once()->with([
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
        ])->andReturn([$term]);

        $filters = (new Globals())->$getter();
        $this->assertSame([$slug], array_keys($filters['terms']));
        $this->assertSame($taxonomy, $filters['terms'][$slug]['taxonomy']);
        $link = "https://example.org/{$postType}/category/{$slug}";
        $this->assertSame($link, $filters['terms'][$slug]['link']);

        $twig = new Environment(new FilesystemLoader(dirname(__DIR__) . '/templates'));
        $html = $twig->render('filters.twig', [
            'filters' => $filters,
            'current_term' => $slug,
            'options' => ['sidebar_settings' => []],
        ]);

        // Check the actual HTML: no post-type parent, nested links, or unrelated team items.
        $this->assertSame(1, substr_count($html, '<li'));
        $this->assertSame(1, substr_count($html, '<a '));
        $this->assertStringContainsString('href="' . $link . '"', $html);
        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringNotContainsString('filter__sublist', $html);
        $this->assertStringNotContainsString('filter__item--team ', $html);
    }
}
