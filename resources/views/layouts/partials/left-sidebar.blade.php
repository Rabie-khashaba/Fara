<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="{{ route('any', 'home') }}" class="logo-dark">
            <img src="/images/Logo-white.svg" class="logo-sm" alt="Wander logo small"/>
            <span class="logo-lg text-dark fs-3 fw-bold d-inline-flex align-items-center">Wander</span>
        </a>

        <a href="{{ route('any', 'home') }}" class="logo-light">
            <img src="/images/Logo-black.svg" class="logo-sm" alt="Wander logo small"/>
            <span class="logo-lg text-white fs-3 fw-bold d-inline-flex align-items-center">Wander</span>
        </a>
    </div>

    <!-- Menu Toggle Button (sm-hover) -->
    <button type="button" class="button-sm-hover" aria-label="Show Full Sidebar">
        <iconify-icon icon="iconamoon:arrow-left-4-square-duotone" class="button-sm-hover-icon"></iconify-icon>
    </button>

    <div class="scrollbar" data-simplebar>
        <ul class="navbar-nav" id="navbar-nav">
            <li class="menu-title">General</li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('home') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboard </span>
                </a>
            </li>

            <li class="menu-title">Apps</li>



            <li class="nav-item">
                <a class="nav-link menu-arrow" href="#sidebarAppUsers" data-bs-toggle="collapse" role="button"
                    aria-expanded="false" aria-controls="sidebarAppUsers">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:profile-circle-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> App Users </span>
                </a>
                <div class="collapse" id="sidebarAppUsers">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('app-users.index', ['status' => 'active']) }}">Users</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('app-users.index', ['status' => 'inactive']) }}">Blocked Users</a>
                        </li>

                    </ul>
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('notifications.index') }}">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:notification-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Notifications </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('places.index') }}">
                    <span class="nav-icon">
                        <i class="bx bx-map-alt"></i>
                    </span>
                    <span class="nav-text"> Places Management </span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="{{ route('reports.index') }}">
                    <span class="nav-icon">
                        <i class="bx bx-bar-chart-alt-2"></i>
                    </span>
                    <span class="nav-text"> Reports </span>
                </a>
            </li>

            <li class="menu-title">Custom</li>

            @can('view_settings')
                <li class="nav-item">
                    <a class="nav-link menu-arrow" href="#sidebarSettings" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarSettings">
                        <span class="nav-icon">
                            <iconify-icon icon="iconamoon:settings-duotone"></iconify-icon>
                        </span>
                        <span class="nav-text"> Settings </span>
                    </a>
                    <div class="collapse" id="sidebarSettings">
                        <ul class="nav sub-navbar-nav">
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('users.index') }}">Dashboard Users</a>
                            </li>
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('settings.roles.index') }}">Roles</a>
                            </li>
                            <li class="sub-nav-item">
                                <a class="sub-nav-link" href="{{ route('settings.permissions.index') }}">Permissions</a>
                            </li>
                        </ul>
                    </div>
                </li>
            @endcan


            <!-- end Demo Menu Item -->
        </ul>
    </div>
</div>
