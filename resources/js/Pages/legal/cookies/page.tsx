import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"
import { SEOHead } from "@/components/documate/seo-head"

const MAIL_PRIVACY = import.meta.env.VITE_MAIL_PRIVACY as string || 'privacy@documate.nexkit.app'

const cookies = [
  {
    name: "Session cookie",
    purpose: "Keeps you logged in during your browser session.",
    type: "Essential",
    duration: "Session (expires on browser close)",
  },
  {
    name: "Remember me token",
    purpose: "Keeps you logged in across browser sessions when you choose 'Remember me'.",
    type: "Essential",
    duration: "30 days",
  },
  {
    name: "Guest ID cookie",
    purpose: "Identifies your guest session so your free daily usage is tracked without requiring an account.",
    type: "Essential",
    duration: "30 days",
  },
  {
    name: "CSRF token",
    purpose: "Protects against cross-site request forgery attacks on form submissions.",
    type: "Essential",
    duration: "Session",
  },
]

export default function CookiesPage() {
  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="Cookie Policy — Documate"
        description="Documate uses only essential cookies to maintain your login session. No advertising or tracking cookies. Read our full cookie policy."
        canonical="/legal/cookies"
      />
      <Navbar />
      <main className="mx-auto max-w-3xl px-6 py-20">
        <div className="mb-12">
          <h1 className="text-3xl font-bold text-white">Cookie Policy</h1>
          <p className="mt-2 text-sm text-zinc-500">Last updated: April 2026</p>
        </div>

        <div className="space-y-10 text-sm leading-7 text-zinc-400">
          <section>
            <h2 className="text-base font-semibold text-white">What Are Cookies?</h2>
            <p className="mt-2">
              Cookies are small text files placed on your device by a website. They allow the site to remember your preferences and maintain your session between page loads.
            </p>
          </section>

          <section>
            <h2 className="text-base font-semibold text-white">Cookies We Use</h2>
            <p className="mt-2 mb-4">Documate uses only essential cookies — no advertising, analytics tracking, or third-party marketing cookies.</p>

            <div className="overflow-hidden rounded-xl border border-zinc-800">
              <table className="w-full text-xs">
                <thead className="bg-zinc-800/50">
                  <tr>
                    <th className="px-4 py-3 text-left font-medium text-zinc-300">Cookie</th>
                    <th className="px-4 py-3 text-left font-medium text-zinc-300">Purpose</th>
                    <th className="px-4 py-3 text-left font-medium text-zinc-300">Type</th>
                    <th className="px-4 py-3 text-left font-medium text-zinc-300">Duration</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-zinc-800">
                  {cookies.map((c) => (
                    <tr key={c.name}>
                      <td className="px-4 py-3 font-mono text-zinc-300">{c.name}</td>
                      <td className="px-4 py-3 text-zinc-400">{c.purpose}</td>
                      <td className="px-4 py-3 text-zinc-400">{c.type}</td>
                      <td className="px-4 py-3 text-zinc-500">{c.duration}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </section>

          <section>
            <h2 className="text-base font-semibold text-white">Managing Cookies</h2>
            <p className="mt-2">
              You can block or delete cookies through your browser settings. Note that disabling essential cookies will break login functionality and guest session tracking. We do not provide a cookie consent banner because we only use strictly necessary cookies that do not require consent under GDPR.
            </p>
          </section>

          <section>
            <h2 className="text-base font-semibold text-white">Third-Party Cookies</h2>
            <p className="mt-2">
              When you complete a payment, Stripe may set its own cookies for fraud prevention and payment session management. These are governed by <span className="text-zinc-300">Stripe's Privacy Policy</span>.
            </p>
          </section>

          <section>
            <h2 className="text-base font-semibold text-white">Contact</h2>
            <p className="mt-2">
              Questions about our cookie usage? Contact us at {MAIL_PRIVACY}.
            </p>
          </section>
        </div>
      </main>
      <Footer />
    </div>
  )
}
