<div class="main-nav">
    <!-- Sidebar Logo -->
    <div class="logo-box">
        <a href="{{ route('any', 'home') }}" class="logo-dark">
            <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm"/>
            <img src="/images/logo-dark.png" class="logo-lg" alt="logo dark"/>
        </a>

        <a href="{{ route('any', 'home') }}" class="logo-light">
            <img src="/images/logo-sm.png" class="logo-sm" alt="logo sm"/>
            <img src="/images/logo-light.png" class="logo-lg" alt="logo light"/>
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
                <a class="nav-link menu-arrow" href="#sidebarDashboards" data-bs-toggle="collapse" role="button"
                   aria-expanded="false" aria-controls="sidebarDashboards">
                    <span class="nav-icon">
                        <iconify-icon icon="iconamoon:home-duotone"></iconify-icon>
                    </span>
                    <span class="nav-text"> Dashboards </span>
                </a>
                <div class="collapse" id="sidebarDashboards">
                    <ul class="nav sub-navbar-nav">
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('second', [ 'dashboards' , 'analytics']) }}">Analytics</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('second', ['dashboards', 'finance'])}}">Finance</a>
                        </li>
                        <li class="sub-nav-item">
                            <a class="sub-nav-link" href="{{ route('second', ['dashboards', 'sales'])}}">Sales</a>
                        </li>
                    </ul>
                </div>
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
                            <a class="sub-nav-link" href="{{ route('app-users.index') }}">Users List</a>
                        </li>

                    </ul>
                </div>
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
