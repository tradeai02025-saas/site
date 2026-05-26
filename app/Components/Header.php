<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Header extends Component
{
    public $title;
    public $showNotifications;
    public $notificationCount;
    public $userInitials;
    public $userName;

    public function __construct(
        $title = null,
        $showNotifications = true,
        $notificationCount = 0,
        $userInitials = 'U',
        $userName = 'Usuário'
    ) {
        $this->title = $title;
        $this->showNotifications = $showNotifications;
        $this->notificationCount = $notificationCount;
        $this->userInitials = $userInitials;
        $this->userName = $userName;
    }

    public function render()
    {
        return view('components.header');
    }
}