import { useState, useEffect } from 'react'
import { router } from '@inertiajs/react'

const CONSENT_KEY = 'cookie_consent'
const GA_ID: string = (import.meta.env.VITE_GA_ID as string) || 'G-36D54FFH27'

function loadGA(id: string) {
    if (document.getElementById('ga-script')) return

    // Standard Google gtag pattern — must use `arguments`, not spread
    window.dataLayer = window.dataLayer || []
    /* eslint-disable prefer-rest-params */
    function gtag() { (window.dataLayer as any[]).push(arguments) }
    /* eslint-enable prefer-rest-params */
    ;(window as any).gtag = gtag

    gtag('js', new Date())
    gtag('config', id)  // send_page_view defaults to true — tracks current page

    const s = document.createElement('script')
    s.id = 'ga-script'
    s.async = true
    s.src = `https://www.googletagmanager.com/gtag/js?id=${id}`
    document.head.appendChild(s)

    // Track subsequent SPA navigations
    router.on('navigate', () => {
        setTimeout(() => {
            ;(window as any).gtag('config', id, {
                page_location: window.location.href,
                page_title: document.title,
            })
        }, 100)
    })
}

export function CookieBanner() {
    const [visible, setVisible] = useState(false)

    useEffect(() => {
        const consent = localStorage.getItem(CONSENT_KEY)
        if (consent === 'accepted') {
            loadGA(GA_ID)
        } else if (!consent) {
            setVisible(true)
        }
    }, [])

    function accept() {
        localStorage.setItem(CONSENT_KEY, 'accepted')
        setVisible(false)
        loadGA(GA_ID)
    }

    function decline() {
        localStorage.setItem(CONSENT_KEY, 'declined')
        setVisible(false)
    }

    if (!visible) return null

    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 border-t border-zinc-800 bg-zinc-950/95 backdrop-blur-sm">
            <div className="mx-auto flex max-w-6xl flex-col gap-4 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                <p className="text-sm text-zinc-400">
                    We use cookies to analyse site usage and improve your experience.{' '}
                    <a href="/legal/cookies" className="underline hover:text-white">
                        Cookie Policy
                    </a>
                </p>
                <div className="flex shrink-0 gap-3">
                    <button
                        onClick={decline}
                        className="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-400 transition-colors hover:border-zinc-500 hover:text-white"
                    >
                        Decline
                    </button>
                    <button
                        onClick={accept}
                        className="rounded-lg bg-white px-4 py-2 text-sm font-semibold text-zinc-900 transition-colors hover:bg-zinc-100"
                    >
                        Accept
                    </button>
                </div>
            </div>
        </div>
    )
}
