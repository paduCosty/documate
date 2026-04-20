import { Link } from '@inertiajs/react'
import { Navbar } from '@/components/documate/navbar'
import { Footer } from '@/components/documate/footer'

const messages: Record<number, { title: string; description: string }> = {
    404: {
        title: 'Page not found',
        description: "The page you're looking for doesn't exist or has been moved.",
    },
    500: {
        title: 'Server error',
        description: 'Something went wrong on our end. Please try again in a moment.',
    },
    503: {
        title: 'Service unavailable',
        description: "We're down for maintenance. Please check back shortly.",
    },
    403: {
        title: 'Access denied',
        description: "You don't have permission to view this page.",
    },
}

export default function Error({ status }: { status: number }) {
    const { title, description } = messages[status] ?? {
        title: 'Unexpected error',
        description: 'An unexpected error occurred.',
    }

    return (
        <>
            <Navbar />
            <main className="flex min-h-[60vh] flex-col items-center justify-center px-6 py-24 text-center">
                <p className="text-5xl font-bold text-zinc-600">{status}</p>
                <h1 className="mt-4 text-2xl font-semibold text-white">{title}</h1>
                <p className="mt-2 text-zinc-400">{description}</p>
                <Link
                    href="/"
                    className="mt-8 rounded-lg bg-white px-5 py-2.5 text-sm font-semibold text-zinc-900 transition-colors hover:bg-zinc-100"
                >
                    Back to home
                </Link>
            </main>
            <Footer />
        </>
    )
}
