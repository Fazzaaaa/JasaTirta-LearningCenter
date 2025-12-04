<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Admin Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
    
    <style>
        /* Prevent initial flash */
        body { visibility: hidden; }
        body.loaded { visibility: visible; }
    </style>
    
    <script>
        // Apply saved sidebar state immediately to prevent layout shift
        (function() {
            const savedState = localStorage.getItem('desktopSidebarOpen');
            if (savedState === 'false') {
                document.documentElement.style.setProperty('--sidebar-width', '5rem');
            }
        })();
        
        // Show body once loaded
        window.addEventListener('DOMContentLoaded', () => {
            document.body.classList.add('loaded');
        });
    </script>
</head>
<body class="antialiased bg-gray-50" 
    x-data="{ 
        sidebarOpen: false, 
        desktopSidebarOpen: localStorage.getItem('desktopSidebarOpen') !== 'false', 
        profileOpen: false 
    }"
    x-init="$watch('desktopSidebarOpen', value => localStorage.setItem('desktopSidebarOpen', value))"
>
    <x-loader />
    
    <div class="min-h-screen">
        {{-- Mobile Overlay --}}
        <div 
            x-show="sidebarOpen" 
            @click="sidebarOpen = false"
            x-transition:enter="transition-opacity ease-linear duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-30 bg-gray-900 bg-opacity-50 lg:hidden"
            style="display: none;"
        ></div>

        @include('layouts.admin._sidebar')
        @include('layouts.admin._navbar')
        
        {{-- Main Content --}}
        <div class="transition-all duration-300" :class="desktopSidebarOpen ? 'lg:ml-64' : 'lg:ml-20'">
            <div class="pt-24 px-4 sm:px-6 lg:px-8 py-8">
                @yield('content')
            </div>
        </div>
    </div>

    <script>
        // Auto scroll sidebar to active menu
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar-scroll');
            const activeLink = sidebar?.querySelector('.bg-blue-50');
            
            if (sidebar && activeLink) {
                // Scroll active menu into view with smooth behavior
                setTimeout(() => {
                    activeLink.scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 100);
            }
        });

        document.addEventListener('alpine:init', () => {
            Alpine.data('notificationsManager', () => ({
                notifications: [],
                unreadCount: 0,
                loading: false,

                init() {
                    this.fetchNotifications();
                },

                async fetchNotifications() {
                    this.loading = true;
                    try {
                        const response = await fetch('{{ route("admin.notifications.data") }}?limit=10');
                        const data = await response.json();
                        this.notifications = data.notifications;
                        this.unreadCount = data.unread_count;
                    } catch (error) {
                        console.error('Error fetching notifications:', error);
                    } finally {
                        this.loading = false;
                    }
                },

                async markAsRead(id) {
                    const notification = this.notifications.find(n => n.id === id);
                    if (notification && !notification.read_at) {
                        try {
                            await fetch(`/admin/notifications/${id}/read`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });
                            notification.read_at = new Date();
                            this.unreadCount = Math.max(0, this.unreadCount - 1);
                            
                            // Navigate to URL if exists
                            if (notification.url && notification.url !== '#') {
                                window.location.href = notification.url;
                            }
                        } catch (error) {
                            console.error('Error marking notification as read:', error);
                        }
                    }
                },

                async markAllAsRead() {
                    try {
                        await fetch('{{ route("admin.notifications.readAll") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        this.notifications.forEach(n => n.read_at = new Date());
                        this.unreadCount = 0;
                    } catch (error) {
                        console.error('Error marking all as read:', error);
                    }
                },

                async deleteNotification(id) {
                    try {
                        await fetch(`/admin/notifications/${id}/delete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        this.notifications = this.notifications.filter(n => n.id !== id);
                        // Recalculate unread count
                        this.unreadCount = this.notifications.filter(n => !n.read_at).length;
                    } catch (error) {
                        console.error('Error deleting notification:', error);
                    }
                },

                getNotificationIcon(type) {
                    const icons = {
                        new_training: '📚',
                        new_schedule: '📅',
                        training: '📚',
                        enrollment: '👤',
                        schedule: '📅',
                        message: '💬',
                        certificate: '🎓',
                        payment: '💰',
                        system: '⚙️'
                    };
                    return icons[type] || '🔔';
                }
            }));
        });
    </script>

    @stack('scripts')
</body>
</html>
