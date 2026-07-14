import { router } from '@inertiajs/react';

/**
 * LogoutButton
 *
 * PENTING: JANGAN membuat <form> manual dengan token dari
 * <meta name="csrf-token">. Di Inertia, meta itu hanya di-render sekali saat
 * halaman pertama dimuat dan TIDAK pernah diperbarui. Begitu sesi kedaluwarsa
 * atau di-regenerate (mis. login di tab lain), token di meta jadi basi → 419.
 *
 * router.post() memakai axios, yang otomatis mengirim header X-XSRF-TOKEN dari
 * cookie XSRF-TOKEN. Cookie itu ditulis ulang Laravel di SETIAP response, jadi
 * selalu sinkron dengan sesi terbaru.
 */
export default function LogoutButton({ className, children, title }) {
    const handleLogout = (e) => {
        e.preventDefault();
        router.post(route('logout'), {}, {
            onError: () => { window.location.href = route('login'); },
        });
    };

    return (
        <button type="button" title={title} onClick={handleLogout} className={className}>
            {children}
        </button>
    );
}