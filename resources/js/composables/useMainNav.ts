import { usePage } from '@inertiajs/vue3';
import { Link2, List, ShieldCheck, Users } from '@lucide/vue';
import { computed } from 'vue';
import { index as adminLinks } from '@/routes/admin/links';
import { index as adminUsers } from '@/routes/admin/users';
import { dashboard } from '@/routes';
import { index as linksIndex } from '@/routes/links';
import type { NavItem } from '@/types';

/**
 * Ítems del menú principal, compartidos por la barra lateral y el header.
 * Los de administración solo aparecen para el admin.
 */
export function useMainNav() {
    const page = usePage();

    return computed<NavItem[]>(() => {
        const items: NavItem[] = [
            { title: 'Acortar', href: dashboard(), icon: Link2 },
            { title: 'Mis enlaces', href: linksIndex(), icon: List },
        ];

        if (page.props.auth.user?.is_admin) {
            items.push(
                { title: 'Usuarios', href: adminUsers(), icon: Users },
                {
                    title: 'Todos los links',
                    href: adminLinks(),
                    icon: ShieldCheck,
                },
            );
        }

        return items;
    });
}
