<?php

namespace Devdojo\Foundation\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class Setup extends Component
{
    /**
     * The ordered wizard steps shown in the sidebar.
     *
     * @var array<int, string>
     */
    public array $steps = ['Starter', 'Packages', 'Project', 'Database', 'Review', 'Launch'];

    /**
     * The starter templates a developer can choose from.
     *
     * @var array<int, array{key: string, name: string, description: string}>
     */
    public array $templates = [
        ['key' => 'blank', 'name' => 'Blank', 'description' => 'A clean foundation with everything you need to build from scratch.'],
        ['key' => 'relay', 'name' => 'Relay', 'description' => 'The todo-list & project management app for teams that ship.'],
        ['key' => 'deskly', 'name' => 'Deskly', 'description' => 'Customer support without the chaos. Inbox, tickets, and knowledge base.'],
        ['key' => 'formly', 'name' => 'Formly', 'description' => 'Forms that feel easy, results that matter. Create beautiful forms in minutes.'],
        ['key' => 'hunted', 'name' => 'Hunted', 'description' => 'Discover and launch products your audience will love.'],
        ['key' => 'catalog', 'name' => 'Catalog', 'description' => 'A modern storefront for digital and physical products.'],
    ];

    /**
     * The template selected by default.
     */
    public string $defaultTemplate = 'blank';

    public function render(): View
    {
        return view('foundation::livewire.setup');
    }
}
