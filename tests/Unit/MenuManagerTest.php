<?php

namespace Tests\Unit;

use Polirium\Core\Base\Menu\MenuManager;
use Tests\TestCase;

class MenuManagerTest extends TestCase
{
    public function test_it_builds_the_nested_menu_api_used_by_polirium(): void
    {
        $manager = new MenuManager();

        $menu = $manager->make('test', function ($builder): void {
            $parent = $builder->add('Parent', [
                'id' => 'parent',
                'url' => 'javascript:void(0);',
                'icon' => 'home',
            ]);
            $parent->data('permission', 'dashboard.view');
            $parent->add('Child', [
                'id' => 'child',
                'url' => '/child',
            ]);
        });

        $parent = $menu->find('parent');

        $this->assertSame($menu, $manager->get('test'));
        $this->assertNotNull($parent);
        $this->assertSame('dashboard.view', $parent->data('permission'));
        $this->assertSame('home', $parent->attributes['icon']);
        $this->assertTrue($parent->hasChildren());
        $this->assertSame('child', $parent->children()->first()->id);
        $this->assertCount(1, $menu->roots());
    }
}
