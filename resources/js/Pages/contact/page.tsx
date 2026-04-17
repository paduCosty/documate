import { useForm, usePage } from "@inertiajs/react"
import { SEOHead } from "@/components/documate/seo-head"
import { Mail, Clock, CheckCircle, AlertCircle } from "lucide-react"
import { Navbar } from "@/components/documate/navbar"
import { Footer } from "@/components/documate/footer"

const MAIL_SUPPORT = import.meta.env.VITE_MAIL_SUPPORT as string || 'support@documate.nexkit.app'
import { DocumateCard } from "@/components/documate/documate-card"
import { DocumateBadge } from "@/components/documate/documate-badge"
import { DocumateInput } from "@/components/documate/documate-input"
import { Link } from "@inertiajs/react"

const subjects = [
  { value: "general",   label: "General question" },
  { value: "billing",   label: "Billing" },
  { value: "technical", label: "Technical issue" },
  { value: "feature",   label: "Feature request" },
]

export default function ContactPage() {
  const { flash = {} } = usePage().props as any

  const { data, setData, post, processing, errors, reset } = useForm({
    name:    "",
    email:   "",
    subject: "general",
    message: "",
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    post(route("contact.send"), {
      onSuccess: () => reset(),
    })
  }

  return (
    <div className="min-h-screen bg-zinc-950">
      <SEOHead
        title="Contact — Documate"
        description="Get in touch with the Documate team. We'd love to hear your feedback or answer your questions."
        canonical="/contact"
      />
      <Navbar />

      <main className="mx-auto max-w-4xl px-6 py-24">
        <h1 className="text-4xl font-bold tracking-tight text-white">Get in touch</h1>
        <p className="mt-4 text-zinc-500">Have a question or feedback? We&apos;d love to hear from you.</p>

        {flash.success && (
          <div className="mt-6 flex items-center gap-3 rounded-xl border border-emerald-700/50 bg-emerald-950/30 px-4 py-3 text-sm text-emerald-400">
            <CheckCircle className="h-4 w-4 shrink-0" />
            {flash.success}
          </div>
        )}
        {flash.error && (
          <div className="mt-6 flex items-center gap-3 rounded-xl border border-red-700/50 bg-red-950/30 px-4 py-3 text-sm text-red-400">
            <AlertCircle className="h-4 w-4 shrink-0" />
            {flash.error}
          </div>
        )}

        <div className="mt-10 grid grid-cols-1 gap-12 md:grid-cols-[1fr_300px]">
          {/* Form */}
          <DocumateCard>
            <form onSubmit={handleSubmit} className="space-y-5">
              <DocumateInput
                label="Name"
                placeholder="Your name"
                value={data.name}
                onChange={(e) => setData("name", e.target.value)}
                error={errors.name}
              />
              <DocumateInput
                label="Email"
                type="email"
                placeholder="you@example.com"
                value={data.email}
                onChange={(e) => setData("email", e.target.value)}
                error={errors.email}
              />
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-zinc-500">Subject</label>
                <select
                  value={data.subject}
                  onChange={(e) => setData("subject", e.target.value)}
                  className="w-full rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white focus:border-zinc-600 focus:outline-none">
                  {subjects.map((s) => (
                    <option key={s.value} value={s.value}>{s.label}</option>
                  ))}
                </select>
                {errors.subject && <p className="text-xs text-red-400">{errors.subject}</p>}
              </div>
              <div className="space-y-1.5">
                <label className="block text-xs font-medium text-zinc-500">Message</label>
                <textarea
                  placeholder="How can we help?"
                  rows={5}
                  value={data.message}
                  onChange={(e) => setData("message", e.target.value)}
                  className="w-full resize-none rounded-xl border border-zinc-800 bg-zinc-900 px-4 py-2.5 text-sm text-white placeholder:text-zinc-600 focus:border-zinc-600 focus:outline-none"
                />
                {errors.message && <p className="text-xs text-red-400">{errors.message}</p>}
              </div>
              <button
                type="submit"
                disabled={processing}
                className="rounded-xl bg-white px-5 py-2.5 text-sm font-semibold text-zinc-900 transition-colors hover:bg-zinc-100 disabled:opacity-50">
                {processing ? "Sending..." : "Send message"}
              </button>
            </form>
          </DocumateCard>

          {/* Info */}
          <DocumateCard className="h-fit space-y-6">
            <div className="flex items-start gap-3">
              <Mail className="mt-0.5 h-5 w-5 text-zinc-500" />
              <span className="text-sm text-white">{MAIL_SUPPORT}</span>
            </div>
            <div className="flex items-start gap-3">
              <Clock className="mt-0.5 h-5 w-5 text-zinc-500" />
              <div>
                <span className="text-sm text-zinc-400">We reply within 24 hours</span>
                <DocumateBadge variant="success" className="ml-2">Usually faster</DocumateBadge>
              </div>
            </div>
            <div className="space-y-2 border-t border-zinc-800 pt-6">
              <Link href="/faq" className="block text-sm text-zinc-500 transition-colors hover:text-white">
                Browse FAQ &rarr;
              </Link>
              <Link href="/tools" className="block text-sm text-zinc-500 transition-colors hover:text-white">
                View all tools &rarr;
              </Link>
            </div>
          </DocumateCard>
        </div>
      </main>

      <Footer />
    </div>
  )
}
