<?php

namespace App\View\Components\Element;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Sidebar extends Component
{
    public  $menuItems;
    public $currentNav;

    public $currentRoute;
    public function __construct()
    {
        list($prefix, $currentNav) = explode('/', request()->path());

        $this->currentRoute = request()->route()->action??['as'];

        $this->currentNav = $currentNav;

        $this->menuItems = $this->menuItems();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.element.sidebar');
    }

    private function menuItems(): array
    {
        return [
            [
                'icon' => 'tabler-users',
                'label' => 'Users',
                'route_group' => 'users',
                'has_children' => true,
                'children' => [
                    [
                        'route' => 'admin.users',
                        'label' => 'Users List',
                        'route_group' => 'users',
                    ],
                    [
                        'route' => 'admin.users.create',
                        'label' => 'Create New Account',
                        'route_group' => 'users',
                    ],
                    [
                        'route' => 'admin.users.merge-accounts',
                        'label' => 'Merge Accounts',
                        'route_group' => 'users',
                    ],
                ],
            ],
            [
                'route' => 'admin.events',
                'icon' => 'tabler-app-window',
                'label' => 'Events',
                'route_group' => 'events',
                'has_children' => false,
            ],
            [
                'route' => 'admin.email.builders',
                'icon' => 'tabler-app-window',
                'label' => 'Email Templates',
                'route_group' => 'email-builders',
                'has_children' => false,
            ],
        ];
    }
}
