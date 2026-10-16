<?php

namespace Tests\Unit;

use Livewire\Component;
use Polirium\Core\Base\Extensions\Finder;
use Polirium\Core\Base\Http\Livewire\ScriptAction\UIScriptActionComponent;
use Tests\TestCase;

class LivewireFinderTest extends TestCase
{
    public function test_it_resolves_custom_components_after_livewire_normalizes_slash_aliases(): void
    {
        $finder = new Finder();
        $finder->setCustomComponents([
            [
                'alias' => 'core/ui::script-action-ui.script',
                'class' => TestScriptActionComponent::class,
            ],
        ]);

        $normalizedAlias = $finder->normalizeName('core/ui::script-action-ui.script');

        $this->assertSame('core.ui::script-action-ui.script', $normalizedAlias);
        $this->assertSame(
            TestScriptActionComponent::class,
            $finder->resolveClassComponentClassName($normalizedAlias),
        );
    }

    public function test_application_livewire_factory_resolves_registered_slash_aliases(): void
    {
        $livewire = app('livewire');

        $this->assertTrue($livewire->exists('core/ui::script-action-ui.script'));
        $this->assertInstanceOf(
            UIScriptActionComponent::class,
            $livewire->new('core/ui::script-action-ui.script'),
        );
    }

    public function test_application_resolves_dynamically_registered_slash_aliases(): void
    {
        $livewire = app('livewire');
        $alias = 'core/test::widgets.dynamic';

        $livewire->component($alias, TestDynamicWidgetComponent::class);

        $this->assertTrue($livewire->exists($alias));
        $this->assertInstanceOf(TestDynamicWidgetComponent::class, $livewire->new($alias));
    }

    public function test_single_class_argument_registration_keeps_livewire_behavior(): void
    {
        $livewire = app('livewire');

        $livewire->component(TestSingleClassComponent::class);

        $this->assertTrue($livewire->exists(TestSingleClassComponent::class));
        $this->assertInstanceOf(
            TestSingleClassComponent::class,
            $livewire->new(TestSingleClassComponent::class),
        );
    }
}

class TestScriptActionComponent extends Component
{
}

class TestDynamicWidgetComponent extends Component
{
}

class TestSingleClassComponent extends Component
{
}
